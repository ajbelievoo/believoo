package com.believoo.bmd

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.os.Build
import androidx.core.app.NotificationCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage

/**
 * Firebase Cloud Messaging for the BMyDesk agent.
 *
 * Two jobs:
 * 1. Keep the device's FCM token on our server (`register` API carries it in
 *    `fcm_token`) so the backend can wake this device with a data push when a
 *    viewer requests a session while the app is closed/doze.
 * 2. Render incoming "join-request" pushes as a heads-up notification that
 *    deep-opens MainActivity.
 */
class BmdFirebaseService : FirebaseMessagingService() {

    override fun onNewToken(token: String) {
        super.onNewToken(token)
        val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        if (prefs.getString("fcm_token", null) == token) return
        prefs.edit().putString("fcm_token", token).apply()
        // Re-register in the background so the server learns the new token —
        // register() is idempotent and keeps the same permanent session code.
        Thread {
            runCatching {
                AgentApi().register(
                    android.os.Build.MODEL ?: "android-device", "android",
                    prefs.getString("member_token", null),
                    prefs.getString("device_id", null),
                    token
                )
            }
        }.start()
    }

    override fun onMessageReceived(msg: RemoteMessage) {
        super.onMessageReceived(msg)
        // Data pushes from the bmydesk backend — e.g. a viewer join-request
        // arriving while the app is in the background.
        val kind = msg.data["kind"] ?: return
        if (kind == "join-request") {
            val name = msg.data["name"] ?: "Someone"
            notify(
                id = 9001,
                title = "BMyDesk — connection request",
                text = "$name wants to view this device. Tap to respond."
            )
        } else if (kind == "ping") {
            // keep-alive nudge — nothing to surface
        }
    }

    private fun notify(id: Int, title: String, text: String) {
        val nm = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        val chId = "bmydesk_requests"
        if (Build.VERSION.SDK_INT >= 26) {
            nm.createNotificationChannel(
                NotificationChannel(chId, "Connection requests", NotificationManager.IMPORTANCE_HIGH)
            )
        }
        val intent = Intent(this, MainActivity::class.java).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        val pi = PendingIntent.getActivity(
            this, id, intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )
        val n = NotificationCompat.Builder(this, chId)
            .setSmallIcon(R.drawable.ic_notification)
            .setColor(0xfffb7185.toInt())
            .setContentTitle(title)
            .setContentText(text)
            .setContentIntent(pi)
            .setAutoCancel(true)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setCategory(NotificationCompat.CATEGORY_CALL)
            .build()
        nm.notify(id, n)
    }
}
