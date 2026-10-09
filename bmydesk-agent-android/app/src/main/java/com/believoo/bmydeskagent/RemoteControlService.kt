package com.believoo.bmydeskagent

import android.accessibilityservice.AccessibilityService
import android.accessibilityservice.GestureDescription
import android.graphics.Path
import android.graphics.PointF
import android.os.SystemClock
import android.view.accessibility.AccessibilityEvent
import android.content.Intent
import com.google.gson.JsonObject

/**
 * Accessibility service that turns a viewer's input-channel messages into real
 * touch gestures on this device — this is what makes an Android host fully
 * controllable (AnyDesk-style). The user enables it once in system settings;
 * every injection happens only while a remote session is live.
 */
class RemoteControlService : AccessibilityService() {

    companion object {
        @Volatile var instance: RemoteControlService? = null
        fun enabled() = instance != null
    }

    override fun onServiceConnected() { instance = this }
    override fun onUnbind(i: Intent?): Boolean { instance = null; return false }
    override fun onAccessibilityEvent(e: AccessibilityEvent?) {}
    override fun onInterrupt() {}

    // ── gesture state for continuous drag strokes ──
    private var strokeDesc: GestureDescription.StrokeDescription? = null
    private var strokeT0 = 0L
    private var lastX = 0f
    private var lastY = 0f
    private var downAt = 0L
    private var moved = false
    private var lastMoveAt = 0L

    /** Map a normalized (0..1) input message to a real gesture/action. */
    fun handleInput(m: JsonObject, scrW: Int, scrH: Int) {
        val t = m.get("t")?.asString ?: return
        val x = (m.get("x")?.asFloat ?: 0f) * scrW
        val y = (m.get("y")?.asFloat ?: 0f) * scrH
        when (t) {
            "down" -> onDown(x, y)
            "move" -> onMove(x, y)
            "up" -> onUp(x, y)
            "key" -> onKey(m.get("code")?.asString, m.get("down")?.asBoolean == true)
        }
    }

    private fun onDown(x: Float, y: Float) {
        lastX = x; lastY = y; moved = false
        downAt = SystemClock.uptimeMillis()
        strokeT0 = downAt
        val p = Path().apply { moveTo(x, y) }
        strokeDesc = GestureDescription.StrokeDescription(p, 0, 10, true)
        dispatch(strokeDesc!!)
    }

    private fun onMove(x: Float, y: Float) {
        val sd = strokeDesc ?: return
        if (Math.abs(x - lastX) + Math.abs(y - lastY) < 4) return
        moved = true
        // throttle stroke continuation ~30ms so the gesture queue stays small
        val now = SystemClock.uptimeMillis()
        if (now - lastMoveAt < 30) return
        lastMoveAt = now
        val seg = Path().apply { moveTo(lastX, lastY); lineTo(x, y) }
        strokeDesc = sd.continueStroke(seg, now - strokeT0, 40, true)
        lastX = x; lastY = y
        dispatch(strokeDesc!!)
    }

    private fun onUp(x: Float, y: Float) {
        val sd = strokeDesc
        if (sd == null) { tap(x, y); return }
        val dur = SystemClock.uptimeMillis() - downAt
        if (!moved && dur < 250) {
            // quick touch at one point → simple tap
            strokeDesc = null
            tap(x, y)
            return
        }
        val seg = Path().apply { moveTo(lastX, lastY); lineTo(x, y) }
        strokeDesc = sd.continueStroke(seg, SystemClock.uptimeMillis() - strokeT0, 20, false)
        dispatch(strokeDesc!!)
        strokeDesc = null
    }

    private fun tap(x: Float, y: Float) {
        val p = Path().apply { moveTo(x, y) }
        val sd = GestureDescription.StrokeDescription(p, 0, 60)
        dispatch(sd)
    }

    private fun dispatch(sd: GestureDescription.StrokeDescription) {
        runCatching { dispatchGesture(GestureDescription.Builder().addStroke(sd).build(), null, null) }
    }

    // Common nav keys map to global actions; the rest is ignored for now —
    // typed text goes through the clipboard + focused-field SET_TEXT later.
    private fun onKey(code: String?, down: Boolean) {
        if (!down) return
        when (code) {
            "Escape" -> performGlobalAction(GLOBAL_ACTION_BACK)
            "Home" -> performGlobalAction(GLOBAL_ACTION_HOME)
            "End" -> performGlobalAction(GLOBAL_ACTION_RECENTS)
            "F4" -> performGlobalAction(GLOBAL_ACTION_BACK)
            "PageDown" -> performGlobalAction(GLOBAL_ACTION_POWER_DIALOG)
            "F6" -> performGlobalAction(GLOBAL_ACTION_NOTIFICATIONS)
            "F7" -> performGlobalAction(GLOBAL_ACTION_QUICK_SETTINGS)
        }
    }
}
