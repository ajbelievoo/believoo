package com.believoo.bmd

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
        fun onCtlReady() {}
        fun onChat(from: String, text: String) {}
        fun onChatMsg(id: String, from: String, text: String) { onChat(from, text) }
        fun onChatAck(id: String) {}
        fun onChatSeen(id: String) {}
        fun onCallState(kind: String, on: Boolean) {}
        fun onPipTrack(track: org.webrtc.VideoTrack) {}
        fun onClip(text: String) {}
        fun onFile(name: String, data: ByteArray) {}
        fun onFileProgress(received: Int, total: Int) {}
        fun onTyping(from: String) {}
        fun onWantCtl() {}
        fun onLock() {}
        fun onVol(dir: String) {}
    }

    var events: Events? = null
    @Volatile private var host: WebRtcHost? = null
    var signaling: SignalingClient? = null
    private var iceServers = listOf<PeerConnection.IceServer>()

    override fun onBind(i: Intent?): IBinder = LocalBinder()

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        active = this
        when (intent?.action) {
            ACTION_START -> startForegroundNotif()
            // pre-build the PeerConnectionFactory/ADM while the user reads the
            // projection consent dialog — that dead time hides the ~1-3s init
            ACTION_WARM -> Thread { ensureHost() }.start()
            ACTION_CAPTURE -> {
                val data = intent.getParcelableExtra<Intent>(EXTRA_RESULT_DATA)
                    ?: run { events?.onError("No projection data"); return START_NOT_STICKY }
                startCapture(data)
            }
            ACTION_STOP -> {
                RemoteControlService.blocked = false
                host?.stop(); host = null; pendingOffer = null
                wakeLock?.let { runCatching { if (it.isHeld) it.release() } }; wakeLock = null
                stopSelf()
            }
        }
        return START_STICKY
    }

    fun setIce(list: List<PeerConnection.IceServer>) { iceServers = list }
    fun peer(): WebRtcHost? = host
    fun ctl(): CtlChannel? = host?.ctl

    private fun startForegroundNotif() {
        val chanId = "bmydesk_share"
        val nm = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        nm.createNotificationChannel(NotificationChannel(chanId, "Screen sharing", NotificationManager.IMPORTANCE_LOW))
        val pi = PendingIntent.getActivity(this, 0, Intent(this, MainActivity::class.java),
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT)
        val notif: Notification = Notification.Builder(this, chanId)
            .setContentTitle("BMyDesk — screen sharing")
            .setContentText("Your screen is being shared in a remote session")
            .setSmallIcon(R.drawable.ic_notification)
            .setContentIntent(pi)
            .setOngoing(true)
            .build()
        if (Build.VERSION.SDK_INT >= 34) {
            startForeground(1, notif, ServiceInfo.FOREGROUND_SERVICE_TYPE_MEDIA_PROJECTION)
        } else {
            startForeground(1, notif)
        }
    }

    @Volatile private var pendingOffer: String? = null
    @Volatile private var wakeLock: android.os.PowerManager.WakeLock? = null

    // PeerConnectionFactory + EGL + codec enumeration are heavy (seconds on
    // emulators / low-end phones) — never on the main thread, or Android ANRs.
    private fun startCapture(resultData: Intent) {
        // partial wake lock: the app is meant to sit minimized while the
        // viewer drives the phone — without this, doze can stall encoder +
        // data-channel threads and remote input silently stops landing.
        if (wakeLock == null) {
            val pm = getSystemService(Context.POWER_SERVICE) as android.os.PowerManager
            wakeLock = pm.newWakeLock(android.os.PowerManager.PARTIAL_WAKE_LOCK, "bmd:session")
                .apply { setReferenceCounted(false); runCatching { acquire(4 * 60 * 60 * 1000L) } }
        }
        val wm = getSystemService(Context.WINDOW_SERVICE) as WindowManager
        val m = DisplayMetrics(); @Suppress("DEPRECATION") wm.defaultDisplay.getRealMetrics(m)
        // keep aspect ratio, cap long side at 1920 — mid-range SoCs stall the
        // encoder at 2560-class sizes and the viewer's decoder re-configures
        // (MediaCodec BAD_VALUE) on every resize, freezing the picture.
        // 'fhd' quality mode still scales back up through setQuality().
        val longSide = maxOf(m.widthPixels, m.heightPixels).toFloat()
        val k = if (longSide > 1920f) 1920f / longSide else 1f
        val cw = ((m.widthPixels * k).toInt() / 2) * 2
        val ch = ((m.heightPixels * k).toInt() / 2) * 2
        Thread {
            val h = ensureHost()
            h.startCapture(resultData, cw, ch, 24)
            pendingOffer?.let { pendingOffer = null; h.handleOffer(it, iceServers) }
        }.start()
    }

    // Built once, on a background thread — PeerConnectionFactory + codec/ADM
    // init is the single biggest chunk of accept→picture latency. WARM'd
    // during the consent dialog so capture starts instantly after Allow.
    @Synchronized
    private fun ensureHost(): WebRtcHost {
        host?.let { return it }
        val h = WebRtcHost(this)
        h.listener = object : WebRtcHost.Listener {
                override fun sendSignal(payload: JsonObject) { signaling?.send("signal", payload) }
                override fun onPeerConnected() { events?.onConnected() }
                override fun onPeerDisconnected() { events?.onDisconnected() }
                override fun onPipTrack(track: org.webrtc.VideoTrack) { events?.onPipTrack(track) }
                override fun onError(message: String) { events?.onError(message) }
            }
        h.ctl.sink = object : CtlChannel.Sink {
            override fun onCtlOpen() { events?.onCtlReady() }
            override fun onChat(from: String, text: String) { events?.onChat(from, text) }
            override fun onChatMsg(id: String, from: String, text: String) { events?.onChatMsg(id, from, text) }
            override fun onChatAck(id: String) { events?.onChatAck(id) }
            override fun onChatSeen(id: String) { events?.onChatSeen(id) }
            override fun onCallState(kind: String, on: Boolean) { events?.onCallState(kind, on) }
            override fun onClip(text: String) { events?.onClip(text) }
            override fun onFile(name: String, data: ByteArray) { events?.onFile(name, data) }
            override fun onFileProgress(r: Int, t: Int) { events?.onFileProgress(r, t) }
            override fun onTyping(from: String) { events?.onTyping(from) }
            override fun onWantCtl() { events?.onWantCtl() }
            override fun onLock() { events?.onLock() }
            override fun onVol(dir: String) { events?.onVol(dir) }
            // viewer's quality toggle actually retunes this capture
            override fun onQuality(mode: String) { h.setQuality(mode) }
        }
        host = h
        return h
    }

    fun answerOffer(offerSdp: String) {
        val h = host
        if (h == null) pendingOffer = offerSdp else h.handleOffer(offerSdp, iceServers)
    }
    fun addIce(c: JsonObject) { host?.addIce(c) }

    override fun onDestroy() {
        if (active === this) active = null
        host?.stop()
        wakeLock?.let { runCatching { if (it.isHeld) it.release() } }; wakeLock = null
        super.onDestroy()
    }

    companion object {
        const val ACTION_START = "com.believoo.bmd.START"
        const val ACTION_WARM = "com.believoo.bmd.WARM"
        const val ACTION_CAPTURE = "com.believoo.bmd.CAPTURE"
        const val ACTION_STOP = "com.believoo.bmd.STOP"
        const val EXTRA_RESULT_DATA = "result_data"
        // lets SessionBubbleService reach the live ctl channel even when the
        // activity is dead — the overlay must keep working without the UI
        @Volatile var active: ScreenShareService? = null
    }
}
