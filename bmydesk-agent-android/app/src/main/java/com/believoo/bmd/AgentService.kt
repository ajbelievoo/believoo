package com.believoo.bmd

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Context
import android.content.Intent
import android.content.pm.ServiceInfo
import android.os.Build
import android.os.IBinder
import kotlin.concurrent.thread

/**
 * Always-on agent service. Its only jobs:
 *  1. Keep the process (and so MainActivity's ws + poll threads) alive after
 *     the user swipes the app away — a dataSync FGS stops Android from
 *     freezing/killing us between sessions.
 *  2. Heartbeat /status every few seconds. This keeps the device's
 *     "host_alive" fresh server-side AND spots a session in 'connecting'
 *     state — surfacing it as a heads-up notification when the UI is closed.
 *
 * It deliberately does NOT drain the signal queue or run WebRTC — the
 * activity owns the session flow; this is just reachability glue.
 */
class AgentService : Service() {

    override fun onBind(i: Intent?): IBinder? = null

    @Volatile private var running = false
    @Volatile private var reqNotified = false

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        startForegroundNotif()
        if (!running) {
            running = true
            thread { loop() }
        }
        return START_STICKY
    }

    private fun startForegroundNotif() {
        val nm = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        nm.createNotificationChannel(NotificationChannel(CHAN_RUN, "BMyDesk agent", NotificationManager.IMPORTANCE_MIN))
        val pi = PendingIntent.getActivity(this, 0, Intent(this, MainActivity::class.java),
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT)
        val n: Notification = Notification.Builder(this, CHAN_RUN)
            .setContentTitle("BMyDesk agent")
            .setContentText("Your device code stays reachable")
            .setSmallIcon(R.drawable.ic_notification)
            .setContentIntent(pi)
            .setOngoing(true)
            .build()
        if (Build.VERSION.SDK_INT >= 34) {
            startForeground(ID, n, ServiceInfo.FOREGROUND_SERVICE_TYPE_DATA_SYNC)
        } else {
            startForeground(ID, n)
        }
    }

    private fun loop() {
        val api = AgentApi()
        Thread.sleep(6000) // let the activity's own register settle first
        while (true) {
            try {
                val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
                val code = prefs.getString("session_code", null)
                val token = prefs.getString("agent_token", null)
                if (code != null && token != null) {
                    val s = api.status(code, token)
                    if (s?.get("ok")?.asBoolean == true) {
                        val st = s.get("status").s()
                        if (st == "connecting") {
                            // UI closed → heads-up so the host actually sees it;
                            // UI open → MainActivity's reqBox handles it.
                            if (!MainActivity.foreground && !reqNotified) {
                                reqNotified = true
                                notifyRequest(s.get("viewer").s())
                            }
                        } else {
                            reqNotified = false
                        }
                    }
                }
            } catch (_: Exception) {}
            Thread.sleep(4000)
        }
    }

    private fun notifyRequest(viewerName: String?) {
        val nm = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        if (Build.VERSION.SDK_INT >= 26) {
            nm.createNotificationChannel(NotificationChannel(CHAN_REQ, "Connection requests", NotificationManager.IMPORTANCE_HIGH))
        }
        val pi = PendingIntent.getActivity(this, 1, Intent(this, MainActivity::class.java),
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT)
        val n = Notification.Builder(this, CHAN_REQ)
            .setContentTitle("BMyDesk — connection request")
            .setContentText("${viewerName ?: "Someone"} wants to view this device — tap to respond")
            .setSmallIcon(R.drawable.ic_notification)
            .setContentIntent(pi)
            .setAutoCancel(true)
            .setCategory(Notification.CATEGORY_CALL)
            .build()
        nm.notify(REQ_ID, n)
    }

    companion object {
        const val ID = 77
        const val REQ_ID = 78
        const val CHAN_RUN = "bmydesk_agent_run"
        const val CHAN_REQ = "bmydesk_requests"
    }
}
