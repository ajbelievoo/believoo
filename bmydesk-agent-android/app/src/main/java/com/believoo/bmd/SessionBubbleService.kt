package com.believoo.bmd

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Intent
import android.content.pm.ServiceInfo
import android.graphics.Color
import android.graphics.PixelFormat
import android.graphics.drawable.GradientDrawable
import android.os.Build
import android.os.IBinder
import android.provider.Settings
import android.view.Gravity
import android.view.MotionEvent
import android.view.View
import android.view.WindowManager
import android.widget.ImageView

/**
 * Floating session bubble (AnyDesk/Facebook-chat-head style).
 *
 * Lives while a remote-viewer session is minimized — the WebRTC peer keeps
 * running inside MainActivity; this service just holds a foreground
 * notification + a draggable overlay dot so the user can jump back in.
 * The overlay itself needs SYSTEM_ALERT_WINDOW; without it we still show
 * the persistent notification (tap = same resume action).
 */
class SessionBubbleService : Service() {

    override fun onBind(i: Intent?): IBinder? = null

    private var wm: WindowManager? = null
    private var bubble: View? = null

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        startForegroundNotif()
        if (bubble == null) addBubble()
        return START_STICKY
    }

    private fun resumeIntent(): PendingIntent {
        val i = Intent(this, MainActivity::class.java).apply {
            putExtra("resume_viewer", true)
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_SINGLE_TOP)
        }
        return PendingIntent.getActivity(this, 7, i,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE)
    }

    private fun startForegroundNotif() {
        val nm = getSystemService(NotificationManager::class.java)
        nm.createNotificationChannel(
            NotificationChannel(CHAN, "Remote session", NotificationManager.IMPORTANCE_LOW))
        val n = Notification.Builder(this, CHAN)
            .setContentTitle("BMyDesk — remote session active")
            .setContentText("Tap to return to the remote screen")
            .setSmallIcon(R.drawable.ic_notification)
            .setOngoing(true)
            .setContentIntent(resumeIntent())
            .build()
        if (Build.VERSION.SDK_INT >= 29)
            startForeground(ID, n, ServiceInfo.FOREGROUND_SERVICE_TYPE_DATA_SYNC)
        else startForeground(ID, n)
    }

    private fun addBubble() {
        if (!Settings.canDrawOverlays(this)) return // notification still works
        wm = getSystemService(WINDOW_SERVICE) as WindowManager
        val d = resources.displayMetrics.density
        val sz = (54 * d).toInt()
        val lp = WindowManager.LayoutParams(
            sz, sz,
            if (Build.VERSION.SDK_INT >= 26) WindowManager.LayoutParams.TYPE_APPLICATION_OVERLAY
            else @Suppress("DEPRECATION") WindowManager.LayoutParams.TYPE_PHONE,
            WindowManager.LayoutParams.FLAG_NOT_FOCUSABLE,
            PixelFormat.TRANSLUCENT
        ).apply {
            gravity = Gravity.TOP or Gravity.START
            x = resources.displayMetrics.widthPixels - sz - (8 * d).toInt()
            y = (resources.displayMetrics.heightPixels * 0.35f).toInt()
        }
        val iv = ImageView(this).apply {
            setImageResource(R.mipmap.ic_launcher)
            background = GradientDrawable().apply {
                shape = GradientDrawable.OVAL
                setColor(Color.parseColor("#0f172a"))
                setStroke((2 * d).toInt(), Color.parseColor("#e11d48"))
            }
            scaleType = ImageView.ScaleType.FIT_CENTER
            setPadding((8 * d).toInt(), (8 * d).toInt(), (8 * d).toInt(), (8 * d).toInt())
            elevation = 12 * d
        }
        // drag = move the bubble along the screen edge; short tap = resume
        var dx = 0f; var dy = 0f; var moved = false
        iv.setOnTouchListener { v, ev ->
            when (ev.action) {
                MotionEvent.ACTION_DOWN -> { dx = ev.rawX - lp.x; dy = ev.rawY - lp.y; moved = false; true }
                MotionEvent.ACTION_MOVE -> {
                    if (Math.abs(ev.rawX - lp.x - dx) > 4 || Math.abs(ev.rawY - lp.y - dy) > 4) moved = true
                    lp.x = (ev.rawX - dx).toInt(); lp.y = (ev.rawY - dy).toInt()
                    runCatching { wm?.updateViewLayout(v, lp) }; true
                }
                MotionEvent.ACTION_UP -> {
                    // snap to nearest edge like a chat head
                    val w = resources.displayMetrics.widthPixels
                    lp.x = if (lp.x + sz / 2 < w / 2) (6 * d).toInt() else w - sz - (6 * d).toInt()
                    runCatching { wm?.updateViewLayout(v, lp) }
                    // PendingIntent.send() — background-start-safe way to
                    // reopen the activity (direct startActivity is blocked)
                    if (!moved) runCatching { resumeIntent().send() }
                    true
                }
                else -> false
            }
        }
        runCatching { wm?.addView(iv, lp); bubble = iv }
    }

    override fun onDestroy() {
        bubble?.let { runCatching { wm?.removeView(it) } }
        bubble = null; wm = null
        super.onDestroy()
    }

    companion object {
        const val CHAN = "bmd_session"
        const val ID = 42
    }
}
