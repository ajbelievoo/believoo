package com.believoo.bmydeskagent

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Context
import android.content.Intent
import android.content.pm.ServiceInfo
import android.os.Binder
import android.os.Build
import android.os.IBinder
import android.util.DisplayMetrics
import android.view.WindowManager
import com.google.gson.JsonObject
import org.webrtc.PeerConnection

/**
 * Foreground service that owns the screen capture + WebRTC host peer.
 * Android 14+ requires a mediaProjection-type FGS before capture starts.
 */
class ScreenShareService : Service() {

    inner class LocalBinder : Binder() { val service get() = this@ScreenShareService }

    interface Events {
        fun onConnected()      // viewer P2P established
        fun onDisconnected()
        fun onError(msg: String)
    }

    var events: Events? = null
    private var host: WebRtcHost? = null
    var signaling: SignalingClient? = null
    private var iceServers = listOf<PeerConnection.IceServer>()

    override fun onBind(i: Intent?): IBinder = LocalBinder()

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        when (intent?.action) {
            ACTION_START -> startForegroundNotif()
            ACTION_CAPTURE -> {
                val data = intent.getParcelableExtra<Intent>(EXTRA_RESULT_DATA)
                    ?: run { events?.onError("No projection data"); return START_NOT_STICKY }
                startCapture(data)
            }
            ACTION_STOP -> { host?.stop(); stopSelf() }
        }
        return START_STICKY
    }

    fun setIce(list: List<PeerConnection.IceServer>) { iceServers = list }
    fun peer(): WebRtcHost? = host

    private fun startForegroundNotif() {
        val chanId = "bmydesk_share"
        val nm = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        nm.createNotificationChannel(NotificationChannel(chanId, "Screen sharing", NotificationManager.IMPORTANCE_LOW))
        val pi = PendingIntent.getActivity(this, 0, Intent(this, MainActivity::class.java),
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT)
        val notif: Notification = Notification.Builder(this, chanId)
            .setContentTitle("BMyDesk — screen sharing")
            .setContentText("Your screen is being shared in a remote session")
            .setSmallIcon(android.R.drawable.presence_video_online)
            .setContentIntent(pi)
            .setOngoing(true)
            .build()
        if (Build.VERSION.SDK_INT >= 34) {
            startForeground(1, notif, ServiceInfo.FOREGROUND_SERVICE_TYPE_MEDIA_PROJECTION)
        } else {
            startForeground(1, notif)
        }
    }

    private fun startCapture(resultData: Intent) {
        val wm = getSystemService(Context.WINDOW_SERVICE) as WindowManager
        val m = DisplayMetrics(); @Suppress("DEPRECATION") wm.defaultDisplay.getRealMetrics(m)
        host = WebRtcHost(this).also { h ->
            h.listener = object : WebRtcHost.Listener {
                override fun sendSignal(payload: JsonObject) { signaling?.send("signal", payload) }
                override fun onPeerConnected() { events?.onConnected() }
                override fun onPeerDisconnected() { events?.onDisconnected() }
                override fun onError(message: String) { events?.onError(message) }
            }
            h.startCapture(resultData, m.widthPixels, m.heightPixels, 15)
        }
    }

    fun answerOffer(offerSdp: String) { host?.handleOffer(offerSdp, iceServers) }
    fun addIce(c: JsonObject) { host?.addIce(c) }

    override fun onDestroy() {
        host?.stop()
        super.onDestroy()
    }

    companion object {
        const val ACTION_START = "com.believoo.bmydeskagent.START"
        const val ACTION_CAPTURE = "com.believoo.bmydeskagent.CAPTURE"
        const val ACTION_STOP = "com.believoo.bmydeskagent.STOP"
        const val EXTRA_RESULT_DATA = "result_data"
    }
}
