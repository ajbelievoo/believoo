package com.believoo.bmd

import android.accessibilityservice.AccessibilityService
import android.accessibilityservice.GestureDescription
import android.graphics.Path
import android.os.SystemClock
import android.view.accessibility.AccessibilityEvent
import android.view.accessibility.AccessibilityNodeInfo
import android.content.Intent
import com.google.gson.JsonObject

/**
 * Accessibility service that turns a viewer's input-channel messages into real
 * touch gestures on this device — this is what makes an Android host fully
 * controllable (AnyDesk-style). The user enables it once in system settings;
 * every injection happens only while a remote session is live.
 *
 * Handles: move/down/up (tap+drag), wheel (swipe scroll), b=2 (long-press
 * right-click), and printable keys (SET_TEXT into the focused input field).
 */
class RemoteControlService : AccessibilityService() {

    companion object {
        @Volatile var instance: RemoteControlService? = null
        // set when the host accepted the session as view-only — every input
        // message is dropped while this is on, reset on session end.
        @Volatile var blocked = false
        fun enabled() = instance != null

        /** Authoritative check — reads Settings.Secure so it's true the moment
            the user flips the toggle, even before the service binds. */
        fun enabledInSettings(ctx: android.content.Context): Boolean {
            val flat = android.provider.Settings.Secure.getString(
                ctx.contentResolver,
                android.provider.Settings.Secure.ENABLED_ACCESSIBILITY_SERVICES) ?: return false
            return flat.contains(ctx.packageName)
        }
    }

    override fun onServiceConnected() { instance = this }
    override fun onUnbind(i: Intent?): Boolean { instance = null; blocked = false; return false }
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
    private var skipUp = false
    private var scrW = 0
    private var scrH = 0

    /** Map a normalized (0..1) input message to a real gesture/action. */
    fun handleInput(m: JsonObject, scrW: Int, scrH: Int) {
        if (blocked) return
        val t = m.get("t").s() ?: return
        this.scrW = scrW; this.scrH = scrH
        val x = (m.get("x")?.asFloat ?: 0f) * scrW
        val y = (m.get("y")?.asFloat ?: 0f) * scrH
        when (t) {
            "down" -> onDown(x, y, m.get("b")?.asInt ?: 0)
            "move" -> onMove(x, y)
            "up" -> onUp(x, y)
            "wheel" -> onWheel(x, y, m.get("dy")?.asFloat ?: 0f)
            "key" -> onKey(m)
        }
    }

    private fun onDown(x: Float, y: Float, b: Int) {
        if (b == 2) { // right button → long-press (context menus)
            longPress(x, y)
            skipUp = true
            return
        }
        skipUp = false
        lastX = x; lastY = y; moved = false
        downAt = SystemClock.uptimeMillis()
        strokeT0 = downAt
        val p = Path().apply { moveTo(x, y) }
        strokeDesc = GestureDescription.StrokeDescription(p, 0, 10, true)
        dispatch(strokeDesc!!)
    }

    private fun onMove(x: Float, y: Float) {
        val sd = strokeDesc ?: return
        if (Math.abs(x - lastX) + Math.abs(y - lastY) < 3) return
        moved = true
        // throttle stroke continuation ~20ms so the gesture queue stays small
        val now = SystemClock.uptimeMillis()
        if (now - lastMoveAt < 20) return
        lastMoveAt = now
        val seg = Path().apply { moveTo(lastX, lastY); lineTo(x, y) }
        strokeDesc = sd.continueStroke(seg, now - strokeT0, 40, true)
        lastX = x; lastY = y
        dispatch(strokeDesc!!)
    }

    private fun onUp(x: Float, y: Float) {
        if (skipUp) { skipUp = false; return }
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

    /** Wheel → swipe. dy>0 (wheel down) scrolls content down = finger goes up. */
    private fun onWheel(x: Float, y: Float, dy: Float) {
        val d = (dy / 100f * 350f).coerceIn(-900f, 900f)
        if (d == 0f) return
        val y2 = (y - d).coerceIn(1f, scrH.toFloat() - 1f)
        val p = Path().apply { moveTo(x, y); lineTo(x, y2) }
        dispatch(GestureDescription.StrokeDescription(p, 0, 320))
    }

    private fun tap(x: Float, y: Float) {
        val p = Path().apply { moveTo(x, y) }
        dispatch(GestureDescription.StrokeDescription(p, 0, 60))
    }

    private fun longPress(x: Float, y: Float) {
        val p = Path().apply { moveTo(x, y) }
        dispatch(GestureDescription.StrokeDescription(p, 0, 650))
    }

    // dispatchGesture returns false when the gesture can't start (too many
    // pending) and the callback reports cancel — log once per failure burst
    // so "input stopped while minimized" is diagnosable instead of silent.
    private var failLogged = false

    /** viewer pressed "lock remote" — accessibility global action (API 28+). */
    fun lockScreen(): Boolean {
        val ok = if (android.os.Build.VERSION.SDK_INT >= 28)
            performGlobalAction(GLOBAL_ACTION_LOCK_SCREEN)
        else performGlobalAction(GLOBAL_ACTION_POWER_DIALOG)
        android.util.Log.d("BMyDesk", "remote lockScreen → $ok")
        return ok
    }

    private fun dispatch(sd: GestureDescription.StrokeDescription) {
        runCatching {
            val ok = dispatchGesture(GestureDescription.Builder().addStroke(sd).build(),
                object : GestureResultCallback() {
                    override fun onCancelled(g: GestureDescription?) {
                        if (!failLogged) { failLogged = true
                            android.util.Log.w("BMyDesk", "remote gesture CANCELLED (bg/OEM restriction?)") }
                    }
                    override fun onCompleted(g: GestureDescription?) { failLogged = false }
                }, null)
            if (!ok && !failLogged) { failLogged = true
                android.util.Log.w("BMyDesk", "dispatchGesture returned false") }
        }
    }

    // ── keys: global actions for nav keys, SET_TEXT for printable chars ──
    private var shiftHeld = false

    private fun onKey(m: JsonObject) {
        val code = m.get("code").s()
        // viewers send Shift as its own key event — track its held state so
        // KeyX can be resolved to the right case/symbol
        if (code == "ShiftLeft" || code == "ShiftRight") {
            shiftHeld = m.get("down")?.asBoolean == true
            return
        }
        if (m.get("down")?.asBoolean != true) return
        val k = m.get("k").s()
        if (!k.isNullOrEmpty()) { typeChar(k[0]); return }
        when (code) {
            "Escape" -> performGlobalAction(GLOBAL_ACTION_BACK)
            "Home" -> performGlobalAction(GLOBAL_ACTION_HOME)
            "End" -> performGlobalAction(GLOBAL_ACTION_RECENTS)
            "F4" -> performGlobalAction(GLOBAL_ACTION_BACK)
            "Backspace" -> delChar()
            "Enter" -> newlineOrGo()
            "PageDown" -> performGlobalAction(GLOBAL_ACTION_POWER_DIALOG)
            "F6" -> performGlobalAction(GLOBAL_ACTION_NOTIFICATIONS)
            "F7" -> performGlobalAction(GLOBAL_ACTION_QUICK_SETTINGS)
            else -> codeChar(code, (m.get("shift")?.asBoolean == true) || shiftHeld)
        }
    }

    private fun codeChar(code: String?, shift: Boolean) {
        val c = when {
            code != null && code.length == 4 && code.startsWith("Key") ->
                code[3].let { if (shift) it else it.lowercaseChar() }
            code != null && code.length == 6 && code.startsWith("Digit") ->
                if (shift) "!@#$%^&*()"[code[5] - '0'] else code[5]
            code == "Space" -> ' '
            code == "Period" -> if (shift) '>' else '.'
            code == "Comma" -> if (shift) '<' else ','
            code == "Minus" -> if (shift) '_' else '-'
            code == "Equal" -> if (shift) '+' else '='
            code == "Slash" -> if (shift) '?' else '/'
            code == "Backslash" -> if (shift) '|' else '\\'
            code == "Semicolon" -> if (shift) ':' else ';'
            code == "Quote" -> if (shift) '"' else '\''
            code == "BracketLeft" -> if (shift) '{' else '['
            code == "BracketRight" -> if (shift) '}' else ']'
            code == "Backquote" -> if (shift) '~' else '`'
            else -> return
        }
        typeChar(c)
    }

    /** SET_TEXT into whichever field has input focus — real typing, not paste. */
    private fun focusedInput(): AccessibilityNodeInfo? {
        val root = rootInActiveWindow ?: return null
        val n = root.findFocus(AccessibilityNodeInfo.FOCUS_INPUT)
        return if (n != null && n.isEditable) n else { n?.recycle(); null }
    }

    private fun setNodeText(n: AccessibilityNodeInfo, text: String) {
        val args = android.os.Bundle().apply {
            putCharSequence(AccessibilityNodeInfo.ACTION_ARGUMENT_SET_TEXT_CHARSEQUENCE, text)
        }
        n.performAction(AccessibilityNodeInfo.ACTION_SET_TEXT, args)
    }

    private fun typeChar(c: Char) {
        val n = focusedInput() ?: return
        runCatching { setNodeText(n, (n.text?.toString() ?: "") + c) }
        n.recycle()
    }

    private fun delChar() {
        val n = focusedInput() ?: return
        runCatching { setNodeText(n, (n.text?.toString() ?: "").dropLast(1)) }
        n.recycle()
    }

    private fun newlineOrGo() {
        val n = focusedInput()
        if (n != null && n.isMultiLine) {
            runCatching { setNodeText(n, (n.text?.toString() ?: "") + "\n") }
        }
        n?.recycle()
    }
}
