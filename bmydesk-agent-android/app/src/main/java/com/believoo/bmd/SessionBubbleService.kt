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
import android.widget.TextView

/**
 * Floating session bubble (AnyDesk / Facebook chat-head style).
 *
 * Runs while a remote session is minimized — the WebRTC peer keeps working
 * in ScreenShareService / MainActivity; this service holds a foreground
 * notification + a draggable overlay dot so the user can jump back in.
 *
 * Tap the dot → a mini toolbar fans out above it:
 *   ⌂ open the app   🎙 mic on/off   📹 camera on/off   ✕ end session
 * The overlay itself needs SYSTEM_ALERT_WINDOW; without it the persistent
 * notification (tap = resume) still covers returning to the session.
 */
class SessionBubbleService : Service() {

    override fun onBind(i: Intent?): IBinder? = null

    private var wm: WindowManager? = null
    private var bubble: View? = null
    private var menu: View? = null
    private val micOn get() = micState
    private val camOn get() = camState
    private val ctlOn get() = !viewOnlyState

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

    private fun confirmIntent(): PendingIntent {
        // the Yes/No dialog lives in the activity — open it straight there
        val i = Intent(this, MainActivity::class.java).apply {
            putExtra("confirm_disconnect", true)
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_SINGLE_TOP)
        }
        return PendingIntent.getActivity(this, 8, i,
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

    private fun overlayLp(w: Int, h: Int, x: Int, y: Int): WindowManager.LayoutParams =
        WindowManager.LayoutParams(
            w, h,
            if (Build.VERSION.SDK_INT >= 26) WindowManager.LayoutParams.TYPE_APPLICATION_OVERLAY
            else @Suppress("DEPRECATION") WindowManager.LayoutParams.TYPE_PHONE,
            WindowManager.LayoutParams.FLAG_NOT_FOCUSABLE or WindowManager.LayoutParams.FLAG_LAYOUT_NO_LIMITS,
            PixelFormat.TRANSLUCENT
        ).apply { gravity = Gravity.TOP or Gravity.START; this.x = x; this.y = y }

    private fun addBubble() {
        if (!Settings.canDrawOverlays(this)) return // notification still works
        wm = getSystemService(WINDOW_SERVICE) as WindowManager
        val d = resources.displayMetrics.density
        val sz = (54 * d).toInt()
        val lp = overlayLp(sz, sz,
            resources.displayMetrics.widthPixels - sz - (8 * d).toInt(),
            (resources.displayMetrics.heightPixels * 0.35f).toInt())

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
                    val w = resources.displayMetrics.widthPixels
                    lp.x = if (lp.x + sz / 2 < w / 2) (6 * d).toInt() else w - sz - (6 * d).toInt()
                    runCatching { wm?.updateViewLayout(v, lp) }
                    if (!moved) toggleMenu(lp)
                    true
                }
                else -> false
            }
        }
        runCatching { wm?.addView(iv, lp); bubble = iv }
    }

    // ── radial mini-menu — fans out above the bubble on tap ──
    private fun menuBtn(glyph: String, tint: Int, onTap: () -> Unit): View {
        val d = resources.displayMetrics.density
        val sz = (42 * d).toInt()
        return TextView(this).apply {
            text = glyph; textSize = 16f; gravity = Gravity.CENTER
            setTextColor(Color.WHITE)
            background = GradientDrawable().apply {
                shape = GradientDrawable.OVAL
                setColor(tint)
                setStroke((1.5f * d).toInt(), Color.parseColor("#475569"))
            }
            elevation = 10 * d
            layoutParams = android.widget.LinearLayout.LayoutParams(sz, sz).apply {
                marginEnd = (6 * d).toInt()
            }
            setOnClickListener { onTap() }
        }
    }

    private fun toggleMenu(bLp: WindowManager.LayoutParams) {
        if (menu != null) { runCatching { wm?.removeView(menu) }; menu = null; return }
        val w = wm ?: return
        val d = resources.displayMetrics.density
        val row = android.widget.LinearLayout(this).apply {
            orientation = android.widget.LinearLayout.HORIZONTAL
            setPadding((6 * d).toInt(), (6 * d).toInt(), (6 * d).toInt(), (6 * d).toInt())
            background = GradientDrawable().apply {
                cornerRadius = 24 * d
                setColor(Color.parseColor("#0f172a"))
                setStroke((1 * d).toInt(), Color.parseColor("#334155"))
            }
            elevation = 14 * d
        }
        row.addView(menuBtn("⌂", 0xFF334155.toInt()) { runCatching { resumeIntent().send() } })
        row.addView(menuBtn("🎙", if (micOn) 0xFF15803D.toInt() else 0xFF334155.toInt()) {
            actionListener?.invoke("mic"); paintMenu(row)
        })
        row.addView(menuBtn("📹", if (camOn) 0xFF15803D.toInt() else 0xFF334155.toInt()) {
            actionListener?.invoke("cam"); paintMenu(row)
        })
        // host-side control lock — 🔓 = viewer can control, 👁 = view-only.
        // Flip the statics FIRST so the tap works even when MainActivity is
        // dead/minimized — the overlay + service outlive the UI; the listener
        // then re-asserts for chat/log/UI paint (idempotent).
        row.addView(menuBtn(if (ctlOn) "🔓" else "👁", if (ctlOn) 0xFF15803D.toInt() else 0xFF7F1D1D.toInt()) {
            viewOnlyState = !viewOnlyState
            RemoteControlService.blocked = viewOnlyState
            runCatching { ScreenShareService.active?.ctl()?.sendViewOnly(viewOnlyState) }
            actionListener?.invoke("ctl")
            paintMenu(row)
        })
        row.addView(menuBtn("✕", 0xFFDC2626.toInt()) { runCatching { confirmIntent().send() }; toggleMenu(bLp) })

        val mw = (5 * 48 * d + 24 * d).toInt()
        val mx = (bLp.x + bLp.width / 2 - mw / 2).coerceIn(0,
            resources.displayMetrics.widthPixels - mw)
        val my = (bLp.y - (64 * d).toInt()).coerceAtLeast((24 * d).toInt())
        val mlp = overlayLp(mw, (56 * d).toInt(), mx, my)
        runCatching { w.addView(row, mlp); menu = row }
    }

    private fun paintMenu(row: android.widget.LinearLayout) {
        // repaints mic/cam/control pills from the ACTIVITY-side truth —
        // the service just displays the state MainActivity last set
        row.getChildAt(1)?.background = GradientDrawable().apply {
            shape = GradientDrawable.OVAL
            setColor(if (micOn) 0xFF15803D.toInt() else 0xFF334155.toInt())
            setStroke(3, Color.parseColor("#475569"))
        }
        row.getChildAt(2)?.background = GradientDrawable().apply {
            shape = GradientDrawable.OVAL
            setColor(if (camOn) 0xFF15803D.toInt() else 0xFF334155.toInt())
            setStroke(3, Color.parseColor("#475569"))
        }
        (row.getChildAt(3) as? TextView)?.let {
            it.text = if (ctlOn) "🔓" else "👁"
            it.background = GradientDrawable().apply {
                shape = GradientDrawable.OVAL
                setColor(if (ctlOn) 0xFF15803D.toInt() else 0xFF7F1D1D.toInt())
                setStroke(3, Color.parseColor("#475569"))
            }
        }
    }

    override fun onDestroy() {
        menu?.let { runCatching { wm?.removeView(it) } }
        bubble?.let { runCatching { wm?.removeView(it) } }
        menu = null; bubble = null; wm = null
        super.onDestroy()
    }

    companion object {
        const val CHAN = "bmd_session"
        const val ID = 42
        /** MainActivity registers here — "mic" / "cam" toggles land on the
            live peer (viewer or host) without reopening the UI. */
        @Volatile var actionListener: ((String) -> Unit)? = null
        @Volatile var micState = false
        @Volatile var camState = false
        @Volatile var viewOnlyState = false
    }
}
