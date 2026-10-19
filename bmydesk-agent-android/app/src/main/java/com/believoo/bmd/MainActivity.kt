package com.believoo.bmd

import android.app.Activity
import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.content.ServiceConnection
import android.graphics.Color
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.media.projection.MediaProjectionManager
import android.os.Bundle
import android.os.IBinder
import android.view.Gravity
import android.view.View
import android.widget.Button
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.ScrollView
import android.widget.TextView
import com.google.gson.JsonObject
import kotlin.concurrent.thread

/**
 * BMyDesk Agent (Android) — shows an 8-char session code, listens for viewer
 * join requests, asks the host to accept, then streams the screen over WebRTC.
 * V1: view-only host (remote input needs an AccessibilityService — later phase).
 */
class MainActivity : Activity() {

    private val api = AgentApi()
    @Volatile private var reg: AgentApi.Registration? = null
    private var service: ScreenShareService? = null
    private var bound = false

    private lateinit var codeView: TextView
    private lateinit var statusView: TextView
    private lateinit var spinner: ProgressBar
    private lateinit var reqBox: LinearLayout
    private lateinit var reqName: TextView
    private lateinit var endBtn: Button

    private var pendingProjection = false
    private var waitingOfferSdp: String? = null
    private var pullRef: PullRefreshLayout? = null
    private lateinit var pinLink: TextView
    @Volatile private var pinSet = false
    @Volatile private var unattendedPrompted = false
    @Volatile private var unattendedConsent = false

    // Viewer mode (Remote Desk — connect to another code)
    private var remoteViewer: RemoteViewer? = null
    private var remoteSurface: org.webrtc.SurfaceViewRenderer? = null
    private lateinit var remoteCodeInput: android.widget.EditText
    private lateinit var remoteNoteInput: android.widget.EditText
    private lateinit var remotePinInput: android.widget.EditText
    private lateinit var viewerPane: android.widget.FrameLayout
    private lateinit var viewerStatus: TextView
    private lateinit var vProgress: ProgressBar
    @Volatile private var vProgPct = 0
    @Volatile private var vJoinedAtMs = 0L

    // Session tools (chat + file) — shared by host + viewer modes
    private lateinit var sessRow: LinearLayout
    private lateinit var chatPanel: LinearLayout
    private lateinit var chatLog: LinearLayout // one row per message (tick state needs a view each)
    private lateinit var chatScroll: ScrollView
    private lateinit var chatInput: android.widget.EditText
    private var chatCount = 0

    private val conn = object : ServiceConnection {
        override fun onServiceConnected(name: ComponentName?, binder: IBinder?) {
            service = (binder as ScreenShareService.LocalBinder).service
            bound = true
            service?.events = object : ScreenShareService.Events {
                override fun onConnected() = ui {
                    peerLive = true
                    teardownRunnable?.let { teardownHandler.removeCallbacks(it) }
                    setStatus("Viewer connected — sharing screen", "#22c55e")
                    sessRow.visibility = View.VISIBLE
                    // auto-minimize to the floating bubble — the controlled
                    // phone shows its real screen, not our UI; session stays
                    // live via the foreground service + accessibility service
                    if (getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getBoolean("auto_min_host", true)) {
                        sessTicker.postDelayed({
                            if (peerLive && service != null && !isFinishing && !isDestroyed)
                                minimizeViewer()
                        }, 1200)
                    }
                }
                override fun onDisconnected() = ui {
                    peerLive = false
                    setStatus("Viewer disconnected", "#f59e0b")
                    sessRow.visibility = View.GONE; chatPanel.visibility = View.GONE
                    // grace window for the viewer's auto-reconnect — after that
                    // the capture is torn down so a dead viewer can't leave
                    // MediaProjection recording forever.
                    teardownRunnable?.let { teardownHandler.removeCallbacks(it) }
                    val r = Runnable { if (!peerLive && service != null) endSession() }
                    teardownRunnable = r
                    teardownHandler.postDelayed(r, 30_000)
                }
                override fun onError(msg: String) = ui { setStatus(msg, "#ef4444") }
                override fun onCtlReady() = ui {
                    sessRow.visibility = View.VISIBLE; startClipSync()
                    // host accepted with "view only" — lock input injection and
                    // tell the viewer so their UI can reflect it too.
                    if (pendingViewOnly) {
                        pendingViewOnly = false
                        setViewOnlyLive(true)
                        chatAppend("System", "View-only session — remote control is locked", false)
                    }
                    // without the accessibility service the viewer can only
                    // WATCH — surface that clearly on both ends
                    if (!RemoteControlService.enabled()) {
                        setStatus("⚠ Remote control OFF — enable Accessibility for input", "#f59e0b")
                        chatAppend("System", "Viewer can't control: enable BMyDesk in Settings → Accessibility. " +
                            "Tap the bubble → 🔓 to unlock after enabling.", false)
                        service?.ctl()?.sendChat("system", "⚠ Host hasn't enabled remote-control (Accessibility off) — you can only watch. Ask them to enable BMyDesk in Settings → Accessibility.")
                    }
                }
                override fun onChat(from: String, text: String) = ui { chatAppend(from, text, false) }
                override fun onChatMsg(id: String, from: String, text: String) = chatIncoming(id, from, text)
                override fun onChatAck(id: String) = ui { chatTickRows[id]?.let { it.text = it.tag.toString() + "  ✓✓" } }
                override fun onChatSeen(id: String) = ui { chatTickRows[id]?.let { it.text = it.tag.toString() + "  ✓✓"; it.setTextColor(Color.parseColor("#38bdf8")) } }
                override fun onClip(text: String) = clipSet(text)
                override fun onFile(name: String, data: ByteArray) = confirmIncoming(name, data)
                override fun onCallState(kind: String, on: Boolean) = ui {
                    chatAppend("System", if (on) "Viewer started $kind" else "Viewer stopped $kind", false)
                }
                override fun onPipTrack(track: org.webrtc.VideoTrack) = showPip(track)
            }
            service?.signaling = signaling
            service?.setIce(iceServersFrom(reg?.iceServers))
            // flush an offer that arrived before binding finished
            waitingOfferSdp?.let { sdp -> waitingOfferSdp = null; handleOffer(sdp) }
        }
        override fun onServiceDisconnected(name: ComponentName?) { bound = false; service = null }
    }

    private val signaling = SignalingClient()
    @Volatile private var statusPollRunning = false
    // host-side teardown: viewer drop gets a reconnect grace window, then the
    // whole session is ended (capture + FGS) — no orphaned screen recording
    @Volatile private var peerLive = false
    private val teardownHandler = android.os.Handler(android.os.Looper.getMainLooper())
    private var teardownRunnable: Runnable? = null
    // nonce dedupe — signals arrive via ws AND the HTTP queue; the shared
    // "n" field collapses double delivery.
    private val seenNonces = java.util.concurrent.ConcurrentHashMap.newKeySet<String>()
    private fun sigNew(p: JsonObject): Boolean {
        val n = p.get("n").s() ?: return true
        if (!seenNonces.add(n)) return false
        if (seenNonces.size > 600) seenNonces.clear()
        return true
    }
    // queued signals carry a server timestamp — drop stale ones so leftovers
    // from a dead pairing can't kill a fresh session
    private fun sigFresh(p: JsonObject): Boolean {
        // join-request is exempt — the server only queues it at join() time,
        // and an FCM-woken host may legitimately answer it minutes later.
        if (p.get("kind").s() == "join-request") return true
        val at = p.get("at").s() ?: return true
        return runCatching {
            System.currentTimeMillis() - java.time.Instant.parse(at).toEpochMilli() < 45_000
        }.getOrDefault(true)
    }
    // set when the host answers a join request — polled requests older than
    // this are not re-shown (fixes the "accept loop").
    @Volatile private var respondedAtMs = 0L
    // Accept options — the host can lock input (view-only) or mark the viewer
    // trusted so future joins skip approval entirely.
    private lateinit var chkViewOnly: android.widget.CheckBox
    private lateinit var chkTrust: android.widget.CheckBox
    @Volatile private var pendingViewOnly = false
    @Volatile private var pendingTrust = false
    @Volatile private var pendingVDevice: String? = null
    @Volatile private var pendingVName: String? = null

    private fun resetAcceptOptions() {
        pendingViewOnly = false; pendingTrust = false
        pendingVDevice = null; pendingVName = null
        runCatching {
            val sp = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
            chkViewOnly.isChecked = sp.getBoolean("def_viewonly", true) // default: watch first, grant control deliberately
            chkTrust.isChecked = sp.getBoolean("def_trust", false)
        }
    }

    private fun handleHostSignal(p: JsonObject) {
        when (p.get("kind").s()) {
            // queued by the server on join() so relay-mode viewers still reach us
            "join-request" -> ui {
                val nm = p.get("name").s() ?: "Someone"
                val nt = p.get("note").s()
                resetAcceptOptions()
                pendingVDevice = p.get("vdevice").s(); pendingVName = nm
                reqName.text = "$nm wants to view this device" + (if (nt.isNullOrBlank()) "" else "\n“$nt”")
                reqBox.visibility = View.VISIBLE
            }
            "offer" -> {
                val sdp = p.get("sdp").asString
                if (pendingProjection) waitingOfferSdp = sdp
                else ui { handleOffer(sdp) }
            }
            "ice" -> p.getAsJsonObject("candidate")?.let { service?.addIce(it) }
            "end" -> ui { endSession() }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        // Crash → record it and auto-relaunch within ~1s. resume_code (kept in
        // prefs while a viewer session is live) puts the user right back where
        // they were — no dead dialog, no manual app restart.
        Thread.setDefaultUncaughtExceptionHandler { _, e ->
            android.util.Log.e("BMyDesk", "crash", e)
            runCatching { com.google.firebase.crashlytics.FirebaseCrashlytics.getInstance().recordException(e) }
            runCatching {
                getSharedPreferences("bmydesk", Context.MODE_PRIVATE).edit().putBoolean("crashed", true).apply()
                val relaunch = Intent(this, MainActivity::class.java)
                    .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK)
                val pi = android.app.PendingIntent.getActivity(this, 991, relaunch,
                    android.app.PendingIntent.FLAG_IMMUTABLE or android.app.PendingIntent.FLAG_CANCEL_CURRENT)
                (getSystemService(Context.ALARM_SERVICE) as android.app.AlarmManager)
                    .setAlarmClock(android.app.AlarmManager.AlarmClockInfo(System.currentTimeMillis() + 600, null), pi)
            }
            android.os.Process.killProcess(android.os.Process.myPid())
            kotlin.system.exitProcess(10)
        }
        super.onCreate(savedInstanceState)
        buildUi()
        SessionBubbleService.actionListener = { cmd -> onBubbleAction(cmd) }
        // Persistent agent — keeps the process (and so the ws/poll loop) alive
        // even after the user swipes the activity away, and re-surfaces join
        // requests as heads-up notifications.
        runCatching { startForegroundService(Intent(this, AgentService::class.java)) }
        // load libwebrtc natives off the UI thread so the first connect can't ANR
        thread { runCatching {
            org.webrtc.PeerConnectionFactory.initialize(
                org.webrtc.PeerConnectionFactory.InitializationOptions.builder(applicationContext)
                    .setEnableInternalTracer(false).createInitializationOptions())
        } }
        // First launch asks for every runtime permission in one go (notifications,
        // camera, mic, location, files/media) — then never again.
        ensureRuntimePermissions()
        // Deep links (bmydesk://auth, bmydesk://connect/<code>, shared
        // https invite links) run their own startup path; a normal launch
        // registers and rejoins whatever session a crash interrupted.
        if (!handleDeepLink(intent)) { register(); resumeAfterCrash() }
        checkUpdate()
        checkBatteryExemption()
    }

    override fun onResume() {
        foreground = true; super.onResume()
        if (getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getBoolean("keep_awake", false))
            window.addFlags(android.view.WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
        else window.clearFlags(android.view.WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
        // opened the app while a session sits in the bubble → bring it back
        if (viewerMinimized && (remoteViewer != null || vJoin != null || service != null)) restoreViewer()
        maybeConfirmDisconnect(intent)
    }
    override fun onPause() { foreground = false; super.onPause() }

    companion object {
        /** AgentService checks this to avoid pinging while the UI is up. */
        @Volatile var foreground = false
    }

    override fun onNewIntent(intent: Intent?) {
        super.onNewIntent(intent)
        setIntent(intent)
        if (intent?.getBooleanExtra("confirm_disconnect", false) == true) {
            maybeConfirmDisconnect(intent); return
        }
        if (intent?.getBooleanExtra("resume_viewer", false) == true &&
            (remoteViewer != null || vJoin != null || service != null)) {
            restoreViewer(); return
        }
        if (intent != null && handleDeepLink(intent)) return
    }

    /**
     * Deep links:
     *   bmydesk://auth?token=…&name=…        — sign-in handoff (web / Google OAuth)
     *   bmydesk://connect/<CODE>             — open the Remote Desk viewer on CODE
     *   bmydesk://view/<CODE>                — same
     *   https://bmydesk.believoo.com/remote/guest/<CODE> — shared invite link (App Link)
     * Returns true when the intent carried a deep link (it owns startup —
     * register() is called from inside where needed).
     */
    private fun handleDeepLink(i: Intent?): Boolean {
        val u = i?.data ?: return false
        if (u.scheme == "bmydesk" && u.host == "auth") {
            val token = u.getQueryParameter("token") ?: return false
            val name = u.getQueryParameter("name") ?: ""
            getSharedPreferences("bmydesk", Context.MODE_PRIVATE).edit()
                .putString("member_token", token).putString("member_name", name).apply()
            ui { setStatus("Signed in as $name", "#22c55e") }
            register()
            return true
        }
        val code: String? = when {
            u.scheme == "bmydesk" && u.host in listOf("connect", "view", "remote") ->
                u.pathSegments?.firstOrNull() ?: u.getQueryParameter("code")
            u.scheme == "https" && u.host == "bmydesk.believoo.com" -> {
                val segs = u.pathSegments ?: emptyList()
                if (segs.size >= 3 && segs[0] == "remote" && segs[1] == "guest") segs[2]
                else u.getQueryParameter("code")
            }
            else -> null
        }
        val clean = code?.uppercase()?.replace(Regex("[^A-Z0-9]"), "").orEmpty()
        if (clean.length < 6) return false
        remoteCodeInput.setText(clean)
        if (service == null) register() // keep our own host code alive too
        // auto-connect — a shared link means intent; don't make the user tap again
        viewerPane.postDelayed({ connectRemote(clean) }, 700)
        return true
    }

    // If the last run crashed mid-session, jump straight back into it —
    // "reload" restores the position instead of dumping the user on the home card.
    private fun resumeAfterCrash() {
        val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        if (!prefs.getBoolean("crashed", false)) return
        prefs.edit().remove("crashed").apply()
        val code = prefs.getString("resume_code", null) ?: return
        prefs.edit().remove("resume_code").apply()
        if (code.length < 6) return
        toast("Restarted — rejoining session")
        viewerPane.postDelayed({ remoteCodeInput.setText(code); connectRemote(code) }, 1200)
    }

    // First-run permission primer — explain once, then fire the system prompts
    // for every access the session tools can use. Skippable; asked only once.
    private fun ensureRuntimePermissions() {
        val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        if (prefs.getBoolean("perms_asked_v1", false)) return
        val wanted = mutableListOf(
            android.Manifest.permission.CAMERA,
            android.Manifest.permission.RECORD_AUDIO,
            android.Manifest.permission.ACCESS_FINE_LOCATION,
            android.Manifest.permission.ACCESS_COARSE_LOCATION,
        )
        if (android.os.Build.VERSION.SDK_INT >= 33) {
            wanted += android.Manifest.permission.POST_NOTIFICATIONS
            wanted += android.Manifest.permission.READ_MEDIA_IMAGES
            wanted += android.Manifest.permission.READ_MEDIA_VIDEO
            wanted += android.Manifest.permission.READ_MEDIA_AUDIO
        } else {
            wanted += android.Manifest.permission.READ_EXTERNAL_STORAGE
            if (android.os.Build.VERSION.SDK_INT <= 28) wanted += android.Manifest.permission.WRITE_EXTERNAL_STORAGE
        }
        val missing = wanted.filter {
            checkSelfPermission(it) != android.content.pm.PackageManager.PERMISSION_GRANTED
        }
        if (missing.isEmpty()) { prefs.edit().putBoolean("perms_asked_v1", true).apply(); return }
        val dlg = android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("Allow BMyDesk access")
            .setMessage("BMyDesk needs a few permissions so every tool works during a remote session:\n\n• Notifications — incoming connection alerts\n• Camera & microphone — stream/call tools\n• Location — device check-in\n• Files & media — send and receive files\n\nYou can change them anytime in Android Settings.")
            .setPositiveButton("Continue") { _, _ -> requestPermissions(missing.toTypedArray(), 1004) }
            .setNegativeButton("Skip", null)
            .create()
        dlg.setOnDismissListener { prefs.edit().putBoolean("perms_asked_v1", true).apply() }
        dlg.show()
    }

    // If a newer build exists on the server, offer to open the download page.
    private fun checkUpdate() {
        thread {
            val latest = api.latestVersion("android") ?: return@thread
            if (latest.first != BuildConfig.VERSION_NAME) ui {
                runCatching {
                    android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
                        .setTitle("Update available — v${latest.first}")
                        .setMessage("A newer BMyDesk Agent is available. Download the update now?")
                        .setPositiveButton("Download") { _, _ ->
                            startActivity(Intent(Intent.ACTION_VIEW, android.net.Uri.parse(latest.second)))
                        }
                        .setNegativeButton("Later", null)
                        .show()
                }
            }
        }
    }

    // Unattended access — let trusted viewers connect with code+PIN, no
    // approval needed. The dialog explains WHAT the pin does (with an example)
    // because a bare prompt left users setting pins they didn't understand.
    private fun promptUnattendedPin() {
        val code = reg?.code ?: return
        val tok = reg?.agentToken ?: return
        val input = android.widget.EditText(this).apply {
            hint = "4–12 digit PIN"
            inputType = android.text.InputType.TYPE_CLASS_NUMBER
        }
        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("Unattended access PIN" + (if (pinSet) " — currently ON" else ""))
            .setMessage(
                "A PIN lets someone connect to this device WITHOUT you tapping Accept — " +
                "they only need your code + PIN.\n\n" +
                "Example: set PIN 4821 on your office PC; from home you enter its code + 4821 " +
                "and connect instantly, even at 2 AM.\n\n" +
                "⚠ Anyone who knows the code + PIN gets in — treat it like a password. " +
                "The device still asks for one-tap screen-share consent (Android requirement).")
            .setView(input)
            .setPositiveButton("Save") { _, _ -> savePin(code, tok, input.text.toString().trim()) }
            .setNeutralButton("Remove PIN") { _, _ -> savePin(code, tok, "") }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun savePin(code: String, tok: String, pin: String) {
        if (pin.isNotBlank() && !pin.matches(Regex("^[0-9]{4,12}$"))) {
            toast("PIN must be 4–12 digits"); return
        }
        thread {
            val ok = api.setPin(code, tok, pin)
            if (ok) pinSet = pin.isNotBlank()
            ui {
                refreshPinLabel()
                setStatus(if (ok) (if (pin.isBlank()) "Unattended access OFF" else "Unattended access ON — share code + PIN")
                    else "PIN update failed", if (ok && pin.isNotBlank()) "#22c55e" else "#f59e0b")
            }
        }
    }

    private fun refreshPinLabel() {
        runCatching {
            pinLink.text = if (pinSet) "🔒 Unattended access PIN — ON ✓" else "🔒 Set unattended-access PIN"
            pinLink.setTextColor(Color.parseColor(if (pinSet) "#22c55e" else "#475569"))
        }
    }

    // PIN/trusted auto-accept already passed server-side — Android's only
    // remaining gate is the MediaProjection consent, so ask for it directly.
    // Without this prompt the viewer's offer parked forever (auto-accept
    // looked "connected" but nothing ever streamed).
    private fun promptUnattendedConsent(name: String) {
        if (unattendedPrompted) return
        unattendedPrompted = true
        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("Unattended connection")
            .setMessage("$name connected with your PIN / trusted access.\n\n" +
                "Android needs one tap to allow screen sharing — tap Allow to start the session.")
            .setPositiveButton("Allow") { _, _ ->
                unattendedPrompted = false
                unattendedConsent = true
                respondedAtMs = System.currentTimeMillis()
                pendingProjection = true
                setStatus("Requesting screen capture permission…", "#f59e0b")
                val mpm = getSystemService(Context.MEDIA_PROJECTION_SERVICE) as MediaProjectionManager
                startActivityForResult(mpm.createScreenCaptureIntent(), 1001)
            }
            .setNegativeButton("End session") { _, _ -> unattendedPrompted = false; endSession() }
            .setOnCancelListener { unattendedPrompted = false }
            .show()
    }

    // Viewer-side PIN hint — most users never need it; explain when they do.
    private fun showPinHelp() {
        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("What is the PIN?")
            .setMessage("Leave it EMPTY for normal connections — the host just taps Accept.\n\n" +
                "The PIN is only for UNATTENDED devices: the host sets a secret PIN on their " +
                "device so trusted people can connect anytime, without anyone being there to accept.\n\n" +
                "Example: your office PC has PIN 4821 → from home you type its code + 4821 and you're in instantly.")
            .setPositiveButton("Got it", null)
            .show()
    }

    // ── Settings centre ────────────────────────────────────────────
    private fun showSettings() {
        val sp = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        val dark = sp.getBoolean("dark", true)
        val fg = Color.parseColor(if (dark) "#e2e8f0" else "#0f172a")
        val sub = Color.parseColor(if (dark) "#94a3b8" else "#64748b")
        val bgc = Color.parseColor(if (dark) "#0b1220" else "#f1f5f9")
        val root = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(dp(18), dp(14), dp(18), dp(24)); setBackgroundColor(bgc) }
        fun head(t: String) = root.addView(TextView(this).apply { text = t.uppercase(); setTextColor(Color.parseColor("#fb7185")); textSize = 11f; setPadding(0, dp(18), 0, dp(4)); letterSpacing = 0.1f })
        fun sw(key: String, def: Boolean, title: String, desc: String) {
            val row = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER_VERTICAL; setPadding(0, dp(8), 0, dp(8)) }
            val tv = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
            tv.addView(TextView(this).apply { text = title; setTextColor(fg); textSize = 14f })
            tv.addView(TextView(this).apply { text = desc; setTextColor(sub); textSize = 11f })
            row.addView(tv, LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f))
            row.addView(android.widget.Switch(this).apply { isChecked = sp.getBoolean(key, def); setOnCheckedChangeListener { _, v -> sp.edit().putBoolean(key, v).apply() } })
            root.addView(row)
        }
        fun act(title: String, desc: String, onClick: () -> Unit) {
            val row = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(0, dp(10), 0, dp(10)); setOnClickListener { onClick() } }
            row.addView(TextView(this).apply { text = title; setTextColor(fg); textSize = 14f })
            row.addView(TextView(this).apply { text = desc; setTextColor(sub); textSize = 11f })
            root.addView(row)
        }
        root.addView(TextView(this).apply { text = "⚙ Settings"; setTextColor(fg); textSize = 20f; typeface = Typeface.DEFAULT_BOLD })
        head("Incoming requests (host)")
        sw("vibrate_req", true, "Vibrate on request", "Buzz when someone asks to connect")
        sw("def_viewonly", true, "Accept as view-only by default", "Viewer can watch but not touch — untick to default to full control")
        sw("def_trust", false, "Always trust by default", "Skip approval for known devices next time")
        sw("keep_awake", false, "Keep screen awake", "Prevent sleep while BMyDesk is open")
        head("When I connect to another device")
        sw("auto_landscape", true, "Auto-rotate for wide screens", "Landscape for PC desktops, portrait for phones")
        sw("clip_sync", true, "Clipboard sync", "Copy here, paste there (and back)")
        act("Default quality: " + qLabel(sp.getString("def_quality", "auto") ?: "auto"), "Tap to cycle Auto → HD → Full HD → SD") {
            val n = when (sp.getString("def_quality", "auto")) { "auto" -> "hd"; "hd" -> "fhd"; "fhd" -> "sd"; else -> "auto" }
            sp.edit().putString("def_quality", n).apply(); toast("Default quality: ${qLabel(n)}")
        }
        head("Permissions & reliability")
        act("Remote-control access", if (RemoteControlService.enabled()) "ON ✓ — viewers can control this device" else "OFF — viewers can only watch (tap to fix)") { showAccessibilityGuide() }
        act("Battery optimisation", "Keep BMyDesk reachable in background — tap to fix") { checkBatteryExemption(true) }
        act("Notifications", "Manage alert settings") {
            runCatching { startActivity(Intent(android.provider.Settings.ACTION_APP_NOTIFICATION_SETTINGS).putExtra(android.provider.Settings.EXTRA_APP_PACKAGE, packageName)) }
        }
        head("Device")
        act("Copy my session code", reg?.code ?: "not registered yet") { reg?.code?.let { clipSet(it); toast("Copied") } }
        act("Copy device ID", deviceId()) { clipSet(deviceId()); toast("Copied") }
        act("Check for updates", "Current version v${BuildConfig.VERSION_NAME}") { checkUpdate(); toast("Checking…") }
        act("Open web workspace", "bmydesk.believoo.com") { startActivity(Intent(Intent.ACTION_VIEW, android.net.Uri.parse("https://bmydesk.believoo.com"))) }
        act("Help & support", "Guides and contact") { startActivity(Intent(Intent.ACTION_VIEW, android.net.Uri.parse("https://bmydesk.believoo.com/help"))) }
        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_NoActionBar_Fullscreen)
            .setView(ScrollView(this).apply { setBackgroundColor(bgc); addView(root) })
            .setPositiveButton("Close", null).show()
    }

    // ── UI ─────────────────────────────────────────────────────────
    private fun dp(v: Int) = (v * resources.displayMetrics.density).toInt()

    // Brand button — rounded, flat, no Material gray. Replaces stock Button()
    // everywhere so the app looks designed, not default.
    private fun mbtn(label: String, color: Int, size: Float = 13f, onClick: () -> Unit): Button =
        Button(this).apply {
            text = label; textSize = size; isAllCaps = false
            setTextColor(Color.WHITE); stateListAnimator = null
            background = GradientDrawable().apply { setColor(color); cornerRadius = dp(14).toFloat() }
            minHeight = 0; minimumHeight = 0
            setPadding(dp(16), dp(8), dp(16), dp(8))
            setOnClickListener { onClick() }
        }

    private fun lpGap(v: Int = 10) = LinearLayout.LayoutParams(
        LinearLayout.LayoutParams.WRAP_CONTENT, LinearLayout.LayoutParams.WRAP_CONTENT
    ).apply { marginStart = dp(v) }

    private fun buildUi() {
        val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        val dark = prefs.getBoolean("dark", true)
        val bg = Color.parseColor(if (dark) "#0b1220" else "#f1f5f9")
        val panel = Color.parseColor(if (dark) "#0f172a" else "#ffffff")
        val rose = Color.parseColor(if (dark) "#fb7185" else "#e11d48")
        val green = Color.parseColor("#22c55e")
        val txtMain = Color.parseColor(if (dark) "#ffffff" else "#0f172a")
        val txtSub = Color.parseColor(if (dark) "#94a3b8" else "#64748b")
        val txtDim = Color.parseColor(if (dark) "#64748b" else "#94a3b8")

        val frame = android.widget.FrameLayout(this).apply { setBackgroundColor(bg) }
        rootFrame = frame
        val scroll = ScrollView(this)
        val col = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER_HORIZONTAL
            setPadding(48, 60, 48, 40)
        }
        scroll.addView(col)
        // pull down from the top = reload (re-register + reconnect realtime)
        val pull = PullRefreshLayout(this).apply { onRefresh = { refreshAll() } }
        pull.addView(scroll)
        pullRef = pull
        frame.addView(pull)

        // settings + theme toggle (top row)
        col.addView(LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL; gravity = Gravity.END
            addView(TextView(this@MainActivity).apply {
                text = "⚙ Settings"; setTextColor(rose); textSize = 12f; setPadding(0, 0, dp(18), 0)
                setOnClickListener { showSettings() }
            })
            addView(TextView(this@MainActivity).apply {
                text = if (dark) "☀ Light mode" else "☾ Dark mode"
                setTextColor(txtSub); textSize = 12f
                setOnClickListener { prefs.edit().putBoolean("dark", !dark).apply(); recreate() }
            })
        }, LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT))

        col.addView(TextView(this).apply {
            text = "BMyDesk Agent"
            setTextColor(txtMain); textSize = 22f; typeface = Typeface.DEFAULT_BOLD
            gravity = Gravity.CENTER
        })
        col.addView(TextView(this).apply {
            text = "Share your screen with a session code"
            setTextColor(txtSub); textSize = 12f; gravity = Gravity.CENTER
            setPadding(0, 6, 0, 30)
        })

        val card = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL; gravity = Gravity.CENTER
            setPadding(32, 40, 32, 40)
            background = GradientDrawable().apply { setColor(panel); cornerRadius = 36f }
        }
        card.addView(TextView(this).apply {
            text = "YOUR SESSION CODE"
            setTextColor(Color.parseColor("#64748b")); textSize = 11f; letterSpacing = 0.2f
        })
        codeView = TextView(this).apply {
            text = "…"
            setTextColor(rose); textSize = 42f; typeface = Typeface.MONOSPACE; letterSpacing = 0.15f
            setTextIsSelectable(true); setPadding(0, 16, 0, 16)
            // long-press = mint a fresh code (clears the device identity)
            setOnLongClickListener {
                getSharedPreferences("bmydesk", Context.MODE_PRIVATE).edit().remove("device_id").apply()
                reg = null
                ui { text = "…"; setStatus("Registering…", "#f59e0b") }
                register()
                true
            }
        }
        card.addView(codeView)
        spinner = ProgressBar(this)
        card.addView(spinner)
        statusView = TextView(this).apply {
            text = "Registering…"; setTextColor(Color.parseColor("#94a3b8")); textSize = 13f
            setPadding(0, 12, 0, 0)
        }
        card.addView(statusView)

        // Accept / reject
        reqBox = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL; visibility = View.GONE
            setPadding(0, 24, 0, 0)
        }
        reqName = TextView(this).apply {
            setTextColor(Color.parseColor("#fbbf24")); textSize = 14f; gravity = Gravity.CENTER
        }
        reqBox.addView(reqName)
        chkViewOnly = android.widget.CheckBox(this).apply {
            text = "View only — watch but can't control\n(unticked = full control — they can tap & type)"
            setTextColor(Color.parseColor("#cbd5e1")); textSize = 13f
        }
        chkTrust = android.widget.CheckBox(this).apply {
            text = "Always trust this device — skip approval next time"
            setTextColor(Color.parseColor("#cbd5e1")); textSize = 13f
        }
        reqBox.addView(chkViewOnly)
        reqBox.addView(chkTrust)
        val btnRow = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER }
        btnRow.addView(mbtn("✓ Accept", Color.parseColor("#16a34a")) { acceptRequest() })
        btnRow.addView(mbtn("✕ Reject", Color.parseColor("#475569")) { rejectRequest() }, lpGap())
        reqBox.addView(btnRow)
        card.addView(reqBox)
        col.addView(card)

        // ── Remote desk: enter a partner code to view + control them ──
        val remoteCard = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(32, 28, 32, 28)
            background = GradientDrawable().apply { setColor(panel); cornerRadius = 36f }
        }
        remoteCard.addView(TextView(this).apply {
            text = "REMOTE DESK"
            setTextColor(Color.parseColor("#64748b")); textSize = 11f; letterSpacing = 0.2f
        })
        remoteCard.addView(TextView(this).apply {
            text = "Enter a partner code to view & control"
            setTextColor(Color.parseColor("#94a3b8")); textSize = 12f; setPadding(0, 4, 0, 14)
        })
        val row = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL }
        val fieldBg = if (dark) Color.parseColor("#131c2e") else Color.parseColor("#f1f5f9")
        remoteCodeInput = android.widget.EditText(this).apply {
            hint = "ABC123XY"
            setTextColor(rose); textSize = 17f; typeface = Typeface.MONOSPACE
            setHintTextColor(Color.parseColor("#475569"))
            filters = arrayOf(android.text.InputFilter.AllCaps(), android.text.InputFilter.LengthFilter(8))
            inputType = android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_FLAG_NO_SUGGESTIONS
            imeOptions = android.view.inputmethod.EditorInfo.IME_ACTION_GO
            setOnEditorActionListener { _, _, _ -> connectRemote(); true }
            setPadding(20, 12, 20, 12)
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
            background = GradientDrawable().apply { setColor(fieldBg); cornerRadius = 16f }
        }
        row.addView(remoteCodeInput)
        row.addView(mbtn("Connect ➜", Color.parseColor("#e11d48")) { connectRemote() }, lpGap())
        remoteCard.addView(row)
        remoteNoteInput = android.widget.EditText(this).apply {
            hint = "Purpose (optional) — shown on their accept prompt"
            setTextColor(txtMain); textSize = 13f
            setHintTextColor(Color.parseColor("#475569"))
            filters = arrayOf(android.text.InputFilter.LengthFilter(100))
            inputType = android.text.InputType.TYPE_CLASS_TEXT
            setPadding(20, 10, 20, 10)
            layoutParams = LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT).apply { topMargin = 10 }
            background = GradientDrawable().apply { setColor(fieldBg); cornerRadius = 16f }
        }
        remoteCard.addView(remoteNoteInput)
        remotePinInput = android.widget.EditText(this).apply {
            hint = "PIN — only for unattended devices"
            setTextColor(rose); textSize = 13f; typeface = Typeface.MONOSPACE
            setHintTextColor(Color.parseColor("#475569"))
            filters = arrayOf(android.text.InputFilter.LengthFilter(12))
            inputType = android.text.InputType.TYPE_CLASS_NUMBER
            // the keyboard's ✓/→ submits — users hunt for a hidden Connect
            // button otherwise and think the PIN step is stuck
            imeOptions = android.view.inputmethod.EditorInfo.IME_ACTION_GO
            setOnEditorActionListener { _, _, _ -> connectRemote(); true }
            setPadding(20, 10, 20, 10)
            layoutParams = LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT).apply { topMargin = 10 }
            background = GradientDrawable().apply { setColor(fieldBg); cornerRadius = 16f }
        }
        remoteCard.addView(remotePinInput)
        remoteCard.addView(TextView(this).apply {
            text = "ⓘ PIN is only for unattended devices — tap to learn more"
            setTextColor(Color.parseColor("#475569")); textSize = 11f
            setPadding(4, 8, 4, 0)
            setOnClickListener { showPinHelp() }
        })
        col.addView(remoteCard)

        // ── Account: sign in with BMyDesk workspace credentials ──
        val acct = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(0, 20, 0, 0) }
        val memberName = prefs.getString("member_name", null)
        if (memberName != null) {
            acct.addView(TextView(this).apply {
                text = "✓ Signed in as $memberName — tap to sign out"
                setTextColor(green); textSize = 12f; gravity = Gravity.CENTER
                setOnClickListener { prefs.edit().remove("member_token").remove("member_name").apply(); recreate() }
            })
        } else {
            val emailIn = android.widget.EditText(this).apply {
                hint = "Workspace email"; setTextColor(txtMain); textSize = 13f
                setHintTextColor(txtDim); inputType = android.text.InputType.TYPE_TEXT_VARIATION_EMAIL_ADDRESS
                setPadding(20, 10, 20, 10)
                background = GradientDrawable().apply { setColor(if (dark) Color.parseColor("#131c2e") else Color.parseColor("#f1f5f9")); cornerRadius = 14f }
            }
            val passIn = android.widget.EditText(this).apply {
                hint = "Password"; setTextColor(txtMain); textSize = 13f
                setHintTextColor(txtDim); inputType = android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_VARIATION_PASSWORD
                setPadding(20, 10, 20, 10)
                background = GradientDrawable().apply { setColor(if (dark) Color.parseColor("#131c2e") else Color.parseColor("#f1f5f9")); cornerRadius = 14f }
            }
            val loginBtn = mbtn("Sign in", Color.parseColor("#e11d48")) {}
            val googleBtn = mbtn("Sign in with Google", Color.parseColor("#334155")) {
                startActivity(Intent(Intent.ACTION_VIEW, android.net.Uri.parse("https://bmydesk.believoo.com/auth/google?agent=1")))
            }
            acct.addView(googleBtn)
            acct.addView(emailIn); acct.addView(passIn); acct.addView(loginBtn)
            loginBtn.setOnClickListener {
                thread {
                    try {
                        val l = api.login(emailIn.text.toString().trim(), passIn.text.toString())
                        prefs.edit().putString("member_token", l.memberToken).putString("member_name", l.name).apply()
                        ui { recreate() }
                    } catch (e: Exception) {
                        ui { setStatus("Login failed: ${e.message}", "#ef4444") }
                    }
                }
            }
        }
        col.addView(acct)

        col.addView(TextView(this).apply {
            text = "Viewer enters this code at\nbmydesk.believoo.com → Remote → Connect"
            setTextColor(Color.parseColor("#64748b")); textSize = 12f; gravity = Gravity.CENTER
            setPadding(0, 24, 0, 0)
        })
        pinLink = TextView(this).apply {
            text = "🔒 Set unattended-access PIN"
            setTextColor(Color.parseColor("#475569")); textSize = 12f; gravity = Gravity.CENTER
            setPadding(0, 10, 0, 0)
            setOnClickListener { promptUnattendedPin() }
        }
        col.addView(pinLink)
        col.addView(TextView(this).apply {
            text = "⧉ Share invite link"
            setTextColor(Color.parseColor("#475569")); textSize = 12f; gravity = Gravity.CENTER
            setPadding(0, 10, 0, 0)
            setOnClickListener {
                val c = reg?.code ?: return@setOnClickListener
                startActivity(Intent.createChooser(Intent(Intent.ACTION_SEND).apply {
                    type = "text/plain"
                    putExtra(Intent.EXTRA_TEXT, "Connect to my device on BMyDesk: https://bmydesk.believoo.com/remote/guest/$c")
                }, "Share invite link"))
            }
        })

        endBtn = mbtn("End session", Color.parseColor("#dc2626")) { endSession() }
        endBtn.visibility = View.GONE
        col.addView(endBtn, LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.WRAP_CONTENT, LinearLayout.LayoutParams.WRAP_CONTENT
        ).apply { topMargin = dp(16) })

        // in-session tools: chat + file send (visible while a viewer is connected)
        sessRow = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER
            visibility = View.GONE; setPadding(0, 12, 0, 0)
        }
        sessRow.addView(mbtn("💬 Chat", Color.parseColor("#1e293b"), 12f) { toggleChat() })
        sessRow.addView(mbtn("📎 Send file", Color.parseColor("#1e293b"), 12f) { pickFile() }, lpGap())
        // mid-session control switch — accepted view-only but want to hand over
        // the keyboard? or took control back? one tap, no rejoin needed.
        viewOnlyLiveBtn = mbtn("👁 View only", Color.parseColor("#1e293b"), 12f) { toggleViewOnlyLive() }
        sessRow.addView(viewOnlyLiveBtn, lpGap())
        // in-call mic/camera — the viewer hears us / sees us Zoom-style
        hostMicBtn = mbtn("🎙", Color.parseColor("#1e293b"), 12f) { toggleCall(kind = "mic", viewer = false) }
        sessRow.addView(hostMicBtn, lpGap())
        hostCamBtn = mbtn("📹", Color.parseColor("#1e293b"), 12f) { toggleCall(kind = "cam", viewer = false) }
        sessRow.addView(hostCamBtn, lpGap())
        col.addView(sessRow)

        // ── Fullscreen viewer pane (remote screen + touch control) ──
        viewerPane = android.widget.FrameLayout(this).apply {
            setBackgroundColor(Color.BLACK); visibility = View.GONE
            layoutParams = LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.MATCH_PARENT)
        }
        remoteSurface = org.webrtc.SurfaceViewRenderer(this)
        viewerPane.addView(remoteSurface, android.widget.FrameLayout.LayoutParams(
            android.widget.FrameLayout.LayoutParams.MATCH_PARENT, android.widget.FrameLayout.LayoutParams.MATCH_PARENT))
        vBar = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER_VERTICAL
            setPadding(20, 10, 20, 10); setBackgroundColor(Color.parseColor("#b30f172a")) // translucent float over video
        }
        val vLeft = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
        viewerStatus = TextView(this).apply { text = "Connecting…"; setTextColor(Color.parseColor("#94a3b8")); textSize = 12f }
        vLeft.addView(viewerStatus)
        vProgress = ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal).apply {
            max = 100; progress = 0; isIndeterminate = false
        }
        vLeft.addView(vProgress, LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT).apply { topMargin = 6 })
        vBar.addView(vLeft, LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f))
        vSessRow = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL; visibility = View.GONE }
        vSessRow.addView(mbtn("💬", Color.parseColor("#1e293b"), 12f) { toggleChat() })
        vSessRow.addView(mbtn("📎", Color.parseColor("#1e293b"), 12f) { pickFile() }, lpGap(6))
        vSessRow.addView(mbtn("⌨", Color.parseColor("#1e293b"), 12f) { keysRow.visibility = if (keysRow.visibility == View.VISIBLE) View.GONE else View.VISIBLE }, lpGap(6))
        vSessRow.addView(mbtn("🖥", Color.parseColor("#1e293b"), 12f) { activeCtl()?.sendScreen() }, lpGap(6))
        vSessRow.addView(mbtn("📂", Color.parseColor("#1e293b"), 12f) { openFs() }, lpGap(6))
        vSessRow.addView(mbtn("⏻", Color.parseColor("#1e293b"), 12f) {
            android.app.AlertDialog.Builder(this@MainActivity)
                .setMessage("Reboot the REMOTE computer? The host user will be asked to confirm.")
                .setPositiveButton("Reboot") { _, _ -> activeCtl()?.sendReboot() }
                .setNegativeButton("Cancel", null).show()
        }, lpGap(6))
        // quality toggle — cycles Auto → SD → HD → FHD; on slow nets SD is smoother
        vQualityBtn = mbtn("Auto", Color.parseColor("#1e293b"), 12f) {
            vQuality = when (vQuality) { "auto" -> "sd"; "sd" -> "hd"; "hd" -> "fhd"; else -> "auto" }
            vQualityBtn.text = qLabel(vQuality)
            remoteViewer?.ctl?.sendQuality(vQuality)
        }
        vSessRow.addView(vQualityBtn, lpGap(6))
        // fit ↔ fill — a landscape remote letterboxes tiny on a portrait phone;
        // fill crops the edges and drag pans the visible window instead.
        vFitBtn = mbtn("⛶ Fill", Color.parseColor("#1e293b"), 12f) {
            fillMode = !fillMode
            vFitBtn.text = if (fillMode) "⛶ Fit" else "⛶ Fill"
            applyViewMode()
        }
        vSessRow.addView(vFitBtn, lpGap(6))
        // touchpad ↔ direct-touch — a cursor you drag around (precise on
        // desktop UIs) vs tap-exactly-where-you-touch
        vPadBtn = mbtn("🖱 Pad", Color.parseColor("#1e293b"), 12f) { setPadMode(!padMode) }
        vSessRow.addView(vPadBtn, lpGap(6))
        // voice/video call — Zoom-style, over the same peer connection
        vMicBtn = mbtn("🎙", Color.parseColor("#1e293b"), 12f) { toggleCall(kind = "mic", viewer = true) }
        vSessRow.addView(vMicBtn, lpGap(6))
        vCamBtn = mbtn("📹", Color.parseColor("#1e293b"), 12f) { toggleCall(kind = "cam", viewer = true) }
        vSessRow.addView(vCamBtn, lpGap(6))
        // pinch-zoom exists but is hard to discover — on-screen zoom controls
        vSessRow.addView(mbtn("🔍+", Color.parseColor("#1e293b"), 12f) { zoomStep(0.5f) }, lpGap(6))
        vSessRow.addView(mbtn("🔍−", Color.parseColor("#1e293b"), 12f) { zoomStep(-0.5f) }, lpGap(6))
        // session buttons overflow on narrow phones — scroll them so the
        // Disconnect button is ALWAYS visible at the right edge
        val vSessScroll = android.widget.HorizontalScrollView(this).apply {
            isHorizontalScrollBarEnabled = false
            addView(vSessRow)
        }
        // cap the strip so the Disconnect button can never be pushed off-screen
        vBar.addView(vSessScroll, LinearLayout.LayoutParams(
            (resources.displayMetrics.widthPixels * 0.40f).toInt(),
            LinearLayout.LayoutParams.WRAP_CONTENT))
        vBar.addView(mbtn("Disconnect", Color.parseColor("#dc2626"), 12f) { confirmExitViewer() }, lpGap(8))
        // collapse the bar into the floating button — remote goes full-screen
        vBar.addView(mbtn("▾", Color.parseColor("#334155"), 12f) { collapseVBar() }, lpGap(6))
        // touchpad cursor — a drawn pointer floating over the remote frame
        padCursor = object : View(this) {
            override fun onDraw(cv: android.graphics.Canvas) {
                val s = width / 24f
                val fill = android.graphics.Paint(android.graphics.Paint.ANTI_ALIAS_FLAG).apply {
                    color = Color.WHITE; style = android.graphics.Paint.Style.FILL }
                val edge = android.graphics.Paint(android.graphics.Paint.ANTI_ALIAS_FLAG).apply {
                    color = Color.parseColor("#0f172a"); style = android.graphics.Paint.Style.STROKE; strokeWidth = 2f }
                // classic arrow pointer (hotspot at the tip, top-left)
                val p = android.graphics.Path().apply {
                    moveTo(0f, 0f); lineTo(0f, 17f * s); lineTo(4.5f * s, 13.5f * s)
                    lineTo(7f * s, 20f * s); lineTo(9.5f * s, 19f * s); lineTo(7f * s, 12.5f * s)
                    lineTo(12f * s, 12.5f * s); close()
                }
                cv.drawPath(p, fill); cv.drawPath(p, edge)
            }
        }.apply {
            visibility = View.GONE
            layoutParams = android.widget.FrameLayout.LayoutParams(dp(26), dp(26))
        }
        viewerPane.addView(padCursor)
        viewerPane.addView(vBar, android.widget.FrameLayout.LayoutParams(
            android.widget.FrameLayout.LayoutParams.MATCH_PARENT, android.widget.FrameLayout.LayoutParams.WRAP_CONTENT,
            Gravity.TOP))
        // floating session button — the toolbar collapses into this so the
        // remote screen is edge-to-edge; tap to fan the controls back out,
        // they auto-collapse again after a few idle seconds
        viewerFab = ImageView(this).apply {
            setImageResource(R.mipmap.ic_launcher)
            background = GradientDrawable().apply {
                shape = GradientDrawable.OVAL
                setColor(Color.parseColor("#0f172a"))
                setStroke(dp(2), Color.parseColor("#e11d48"))
            }
            scaleType = ImageView.ScaleType.FIT_CENTER
            setPadding(dp(8), dp(8), dp(8), dp(8))
            elevation = 12f; visibility = View.GONE
            layoutParams = android.widget.FrameLayout.LayoutParams(
                dp(46), dp(46), Gravity.TOP or Gravity.END).apply {
                topMargin = dp(10); marginEnd = dp(10)
            }
        }
        run {
            var dx = 0f; var dy = 0f; var moved = false
            viewerFab.setOnTouchListener { v, ev ->
                val lp = v.layoutParams as android.widget.FrameLayout.LayoutParams
                when (ev.action) {
                    android.view.MotionEvent.ACTION_DOWN -> {
                        dx = ev.rawX; dy = ev.rawY; moved = false; true
                    }
                    android.view.MotionEvent.ACTION_MOVE -> {
                        if (Math.abs(ev.rawX - dx) + Math.abs(ev.rawY - dy) > 12) moved = true
                        if (moved) {
                            lp.topMargin = (lp.topMargin + (ev.rawY - dy)).toInt()
                                .coerceIn(0, viewerPane.height - v.height)
                            lp.marginEnd = (lp.marginEnd - (ev.rawX - dx)).toInt()
                                .coerceIn(0, viewerPane.width - v.width)
                            dx = ev.rawX; dy = ev.rawY
                            v.layoutParams = lp
                        }
                        true
                    }
                    android.view.MotionEvent.ACTION_UP -> {
                        if (!moved) expandVBar()
                        true
                    }
                    else -> false
                }
            }
        }
        viewerPane.addView(viewerFab)
        // special-keys row — sits under the viewer bar, toggled by ⌨
        keysRow = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL; visibility = View.GONE
            setBackgroundColor(Color.parseColor("#f00f172a")); setPadding(10, 4, 10, 4)
        }
        mapOf("Esc" to "Escape", "Tab" to "Tab", "Ctrl" to "ControlLeft", "Alt" to "AltLeft",
              "◀" to "ArrowLeft", "▲" to "ArrowUp", "▼" to "ArrowDown", "▶" to "ArrowRight",
              "Del" to "Delete", "Home" to "Home", "Win" to "MetaLeft").forEach { (lbl, code) ->
            keysRow.addView(mbtn(lbl, Color.parseColor("#1e293b"), 11f) { sendKey(code) }, lpGap(4))
        }
        keysRow.addView(mbtn("⌨", Color.parseColor("#1e293b"), 11f) { typeDialog() }, lpGap(4))
        keysRow.addView(mbtn("⌨Live", Color.parseColor("#334155"), 10f) { toggleLiveKb() }, lpGap(4))
        // shortcut chords — modifier flag travels on the key event itself
        mapOf("Alt+Tab" to ("Tab" to "alt"), "Alt+F4" to ("F4" to "alt"),
              "Ctrl+A" to ("KeyA" to "ctrl"), "Win+D" to ("KeyD" to "meta")).forEach { (lbl, p) ->
            keysRow.addView(mbtn(lbl, Color.parseColor("#334155"), 10f) { sendKeyMod(p.first, p.second) }, lpGap(4))
        }
        // keys row overflows phone width — wrap in a horizontal scroller
        val keysScroll = android.widget.HorizontalScrollView(this).apply {
            isHorizontalScrollBarEnabled = false
            addView(keysRow)
        }
        viewerPane.addView(keysScroll, android.widget.FrameLayout.LayoutParams(
            android.widget.FrameLayout.LayoutParams.MATCH_PARENT, android.widget.FrameLayout.LayoutParams.WRAP_CONTENT,
            Gravity.BOTTOM))
        frame.addView(viewerPane)

        // Session chat panel — overlays bottom of whichever screen is up
        chatPanel = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL; visibility = View.GONE
            setBackgroundColor(Color.parseColor("#f20f172a")); setPadding(16, 10, 16, 10)
        }
        chatScroll = ScrollView(this)
        chatLog = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
        chatScroll.addView(chatLog)
        chatPanel.addView(chatScroll, LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, 0, 1f))
        val chatRow = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL }
        chatInput = android.widget.EditText(this).apply {
            hint = "Message… or /cmd hostname"; setTextColor(Color.WHITE); textSize = 13f
            setHintTextColor(Color.parseColor("#64748b")); setPadding(14, 8, 14, 8)
            background = GradientDrawable().apply { setColor(Color.parseColor("#1e293b")); cornerRadius = 14f }
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
            setOnEditorActionListener { _, _, _ -> sendChat(); true }
        }
        chatRow.addView(chatInput)
        chatRow.addView(mbtn("Send", Color.parseColor("#e11d48"), 12f) { sendChat() }, lpGap(6))
        chatPanel.addView(chatRow)
        val chatH = (150 * resources.displayMetrics.density).toInt()
        frame.addView(chatPanel, android.widget.FrameLayout.LayoutParams(
            android.widget.FrameLayout.LayoutParams.MATCH_PARENT, chatH, Gravity.BOTTOM))

        col.addView(TextView(this).apply {
            text = "v" + BuildConfig.VERSION_NAME
            setTextColor(Color.parseColor("#475569")); textSize = 10f; gravity = Gravity.CENTER
            setPadding(0, 20, 0, 0)
        })

        setContentView(frame)
    }

    private lateinit var vBar: LinearLayout
    private lateinit var viewerFab: ImageView
    private var vBarHideRunnable: Runnable? = null
    private lateinit var vSessRow: LinearLayout
    private lateinit var keysRow: LinearLayout
    private lateinit var vQualityBtn: Button
    private lateinit var vFitBtn: Button
    private lateinit var vPadBtn: Button
    private lateinit var vMicBtn: Button
    private lateinit var vCamBtn: Button
    private lateinit var hostMicBtn: Button
    private lateinit var hostCamBtn: Button
    private var micOn = false; private var camOn = false
    private var pipView: org.webrtc.SurfaceViewRenderer? = null
    private var lastPipTrack: org.webrtc.VideoTrack? = null
    private var rootFrame: android.widget.FrameLayout? = null
    private var vQuality = "auto"
    private var fillMode = false
    private fun qLabel(q: String) = when (q) { "sd" -> "SD"; "hd" -> "HD"; "fhd" -> "FHD"; else -> "Auto" }

    /** Switch the surface between whole-screen fit and edge-cropped fill. */
    private fun applyViewMode() {
        vScale = 1f; panX = 0f; panY = 0f
        remoteSurface?.let {
            it.scaleX = 1f; it.scaleY = 1f; it.translationX = 0f; it.translationY = 0f
            it.setScalingType(if (fillMode)
                org.webrtc.RendererCommon.ScalingType.SCALE_ASPECT_FILL
                else org.webrtc.RendererCommon.ScalingType.SCALE_ASPECT_FIT)
        }
        updatePadCursor()
    }

    /** Button-driven zoom steps — same transform the pinch gesture applies. */
    private fun zoomStep(d: Float) {
        vScale = (vScale + d).coerceIn(1f, 5f)
        if (vScale <= 1.01f) { panX = 0f; panY = 0f } else clampPan()
        remoteSurface?.let {
            it.scaleX = vScale; it.scaleY = vScale
            it.translationX = panX; it.translationY = panY
        }
        updatePadCursor()
    }

    // ── Session tools: chat / file / clipboard (host + viewer share these) ──
    private fun activeCtl(): CtlChannel? =
        if (remoteViewer != null) remoteViewer?.ctl else service?.ctl()

    private fun toggleChat() {
        chatPanel.visibility = if (chatPanel.visibility == View.VISIBLE) View.GONE else View.VISIBLE
        chatCount = 0
        if (chatPanel.visibility == View.VISIBLE) flushChatSeen()
    }

    // ── Host-side control switch: view-only ↔ full control, mid-session ──
    private lateinit var viewOnlyLiveBtn: Button
    @Volatile private var viewOnlyLive = false

    private fun setViewOnlyLive(on: Boolean) {
        viewOnlyLive = on
        RemoteControlService.blocked = on
        SessionBubbleService.viewOnlyState = on
        service?.ctl()?.sendViewOnly(on)
        if (::viewOnlyLiveBtn.isInitialized) {
            viewOnlyLiveBtn.text = if (on) "✏️ Allow control" else "👁 View only"
            viewOnlyLiveBtn.background = GradientDrawable().apply {
                setColor(Color.parseColor(if (on) "#f43f5e" else "#1e293b"))
                cornerRadius = dp(14).toFloat()
            }
        }
    }
    private fun toggleViewOnlyLive() {
        setViewOnlyLive(!viewOnlyLive)
        chatAppend("System", if (viewOnlyLive) "Remote input blocked — viewer is view-only"
            else "Remote input enabled — viewer has full control", false)
    }

    // WhatsApp-style delivery/seen receipts — id → row so the tick can update
    private val chatTickRows = mutableMapOf<String, TextView>()
    private val unseenChatIds = mutableListOf<String>()
    private var chatSeq = 0

    private fun chatAppend(from: String, text: String, mine: Boolean, id: String? = null) {
        if (text.isBlank()) return
        val label = (if (mine) "You" else from) + ": " + text
        val row = TextView(this).apply {
            this.text = label + if (mine && id != null) "  ✓" else ""
            setTextColor(if (from == "System") Color.parseColor("#94a3b8") else Color.parseColor("#e2e8f0"))
            textSize = 13f
            if (mine && id != null) tag = label // base text — tick gets re-appended on ack/seen
        }
        chatLog.addView(row)
        if (mine && id != null) chatTickRows[id] = row
        chatScroll.post { chatScroll.fullScroll(View.FOCUS_DOWN) }
        if (chatPanel.visibility != View.VISIBLE) {
            chatCount++
            if (from != "System") toast("💬 $from: ${text.take(60)}")
        }
    }

    /** A chat msg arrived — show it; if the panel is open mark it seen now,
        else queue the receipt for when the user opens the chat. */
    private fun chatIncoming(id: String, from: String, text: String) {
        ui {
            chatAppend(from, text, false)
            if (chatPanel.visibility == View.VISIBLE) activeCtl()?.sendChatSeen(id)
            else unseenChatIds.add(id)
        }
    }

    private fun flushChatSeen() {
        unseenChatIds.forEach { activeCtl()?.sendChatSeen(it) }
        unseenChatIds.clear()
    }

    private fun sendChat() {
        val t = chatInput.text.toString().trim(); if (t.isEmpty()) return
        if (t.startsWith("/cmd ")) {
            val c = t.removePrefix("/cmd ").trim()
            if (c.isNotEmpty()) { activeCtl()?.sendCmd(c); chatAppend("You ▸", c, true) }
        } else {
            val me = getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getString("member_name", null)
                ?: (android.os.Build.MODEL ?: "Android")
            val id = "a" + (++chatSeq) + java.util.UUID.randomUUID().toString().take(6)
            activeCtl()?.sendChat(me, t, id)
            chatAppend("You", t, true, id)
        }
        chatInput.setText("")
    }

    private fun pickFile() {
        if (activeCtl()?.open() != true) { toast("No session — connect first"); return }
        startActivityForResult(Intent(Intent.ACTION_OPEN_DOCUMENT).apply {
            addCategory(Intent.CATEGORY_OPENABLE); type = "*/*"
        }, 1002)
    }

    // ── Remote file explorer — drives fs-list-req/fs-get over ctl ──
    private var fsDialog: android.app.AlertDialog? = null
    private var fsListBox: LinearLayout? = null
    private var fsPathView: TextView? = null
    private var fsPath = ""

    private fun openFs() {
        if (remoteViewer?.ctl?.open() != true) { toast("No session — connect first"); return }
        val wrap = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(28, 20, 28, 8)
        }
        fsPathView = TextView(this).apply {
            text = "…"; setTextColor(Color.parseColor("#7dd3fc")); textSize = 12f
        }
        fsListBox = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
        wrap.addView(fsPathView)
        wrap.addView(ScrollView(this).apply { addView(fsListBox) },
            LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT,
                (resources.displayMetrics.heightPixels * 0.55f).toInt()))
        fsDialog = android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("Remote files")
            .setView(wrap)
            .setNegativeButton("Close", null)
            .create()
        fsDialog?.setOnDismissListener { fsDialog = null; fsListBox = null; fsPathView = null }
        fsDialog?.show()
        fsPath = ""; fsReq()
    }

    private fun fsSep(): String = if (fsPath.contains('\\')) "\\" else "/"

    private fun fsReq() {
        fsPathView?.text = if (fsPath.isBlank()) "This device" else fsPath
        fsListBox?.removeAllViews()
        fsListBox?.addView(TextView(this).apply {
            text = "Loading…"; setTextColor(Color.parseColor("#94a3b8")); setPadding(0, 16, 0, 16)
        })
        remoteViewer?.ctl?.sendFsListReq(fsPath)
    }

    private fun fsParent(p: String): String {
        val trimmed = p.trimEnd('\\', '/')
        val sep = fsSep()
        val idx = trimmed.lastIndexOf(sep)
        return when {
            idx > 0 -> trimmed.substring(0, idx)
            p.startsWith("/") -> "/"
            else -> ""
        }
    }

    private fun fsRow(label: String, onTap: () -> Unit) {
        fsListBox?.addView(TextView(this).apply {
            text = label; setTextColor(Color.WHITE); textSize = 14f
            setPadding(12, 18, 12, 18)
            setOnClickListener { onTap() }
        })
    }

    private fun renderFsList(path: String, items: com.google.gson.JsonArray?, err: String?) {
        if (fsDialog == null) return
        fsPath = path
        fsPathView?.text = if (path.isBlank()) "This device" else path
        fsListBox?.removeAllViews()
        if (!err.isNullOrBlank()) {
            fsListBox?.addView(TextView(this).apply {
                text = err; setTextColor(Color.parseColor("#fca5a5")); setPadding(0, 16, 0, 16)
            })
            return
        }
        if (path.isNotBlank()) fsRow("⬅  ..") { fsPath = fsParent(fsPath); fsReq() }
        items?.forEach { el ->
            val it = el.asJsonObject
            val nm = it.get("n").s() ?: return@forEach
            val dir = it.get("d")?.asBoolean == true
            val sz = it.get("s")?.asLong ?: 0
            val label = (if (dir) "📁 " else "📄 ") + nm +
                (if (dir || sz <= 0) "" else "  · ${sz / 1024} KB")
            fsRow(label) {
                val full = if (fsPath.isBlank()) nm else fsPath.trimEnd('\\', '/') + fsSep() + nm
                if (dir) { fsPath = full; fsReq() }
                else {
                    chatAppend("System", "Fetching $nm…", false)
                    remoteViewer?.ctl?.sendFsGet(full)
                }
            }
        }
        if (fsListBox?.childCount == 0) {
            fsListBox?.addView(TextView(this).apply {
                text = "(empty)"; setTextColor(Color.parseColor("#64748b")); setPadding(0, 16, 0, 16)
            })
        }
    }

    // Ask first — a file arriving over a remote session should be confirmed
    // ("yes or no") before it lands in Downloads, not silently accepted.
    private fun confirmIncoming(name: String, data: ByteArray) {
        val safe = name.substringAfterLast('/').substringAfterLast('\\').ifBlank { "bmydesk-file" }
        ui {
            android.app.AlertDialog.Builder(this)
                .setTitle("Incoming file")
                .setMessage("$safe · ${data.size / 1024} KB\nSave it to Downloads?")
                .setPositiveButton("Save") { _, _ -> saveIncoming(safe, data) }
                .setNegativeButton("Decline") { _, _ -> chatAppend("System", "Declined file: $safe", false) }
                .show()
        }
    }

    // Received file → Downloads (MediaStore, no permission needed on API 29+;
    // app external dir on older). Viewer is also told via chat line.
    private fun saveIncoming(name: String, data: ByteArray) {
        thread { saveIncomingBg(name, data) }
    }

    private fun saveIncomingBg(name: String, data: ByteArray) {
        val safe = name.substringAfterLast('/').substringAfterLast('\\').ifBlank { "bmydesk-file" }
        try {
            if (android.os.Build.VERSION.SDK_INT >= 29) {
                val v = android.content.ContentValues().apply {
                    put(android.provider.MediaStore.Downloads.DISPLAY_NAME, safe)
                    put(android.provider.MediaStore.Downloads.IS_PENDING, 1)
                }
                val uri = contentResolver.insert(android.provider.MediaStore.Downloads.EXTERNAL_CONTENT_URI, v)
                contentResolver.openOutputStream(uri!!)!!.use { it.write(data) }
                v.clear(); v.put(android.provider.MediaStore.Downloads.IS_PENDING, 0)
                contentResolver.update(uri, v, null, null)
            } else {
                val dir = getExternalFilesDir(android.os.Environment.DIRECTORY_DOWNLOADS)!!
                java.io.File(dir, safe).writeBytes(data)
            }
            ui { chatAppend("System", "Received: $safe (${data.size / 1024} KB) → Downloads", false) }
        } catch (e: Exception) { ui { chatAppend("System", "File save failed: ${e.message}", false) } }
        ui { toast("📎 Received $safe") }
    }

    // Clipboard: incoming clip → write local. Outgoing poll (foreground only —
    // Android blocks background reads) → send on change.
    private fun clipSet(text: String) {
        if (text.isBlank()) return
        ui {
            runCatching {
                val cm = getSystemService(Context.CLIPBOARD_SERVICE) as android.content.ClipboardManager
                cm.setPrimaryClip(android.content.ClipData.newPlainText("bmydesk", text))
                lastClip = text
            }
            toast("📋 Clipboard synced")
        }
    }

    private var lastClip = ""
    private val clipHandler = android.os.Handler(android.os.Looper.getMainLooper())
    private val clipTick = object : Runnable {
        override fun run() {
            if (activeCtl()?.open() == true) {
                runCatching {
                    val cm = getSystemService(Context.CLIPBOARD_SERVICE) as android.content.ClipboardManager
                    val t = cm.primaryClip?.getItemAt(0)?.text?.toString()
                    if (!t.isNullOrEmpty() && t != lastClip && t.length < 100_000) {
                        lastClip = t
                        activeCtl()?.sendClip(t)
                    }
                }
                clipHandler.postDelayed(this, 2500)
            }
        }
    }
    private fun startClipSync() {
        if (!getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getBoolean("clip_sync", true)) return
        clipHandler.removeCallbacks(clipTick)
        lastClip = ""
        clipHandler.postDelayed(clipTick, 2500)
    }

    private fun toast(t: String) = android.widget.Toast.makeText(this, t, android.widget.Toast.LENGTH_SHORT).show()

    // taps land but do nothing while the host has the session in view-only —
    // say WHY (throttled) instead of looking dead
    private var lockToastAt = 0L
    private fun lockedHint() {
        val now = System.currentTimeMillis()
        if (now - lockToastAt < 4000) return
        lockToastAt = now
        toast("👁 View-only — the host locked control. Ask them to tap 🔓 in the floating menu.")
    }

    // ── Special keys + typing to the remote ──
    private fun sendKey(code: String) {
        if (vLocked) { lockedHint(); return }
        remoteViewer?.sendInput(JsonObject().apply { addProperty("t", "key"); addProperty("code", code); addProperty("down", true) })
        remoteViewer?.sendInput(JsonObject().apply { addProperty("t", "key"); addProperty("code", code); addProperty("down", false) })
    }

    // Stable per-device id — hosts can mark this phone "always trust"
    private fun viewerDeviceId(): String =
        android.provider.Settings.Secure.getString(contentResolver, android.provider.Settings.Secure.ANDROID_ID)
            ?.let { "and-$it" } ?: ""

    // key with a held modifier — Alt+Tab, Ctrl+A, Win+D etc.
    private fun sendKeyMod(code: String, mod: String) {
        for (down in listOf(true, false)) remoteViewer?.sendInput(JsonObject().apply {
            addProperty("t", "key"); addProperty("code", code); addProperty("down", down); addProperty(mod, true)
        })
    }

    // simple char→DOM-code map; shift-aware for capitals + common symbols
    private fun charCode(c: Char): Pair<String, Boolean>? = when {
        c in 'a'..'z' -> "Key" + c.uppercaseChar() to false
        c in 'A'..'Z' -> "Key$c" to true
        c in '0'..'9' -> "Digit$c" to false
        c == ' ' -> "Space" to false
        c == '\n' -> "Enter" to false
        c == '.' -> "Period" to false; c == ',' -> "Comma" to false
        c == '-' -> "Minus" to false; c == '=' -> "Equal" to false
        c == '/' -> "Slash" to false; c == '\\' -> "Backslash" to false
        c == '!' -> "Digit1" to true;  c == '@' -> "Digit2" to true
        c == '#' -> "Digit3" to true;  c == '$' -> "Digit4" to true
        c == '%' -> "Digit5" to true;  c == '^' -> "Digit6" to true
        c == '&' -> "Digit7" to true;  c == '*' -> "Digit8" to true
        c == '(' -> "Digit9" to true;  c == ')' -> "Digit0" to true
        else -> null
    }
    private fun typeText(t: String) {
        for (c in t) {
            if (c == '\b') { sendKey("Backspace"); continue }
            val m = charCode(c) ?: continue
            // 'k' carries the literal char — desktop hosts type() it directly
            // (no code→keymap ambiguity), the Android host SET_TEXTs it; the
            // DOM code + shift flag stay as fallback for older hosts.
            for (down in listOf(true, false)) remoteViewer?.sendInput(JsonObject().apply {
                addProperty("t", "key"); addProperty("code", m.first)
                addProperty("k", c.toString()); addProperty("down", down)
                if (m.second) addProperty("shift", true)
            })
        }
    }

    private fun typeDialog() {
        val input = android.widget.EditText(this).apply { hint = "Type to remote…" }
        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("Send text")
            .setView(input)
            .setPositiveButton("Send") { _, _ -> typeText(input.text.toString()) }
            .setNegativeButton("Cancel", null)
            .show()
    }

    // ── Live soft keyboard: every keystroke streams to the remote ──
    // A thin EditText bar sits at the top of the viewer; its TextWatcher
    // diffs the text and sends chars (or Backspaces) in real time — the
    // same UX as AnyDesk's "show keyboard" on mobile.
    private var liveKb: android.widget.EditText? = null
    private var liveKbLen = 0

    private fun toggleLiveKb() {
        if (liveKb != null) { closeLiveKb(); return }
        if (remoteViewer == null) { toast("No session"); return }
        val et = android.widget.EditText(this).apply {
            inputType = android.text.InputType.TYPE_CLASS_TEXT or
                android.text.InputType.TYPE_TEXT_FLAG_MULTI_LINE or
                android.text.InputType.TYPE_TEXT_FLAG_NO_SUGGESTIONS
            hint = "⌨ Live — typing goes to remote"
            setHintTextColor(Color.parseColor("#64748b"))
            setTextColor(Color.WHITE); textSize = 13f
            setBackgroundColor(Color.parseColor("#e60f172a"))
            setPadding(24, 12, 24, 12)
        }
        viewerPane.addView(et, android.widget.FrameLayout.LayoutParams(
            android.widget.FrameLayout.LayoutParams.MATCH_PARENT,
            android.widget.FrameLayout.LayoutParams.WRAP_CONTENT, Gravity.TOP))
        liveKbLen = 0
        et.addTextChangedListener(object : android.text.TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, st: Int, c: Int, a: Int) {}
            override fun onTextChanged(s: CharSequence?, st: Int, b: Int, c: Int) {}
            override fun afterTextChanged(s: android.text.Editable?) {
                val t = s?.toString() ?: ""
                if (t.length < liveKbLen) {
                    repeat(liveKbLen - t.length) { sendKey("Backspace") }
                } else if (t.length > liveKbLen) {
                    typeText(t.substring(liveKbLen))
                }
                liveKbLen = t.length
            }
        })
        et.requestFocus()
        (getSystemService(Context.INPUT_METHOD_SERVICE) as android.view.inputmethod.InputMethodManager)
            .showSoftInput(et, android.view.inputmethod.InputMethodManager.SHOW_IMPLICIT)
        liveKb = et
    }

    private fun closeLiveKb() {
        liveKb?.let {
            (getSystemService(Context.INPUT_METHOD_SERVICE) as android.view.inputmethod.InputMethodManager)
                .hideSoftInputFromWindow(it.windowToken, 0)
            viewerPane.removeView(it)
        }
        liveKb = null; liveKbLen = 0
    }

    // Ask to enable the accessibility service that injects viewer input —
    // re-asks every 24h while it's still off, because a dismissed prompt
    // used to leave every session permanently view-only with no way back.
    private fun promptAccessibilityOnce() {
        if (RemoteControlService.enabled()) return
        val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        val last = prefs.getLong("access_prompted_at", 0L)
        if (System.currentTimeMillis() - last < 24 * 3600_000L) return
        prefs.edit().putLong("access_prompted_at", System.currentTimeMillis()).apply()
        ui { showAccessibilityGuide() }
    }

    // The toggle is buried deep in settings — show exactly where "BMyDesk
    // Agent" lives instead of dumping users into a huge list with no context.
    private fun showAccessibilityGuide() {
        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("Enable remote control — 4 steps")
            .setMessage(
                "Remote control lets a viewer tap, type and scroll on this device.\n\n" +
                "1. Tap \"Open settings\" below\n" +
                "2. Find \"BMyDesk Agent\" in the list (under Downloaded/Installed services)\n" +
                "3. Switch it ON\n" +
                "4. Tap \"Allow\" on the confirmation dialog\n\n" +
                "Example path: Settings → Accessibility → Downloaded apps → BMyDesk Agent → ON\n\n" +
                "Without it, sessions stay view-only.")
            .setPositiveButton("Open settings") { _, _ ->
                runCatching { startActivity(Intent(android.provider.Settings.ACTION_ACCESSIBILITY_SETTINGS)) }
            }
            .setNegativeButton("Later", null)
            .show()
    }

    // Battery optimization kills background agents — keep prompting (once a
    // day) until the exemption is actually ON, then never again. On Xiaomi/
    // Oppo/Vivo/etc. the stock toggle isn't enough — those phones also need
    // the OEM "autostart/background" switch, so we surface it when present.
    private fun checkBatteryExemption(fromSettings: Boolean = false) {
        val pm = getSystemService(Context.POWER_SERVICE) as android.os.PowerManager
        val exempt = android.os.Build.VERSION.SDK_INT < 23 ||
            runCatching { pm.isIgnoringBatteryOptimizations(packageName) }.getOrDefault(false)
        if (exempt) { if (fromSettings) toast("Battery exemption already ON ✓"); return }
        val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        if (!fromSettings && System.currentTimeMillis() - prefs.getLong("batt_prompt_at", 0L) < 24 * 3600_000L) return
        prefs.edit().putLong("batt_prompt_at", System.currentTimeMillis()).apply()
        ui {
            val dlg = android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
                .setTitle("Keep BMyDesk reachable in background")
                .setMessage("Battery optimization can pause the agent — your code then looks offline and unattended access stops working.\n\n" +
                    "Step 1: tap \"Exempt now\" and choose Allow on the system dialog.\n" +
                    (if (oemAutostartIntent() != null) "Step 2: this phone also needs \"Autostart\" enabled — tap \"Autostart settings\".\n" else "") +
                    "\nTakes ~10 seconds.")
                .setPositiveButton("Exempt now") { _, _ ->
                    runCatching { startActivity(Intent(android.provider.Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS, android.net.Uri.parse("package:$packageName"))) }
                        .onFailure { runCatching { startActivity(Intent(android.provider.Settings.ACTION_IGNORE_BATTERY_OPTIMIZATION_SETTINGS)) } }
                }
                .setNegativeButton("Later", null)
            if (oemAutostartIntent() != null) dlg.setNeutralButton("Autostart settings") { _, _ -> openOemAutostart() }
            dlg.show()
        }
    }

    // OEM-specific autostart/background screens (stock Android has no such
    // thing). First resolvable intent wins; null = nothing extra to do.
    private fun oemAutostartIntent(): Intent? {
        val tries = listOf(
            // Xiaomi / MIUI — the biggest offender for killed background apps
            Intent().setComponent(ComponentName("com.miui.securitycenter", "com.miui.permcenter.autostart.AutoStartManagementActivity")),
            // Oppo / Realme (ColorOS)
            Intent().setComponent(ComponentName("com.coloros.safecenter", "com.coloros.safecenter.permission.startup.StartupAppListActivity")),
            Intent().setComponent(ComponentName("com.coloros.safecenter", "com.coloros.safecenter.startupapp.StartupAppListActivity")),
            // Vivo / iQOO
            Intent().setComponent(ComponentName("com.vivo.permissionmanager", "com.vivo.permissionmanager.activity.BgStartUpManagerActivity")),
            Intent().setComponent(ComponentName("com.iqoo.secure", "com.iqoo.secure.ui.phoneoptimize.AddWhiteListActivity")),
            // Huawei / Honor
            Intent().setComponent(ComponentName("com.huawei.systemmanager", "com.huawei.systemmanager.startupmgr.ui.StartupNormalAppListActivity")),
            Intent().setComponent(ComponentName("com.huawei.systemmanager", "com.huawei.systemmanager.appcontrol.activity.StartupAppControlActivity")),
            // OnePlus
            Intent().setComponent(ComponentName("com.oneplus.security", "com.oneplus.security.chainlaunch.view.ChainLaunchAppListActivity")),
            // Samsung (sleeping-apps battery screen)
            Intent().setComponent(ComponentName("com.samsung.android.lool", "com.samsung.android.sm.ui.battery.BatteryActivity")),
            // Asus
            Intent().setComponent(ComponentName("com.asus.mobilemanager", "com.asus.mobilemanager.autostart.AutoStartActivity")),
        )
        return tries.firstOrNull { runCatching { it.resolveActivity(packageManager) != null }.getOrDefault(false) }
    }
    private fun openOemAutostart() {
        val i = oemAutostartIntent()
        if (i != null) runCatching { startActivity(i) }
        else runCatching { startActivity(Intent(android.provider.Settings.ACTION_APPLICATION_DETAILS_SETTINGS, android.net.Uri.parse("package:$packageName"))) }
    }

    private fun ui(block: () -> Unit) = runOnUiThread(block)

    // Pull-to-refresh target — a soft "reload": fresh registration (keeps the
    // permanent code), ws reconnect if down, queued signals drained. Never
    // tears down a live host session — a re-register would reset it server-side.
    private fun refreshAll() {
        ui { setStatus("Refreshing…", "#f59e0b") }
        thread {
            try {
                val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
                if (service == null) {
                    val r = api.register(
                        android.os.Build.MODEL ?: "android-device", "android",
                        prefs.getString("member_token", null), deviceId(),
                        prefs.getString("fcm_token", null))
                    reg = r
                    prefs.edit().putString("session_code", r.code)
                        .putString("agent_token", r.agentToken).apply()
                }
                reg?.let { rr ->
                    if (!signaling.isConnected || !signaling.hostSubscribed)
                        runCatching { signaling.connect(rr.channel, rr.agentToken) }
                    api.drainSignals(rr.code, rr.agentToken)
                        .forEach { p -> if (sigNew(p) && sigFresh(p)) handleHostSignal(p) }
                }
                ui {
                    codeView.text = reg?.code ?: "…"
                    setStatus("Ready — share your code", "#22c55e")
                }
            } catch (e: Exception) {
                ui { setStatus("Refresh failed: ${e.message}", "#ef4444") }
            } finally {
                ui { pullRef?.stopRefresh() }
            }
        }
    }

    private fun setStatus(t: String, color: String = "#94a3b8") {
        statusView.text = t; statusView.setTextColor(Color.parseColor(color))
    }

    // ── Register + signaling ───────────────────────────────────────
    // Stable device identity — generated once, kept forever; the server maps
    // it to ONE permanent session code (AnyDesk-style). A new code only comes
    // from the user tapping the code → "New code".
    private fun deviceId(): String {
        val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        var id = prefs.getString("device_id", null)
        if (id == null) {
            id = java.util.UUID.randomUUID().toString()
            prefs.edit().putString("device_id", id).apply()
        }
        return id
    }

    private fun register() {
        thread {
            try {
                val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
                // show the permanent code instantly from cache — register() only
                // rotates the token; the code itself is stable per device_id.
                if (reg == null) prefs.getString("session_code", null)?.let { c ->
                    ui { if (codeView.text == "…" || codeView.text == "ERROR") codeView.text = c }
                }
                val r = api.register(
                    android.os.Build.MODEL ?: "android-device", "android",
                    prefs.getString("member_token", null),
                    deviceId(),
                    prefs.getString("fcm_token", null)
                )
                reg = r
                // AgentService reads these to heartbeat + surface requests
                // while the UI is closed.
                prefs.edit()
                    .putString("session_code", r.code).putString("agent_token", r.agentToken).apply()
                ui { codeView.text = r.code; setStatus("Connecting realtime…", "#f59e0b"); spinner.visibility = View.GONE }
                connectSignaling(r)
            } catch (e: Exception) {
                ui { codeView.text = "ERROR"; setStatus("Cannot reach server — retrying…", "#ef4444") }
                Thread.sleep(5000); register()
            }
        }
        // FCM token warm-up runs BESIDE register — awaiting it inline used to
        // add up to 8s to every cold start. When it rotates we re-register so
        // the server keeps a live push token (never while a session is live —
        // a re-register resets the session row server-side).
        thread {
            runCatching {
                val tok = com.google.android.gms.tasks.Tasks.await(
                    com.google.firebase.messaging.FirebaseMessaging.getInstance().token,
                    8, java.util.concurrent.TimeUnit.SECONDS
                )
                val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
                if (!tok.isNullOrBlank() && tok != prefs.getString("fcm_token", null) && service == null) {
                    prefs.edit().putString("fcm_token", tok).apply()
                    val r2 = api.register(
                        android.os.Build.MODEL ?: "android-device", "android",
                        prefs.getString("member_token", null), deviceId(), tok)
                    reg = r2
                    prefs.edit()
                        .putString("session_code", r2.code).putString("agent_token", r2.agentToken).apply()
                }
            }
        }
    }

    private fun connectSignaling(r: AgentApi.Registration) {
        signaling.listener = object : SignalingClient.Listener {
            override fun onConnected() { ui { setStatus("Realtime connected…", "#f59e0b") } }
            override fun onDisconnected() { ui { setStatus("Realtime lost — reconnecting…", "#ef4444") } }
            override fun onSubscribed() { ui { setStatus("Ready — share your code", "#22c55e") } }
            override fun onJoinRequest(name: String, note: String?, vdevice: String?) {
                if (getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getBoolean("vibrate_req", true))
                    runCatching {
                        @Suppress("DEPRECATION") (getSystemService(Context.VIBRATOR_SERVICE) as android.os.Vibrator).vibrate(400)
                    }
                ui {
                    resetAcceptOptions()
                    pendingVDevice = vdevice; pendingVName = name
                    reqName.text = "$name wants to view this device" + (if (note.isNullOrBlank()) "" else "\n“$note”")
                    reqBox.visibility = View.VISIBLE
                }
            }
            override fun onSignal(p: JsonObject) { if (sigNew(p)) handleHostSignal(p) }
            override fun onEnd() { ui { endSession() } }
        }
        signaling.relay = { payload -> reg?.let { rr -> thread { api.signal(rr.code, rr.agentToken, payload) } } }
        thread {
            try { signaling.connect(r.channel, r.agentToken) }
            catch (e: Throwable) { ui { setStatus("Realtime failed: ${e.message}", "#ef4444") } }
        }
        // Always-on fallback loop: drains the HTTP signal queue (covers a
        // peer whose ws is dead) and, while our own ws is unsubscribed,
        // polls /status so join requests still surface. Requests the host
        // already answered are never re-shown (respondedAtMs dedupe).
        // The ws watchdog resubscribes a dead socket — a dropped realtime
        // connection used to leave the device deaf until manual reopen.
        if (statusPollRunning) return
        statusPollRunning = true
        thread {
            Thread.sleep(4000)
            var wsDown = 0
            while (reg != null) {
                try {
                    val rr = reg ?: break
                    api.drainSignals(rr.code, rr.agentToken).forEach { p -> if (sigNew(p) && sigFresh(p)) handleHostSignal(p) }
                    // /status runs ALWAYS — an FCM-woken host may have a live ws
                    // yet still need this path to surface a request that was
                    // queued while the process was dead.
                    val s = api.status(rr.code, rr.agentToken)
                    if (s?.get("ok")?.asBoolean == true) {
                        val st = s.get("status").s()
                        val vj = s.get("viewer_joined_at").s()
                        val pset = s.get("pin_set")?.asBoolean == true
                        if (pset != pinSet) { pinSet = pset; ui { refreshPinLabel() } }
                        // show while the server says 'connecting' — no age
                        // cutoff (a >2min request still needs an answer)
                        if (st == "connecting" && vj != null) {
                            val joinMs = java.time.Instant.parse(vj).toEpochMilli()
                            if (joinMs > respondedAtMs && reqBox.visibility != View.VISIBLE) {
                                ui {
                                    reqName.text = "Someone wants to view this device"
                                    reqBox.visibility = View.VISIBLE
                                    setStatus("Connection request…", "#f59e0b")
                                }
                            }
                        } else if (st == "active" && service == null && !pendingProjection && vj != null) {
                            // Unattended join (PIN or trusted device) — the
                            // server already accepted; Android still needs the
                            // one-tap MediaProjection consent. Without this
                            // branch the viewer's offer parked forever — the
                            // "PIN doesn't work" bug.
                            val joinMs = runCatching { java.time.Instant.parse(vj).toEpochMilli() }.getOrDefault(0L)
                            if (joinMs > respondedAtMs && System.currentTimeMillis() - joinMs < 120_000
                                && reqBox.visibility != View.VISIBLE && !unattendedPrompted) {
                                ui { promptUnattendedConsent(s.get("viewer").s() ?: "A viewer") }
                            }
                        } else if (st != "connecting" && reqBox.visibility == View.VISIBLE && service == null) {
                            ui { reqBox.visibility = View.GONE }
                        }
                    }
                    // realtime watchdog — ~9s of dead socket triggers a
                    // reconnect+resubscribe with the CURRENT agent token.
                    if (!signaling.isConnected || !signaling.hostSubscribed) {
                        if (++wsDown >= 3) {
                            wsDown = 0
                            runCatching { signaling.connect(rr.channel, rr.agentToken) }
                        }
                    } else wsDown = 0
                } catch (e: Exception) {}
                Thread.sleep(3000)
            }
        }
    }

    // ── Accept flow: projection permission → service → capture ─────
    private fun acceptRequest() {
        reqBox.visibility = View.GONE
        respondedAtMs = System.currentTimeMillis()
        pendingViewOnly = chkViewOnly.isChecked
        pendingTrust = chkTrust.isChecked && !pendingVDevice.isNullOrBlank()
        pendingProjection = true
        setStatus("Requesting screen capture permission…", "#f59e0b")
        val mpm = getSystemService(Context.MEDIA_PROJECTION_SERVICE) as MediaProjectionManager
        startActivityForResult(mpm.createScreenCaptureIntent(), 1001)
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == 1002) { // file picked → send over ctl channel
            val uri = data?.data ?: return
            thread {
                try {
                    val name = runCatching {
                        contentResolver.query(uri, null, null, null, null)?.use { c ->
                            val i = c.getColumnIndex(android.provider.OpenableColumns.DISPLAY_NAME)
                            if (c.moveToFirst() && i >= 0) c.getString(i) else null
                        }
                    }.getOrNull() ?: "file"
                    val bytes = contentResolver.openInputStream(uri)?.use { it.readBytes() } ?: return@thread
                    if (bytes.size > 100 * 1024 * 1024) { ui { chatAppend("System", "File too large (max 100 MB)", false) }; return@thread }
                    ui { chatAppend("System", "Sending $name (${bytes.size / 1024} KB)…", false) }
                    activeCtl()?.sendFile(name, bytes)
                    ui { chatAppend("System", "Sent: $name", false) }
                } catch (e: Exception) { ui { chatAppend("System", "File send failed: ${e.message}", false) } }
            }
            return
        }
        if (requestCode != 1001) return
        if (resultCode != RESULT_OK || data == null) {
            pendingProjection = false
            // an auto-accepted (PIN/trusted) session can't be 'rejected' —
            // the server already made it active; deny = end it outright.
            if (unattendedConsent) { unattendedConsent = false; endSession() }
            else { respondTo("reject"); setStatus("Capture permission denied", "#ef4444") }
            return
        }
        // start FGS first (Android 14 requirement) then hand over the projection intent
        startService(Intent(this, ScreenShareService::class.java).setAction(ScreenShareService.ACTION_START))
        bindService(Intent(this, ScreenShareService::class.java), conn, Context.BIND_AUTO_CREATE)
        val svc = Intent(this, ScreenShareService::class.java)
            .setAction(ScreenShareService.ACTION_CAPTURE)
            .putExtra(ScreenShareService.EXTRA_RESULT_DATA, data)
        startService(svc)
        pendingProjection = false
        respondTo("accept")
        unattendedConsent = false
        if (pendingTrust) {
            val vd = pendingVDevice!!; val vn = pendingVName
            reg?.let { r -> thread { api.trust(r.code, r.agentToken, vd, vn) } }
            pendingTrust = false
        }
        setStatus("Accepted — waiting for stream…", "#22c55e")
        promptAccessibilityOnce()
        endBtn.visibility = View.VISIBLE
        waitingOfferSdp?.let { sdp -> waitingOfferSdp = null; handleOffer(sdp) }
    }

    private fun handleOffer(sdp: String) {
        if (service == null || service!!.peer() == null) {
            waitingOfferSdp = sdp  // capture not ready yet
            return
        }
        service!!.answerOffer(sdp)
        setStatus("Streaming…", "#22c55e")
    }

    /** accept/reject: ws whisper for speed AND HTTP POST (server broadcasts +
     *  queues it) — lands no matter whose socket is alive. */
    private fun respondTo(action: String) {
        respondedAtMs = System.currentTimeMillis()
        runCatching { signaling.send("join-$action", JsonObject()) }
        reg?.let { r -> thread { api.respond(r.code, r.agentToken, action) } }
    }

    private fun rejectRequest() {
        reqBox.visibility = View.GONE
        resetAcceptOptions()
        respondTo("reject")
        setStatus("Request rejected", "#f59e0b")
    }

    private fun endSession() {
        viewerMinimized = false
        runCatching { stopService(Intent(this, SessionBubbleService::class.java)) }
        setViewOnlyLive(false)
        RemoteControlService.blocked = false
        unattendedPrompted = false
        unattendedConsent = false
        resetAcceptOptions()
        stopCallMedia()
        reg?.let { thread { api.end(it.code, it.agentToken) } }
        signaling.send("end", JsonObject())
        runCatching { startService(Intent(this, ScreenShareService::class.java).setAction(ScreenShareService.ACTION_STOP)) }
        runCatching { if (bound) unbindService(conn); bound = false }
        // drop every per-session handle so the NEXT viewer starts clean
        // (a stale service/peer used to swallow the next offer until app restart)
        service = null; waitingOfferSdp = null; pendingProjection = false; peerLive = false
        teardownRunnable?.let { teardownHandler.removeCallbacks(it) }
        reqBox.visibility = View.GONE
        endBtn.visibility = View.GONE
        sessRow.visibility = View.GONE; chatPanel.visibility = View.GONE
        chatLog.removeAllViews(); chatTickRows.clear(); unseenChatIds.clear()
        setStatus("Ready — share your code", "#22c55e")
    }

    // ── Remote Desk (viewer): join another device's session ────────
    private var vJoin: AgentApi.JoinResult? = null
    @Volatile private var viewerPollRunning = false

    // Connection progress — the bar keeps moving through the join stages so
    // the client isn't staring at a dead "connecting…" label.
    private fun vStage(pct: Int, text: String) {
        vProgPct = maxOf(vProgPct, pct)
        ui {
            if (::vProgress.isInitialized) vProgress.progress = vProgPct
            viewerStatus.text = "$text — $vProgPct%"
        }
    }
    private fun vProgReset() {
        vProgPct = 0; vJoinedAtMs = 0
        ui { if (::vProgress.isInitialized) vProgress.progress = 0 }
    }

    private fun handleViewerSignal(j: AgentApi.JoinResult, p: JsonObject) {
        when (p.get("kind").s()) {
            "accept" -> { vStage(60, "Accepted — please wait, opening stream…"); ui { startViewerPeer(j) } }
            "reject" -> { ui { viewerStatus.text = "Host declined" }; thread { Thread.sleep(1500); ui { exitViewer() } } }
            "end" -> { ui { viewerStatus.text = "Host ended session" }; thread { Thread.sleep(1200); ui { exitViewer() } } }
            else -> remoteViewer?.onSignal(p)
        }
    }

    // HTTP fallback for the viewer: drains the queued signals + status so a
    // dead viewer ws still completes accept → offer → answer → stream.
    private fun startViewerPoll(j: AgentApi.JoinResult, code: String) {
        if (viewerPollRunning) return
        viewerPollRunning = true
        thread {
            while (vJoin != null) {
                try {
                    // keep the waiting state alive — elapsed seconds tell the
                    // client the host still has to press Accept.
                    if (vProgPct in 1 until 60 && vJoinedAtMs > 0) {
                        val waited = (System.currentTimeMillis() - vJoinedAtMs) / 1000
                        if (waited > 150) {
                            ui { viewerStatus.text = "Host didn't respond — try again later" }
                            thread { Thread.sleep(2000); ui { exitViewer() } }
                            break
                        }
                        vStage(vProgPct, "Waiting for host approval… ${waited}s" +
                            if (waited > 90) " — host may be away; their app must be open" else "")
                    }
                    api.drainSignals(code, j.viewerToken).forEach { p -> if (sigNew(p) && sigFresh(p)) handleViewerSignal(j, p) }
                    if (remoteViewer == null) {
                        val st = api.status(code, j.viewerToken)
                        val stv = st?.get("status").s()
                        if (st?.get("host_waking")?.asBoolean == true) {
                            ui { viewerStatus.text = "Waking the host's device…" }
                        }
                        else if (st?.get("host_alive")?.asBoolean == false) {
                            ui { viewerStatus.text = "Host went offline — ask them to reopen the app" }
                            thread { Thread.sleep(2500); ui { exitViewer() } }
                        }
                        // ws-dead fallback — the server flipped to active but
                        // the accept event never arrived; start streaming NOW
                        else if (stv == "active") {
                            vStage(60, "Accepted — please wait, opening stream…")
                            ui { startViewerPeer(j) }
                        }
                        else if (stv == "rejected") { ui { viewerStatus.text = "Host declined" }; thread { Thread.sleep(1500); ui { exitViewer() } } }
                        else if (stv == "ended") { ui { viewerStatus.text = "Host ended session" }; thread { Thread.sleep(1200); ui { exitViewer() } } }
                    }
                } catch (e: Exception) {}
                Thread.sleep(2000)
            }
            viewerPollRunning = false
        }
    }

    private fun connectRemote(codeOverride: String? = null) {
        val code = (codeOverride ?: remoteCodeInput.text.toString()).trim().uppercase()
        if (code.length < 6) { remoteCodeInput.requestFocus(); return }
        ui {
            viewerPane.visibility = View.VISIBLE
            enterImmersive()
            vProgReset()
            viewerStatus.text = "Joining $code…"
        }
        // joining your own code just hangs — the request prompt lands behind
        // the viewer pane; refuse it up front instead of "waiting" forever.
        if (code == reg?.code) {
            ui { viewerStatus.text = "That's your own code — enter a partner's" }
            thread { Thread.sleep(2000); ui { exitViewer() } }
            return
        }
        // NOTE: no ws-readiness gate — join + the whole signal flow also run
        // over the HTTP queue, so a dead socket must not block connecting.
        thread {
            try {
                val j = api.join(
                    code,
                    remotePinInput.text.toString().trim().ifBlank { null },
                    (android.os.Build.MODEL ?: "Android") + " (app)",
                    remoteNoteInput.text.toString().trim().ifBlank { null },
                    viewerDeviceId(),
                )
                vJoin = j
                // crash-restart resumes here — cleared in exitViewer()
                getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
                    .edit().putString("resume_code", code).apply()
                vJoinedAtMs = System.currentTimeMillis()
                vConnTries = 0
                vStage(15, "Joined — opening channel…")
                if (j.autoAccepted) { vStage(60, "Unattended access — starting stream…"); ui { startViewerPeer(j) } }
                signaling.viewerRelay = { payload -> thread { api.signal(code, j.viewerToken, payload) } }
                startViewerPoll(j, code)
                signaling.connectViewer(j.channel, j.viewerToken, object : SignalingClient.Listener {
                    override fun onConnected() {}
                    override fun onDisconnected() { vStage(30, "Signaling lost — relay mode active") }
                    override fun onJoinRequest(name: String, note: String?, vdevice: String?) {}
                    override fun onSubscribed() {
                        signaling.sendViewer("join-request", JsonObject().apply {
                            addProperty("name", (android.os.Build.MODEL ?: "Android") + " (app)")
                            addProperty("vdevice", viewerDeviceId())
                            remoteNoteInput.text.toString().trim().takeIf { it.isNotBlank() }?.let { addProperty("note", it) }
                        })
                        vStage(45, "Waiting for host approval…")
                    }
                    override fun onJoinAccept() { vStage(60, "Accepted — please wait, opening stream…"); ui { startViewerPeer(j) } }
                    override fun onJoinReject() { ui { viewerStatus.text = "Host declined" }; thread { Thread.sleep(1500); ui { exitViewer() } } }
                    override fun onSignal(p: JsonObject) { if (sigNew(p)) handleViewerSignal(j, p) }
                    override fun onEnd() { ui { viewerStatus.text = "Host ended session"; thread { Thread.sleep(1200); ui { exitViewer() } } } }
                })
            } catch (e: Exception) {
                val msg = e.message ?: "join failed"
                ui {
                    viewerStatus.text = "Join failed: $msg"
                    // loud + persistent — a 2s status flash was invisible, so a
                    // wrong PIN looked like "the connect button does nothing"
                    android.app.AlertDialog.Builder(this@MainActivity, android.R.style.Theme_Material_Dialog_Alert)
                        .setTitle("Could not connect")
                        .setMessage(when {
                            msg.contains("wrong PIN", true) -> "Wrong PIN — check the 4–12 digit PIN the host set, or leave PIN empty for a normal (accepted) connection."
                            msg.contains("too many", true) -> msg
                            else -> msg + "\n\nLeave the PIN field empty unless the host gave you one."
                        })
                        .setPositiveButton("Try again") { _, _ -> ui { exitViewer() } }
                        .setCancelable(false)
                        .show()
                }
            }
        }
    }

    // Auto-reconnect — session stays 'active' server-side, so no re-join and
    // no new host approval. Stale CLOSED events from the old peer instance are
    // ignored by the identity check.
    private var vConnTries = 0
    private var vLastPeerOkAt = 0L
    private var vRebuilding = false
    private fun viewerReconnect(v: RemoteViewer) {
        if (remoteViewer !== v || vJoin == null || vRebuilding) return
        vConnTries++
        // 4 tries ≈ ~30s — long enough for a WiFi↔data flap, short enough
        // that a dead host doesn't keep the viewer spinning for minutes
        if (vConnTries > 4) { ui { viewerStatus.text = "Connection lost — host app closed or network dropped" }; thread { Thread.sleep(2500); ui { exitViewer() } }; return }
        vRebuilding = true
        ui {
            viewerStatus.text = if (vJoin?.autoAccepted == true && vConnTries >= 3)
                // PIN joins "work" server-side but the remote Android still
                // needs its one-tap consent — say so instead of spinning forever
                "Waiting for host… (Android needs one tap on the remote device too — open BMyDesk there)"
            else "Reconnecting… (attempt $vConnTries)"
        }
        android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
            runCatching { remoteViewer?.stop() }
            remoteViewer = null
            vRebuilding = false
            vJoin?.let { startViewerPeer(it) }
        }, 1200)
    }

    private fun startViewerPeer(j: AgentApi.JoinResult) {
        if (remoteViewer != null) return // accept may arrive via ws + queue both
        vStage(72, "Negotiating secure link…")
        // the return-bubble needs "display over other apps" — ask once, never nag
        if (!android.provider.Settings.canDrawOverlays(this)
            && !getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getBoolean("overlay_asked", false)) {
            getSharedPreferences("bmydesk", Context.MODE_PRIVATE).edit().putBoolean("overlay_asked", true).apply()
            android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
                .setTitle("Floating session button")
                .setMessage("While a session runs, BMyDesk can show a small floating button on screen — tap it to jump back. Allow 'Display over other apps'? (You can still return via the notification without it.)")
                .setPositiveButton("Allow") { _, _ ->
                    runCatching { startActivity(Intent(android.provider.Settings.ACTION_MANAGE_OVERLAY_PERMISSION,
                        android.net.Uri.parse("package:$packageName"))) }
                }
                .setNegativeButton("Skip", null)
                .show()
        }
        remoteViewer = RemoteViewer(this).apply {
            val self = this
            listener = object : RemoteViewer.Listener {
                override fun sendSignal(payload: JsonObject) = signaling.sendViewer("signal", payload)
                override fun onPeerConnected() {
                    offerTimeout?.let { sessTicker.removeCallbacks(it) }
                    vLastPeerOkAt = System.currentTimeMillis(); vConnTries = 0; vStage(92, "Almost there…")
                }
                override fun onPeerUnstable() {
                    if (remoteViewer !== self) return
                    ui { streaming = false; viewerStatus.text = "Unstable — watching…" }
                    val droppedAt = vLastPeerOkAt
                    android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
                        if (remoteViewer === self && vJoin != null && vLastPeerOkAt == droppedAt) viewerReconnect(self)
                    }, 6000)
                }
                override fun onPeerDisconnected() { streaming = false; viewerReconnect(self) }
                override fun onPipTrack(track: org.webrtc.VideoTrack) { lastPipTrack = track; showPip(track) }
                override fun onTrack(track: org.webrtc.VideoTrack) = ui {
                    // Fresh renderer each session — a released
                    // SurfaceViewRenderer can't be init()ed again, which is
                    // what made every 2nd+ connect render a black screen.
                    remoteSurface?.let { old ->
                        viewerPane.removeView(old)
                        runCatching { old.release() }
                    }
                    remoteSurface = org.webrtc.SurfaceViewRenderer(this@MainActivity)
                    viewerPane.addView(remoteSurface, 0, android.widget.FrameLayout.LayoutParams(
                        android.widget.FrameLayout.LayoutParams.MATCH_PARENT,
                        android.widget.FrameLayout.LayoutParams.MATCH_PARENT))
                    remoteSurface!!.init(remoteViewer!!.egl.eglBaseContext, object : org.webrtc.RendererCommon.RendererEvents {
                        override fun onFirstFrameRendered() {}
                        override fun onFrameResolutionChanged(w: Int, h: Int, rot: Int) {
                            vidW = w; vidH = h; vidRot = rot
                            updatePadCursor()
                            // wide remote desktop on a portrait phone → rotate so the whole display fills the screen
                            if (getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getBoolean("auto_landscape", true)) ui { requestedOrientation = if (w > h) android.content.pm.ActivityInfo.SCREEN_ORIENTATION_SENSOR_LANDSCAPE
                                 else android.content.pm.ActivityInfo.SCREEN_ORIENTATION_UNSPECIFIED }
                        }
                    })
                    remoteSurface!!.setScalingType(if (fillMode)
                        org.webrtc.RendererCommon.ScalingType.SCALE_ASPECT_FILL
                        else org.webrtc.RendererCommon.ScalingType.SCALE_ASPECT_FIT)
                    track.addSink(remoteSurface)
                    bindViewerTouch()
                    lastPipTrack?.let { showPip(it) } // host camera was already on
                    // restore the touchpad preference from last session
                    if (::vPadBtn.isInitialized) {
                        val pm = getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getBoolean("pad_mode", false)
                        if (pm != padMode) setPadMode(pm) else updatePadCursor()
                    }
                    vStage(100, "Live — touch to control")
                    startSessTimer()
                    // once live, fold the toolbar into the floating button —
                    // the remote screen gets the whole display
                    vBarHideRunnable?.let { sessTicker.removeCallbacks(it) }
                    val hide = Runnable { collapseVBar() }
                    vBarHideRunnable = hide
                    sessTicker.postDelayed(hide, 3000)
                }
                override fun onError(message: String) = ui { viewerStatus.text = message }
            }
            ctl.sink = object : CtlChannel.Sink {
                override fun onCtlOpen() = ui {
                    vSessRow.visibility = View.VISIBLE; startClipSync()
                    if (vQuality == "auto") getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getString("def_quality", "auto")?.let { if (it != "auto") { vQuality = it; vQualityBtn.text = qLabel(it) } }
                    if (vQuality != "auto") remoteViewer?.ctl?.sendQuality(vQuality)

                }
                override fun onChat(from: String, text: String) = ui { chatAppend(from, text, false) }
                override fun onChatMsg(id: String, from: String, text: String) = chatIncoming(id, from, text)
                override fun onChatAck(id: String) = ui { chatTickRows[id]?.let { it.text = it.tag.toString() + "  ✓✓" } }
                override fun onChatSeen(id: String) = ui { chatTickRows[id]?.let { it.text = it.tag.toString() + "  ✓✓"; it.setTextColor(Color.parseColor("#38bdf8")) } }
                override fun onClip(text: String) = clipSet(text)
                override fun onFile(name: String, data: ByteArray) = confirmIncoming(name, data)
                override fun onCallState(kind: String, on: Boolean) = ui {
                    chatAppend("System", if (on) "Host started $kind" else "Host stopped $kind", false)
                }
                override fun onFileProgress(received: Int, total: Int) = ui {
                    if (total > 0) {
                        val pct = received * 100 / total
                        if (pct >= lastFilePct + 25 || (pct == 100 && lastFilePct < 100)) {
                            lastFilePct = pct
                            chatAppend("System", "Receiving file… $pct%", false)
                        }
                    }
                }
                override fun onFsList(path: String, items: com.google.gson.JsonArray?, err: String?) =
                    ui { renderFsList(path, items, err) }
                // host flipped us to watch-only mid-session — the status line
                // says so and local input stops being sent (host drops it anyway)
                override fun onViewOnly(on: Boolean) = ui { vLocked = on }
            }
        }
        remoteViewer!!.start(iceServersFrom(j.iceServers))
        // if the host never answers this offer (lost in dead-channel land),
        // rebuild the peer every 25s instead of hanging on 'Negotiating…'
        val v = remoteViewer!!
        offerTimeout?.let { sessTicker.removeCallbacks(it) }
        val rt = Runnable { if (remoteViewer === v && vJoin != null) viewerReconnect(v) }
        offerTimeout = rt
        sessTicker.postDelayed(rt, 25_000)
    }

    // Touch → input events (same JSON protocol as the web/Electron viewer)
    // + 2-finger pinch = local zoom, drag while zoomed = pan, double-tap resets.
    private var touchMoved = false
    private var dragging = false
    private var downX = 0f; private var downY = 0f
    private var lastMove = 0L
    private var vScale = 1f
    private var panX = 0f; private var panY = 0f
    private var lastTX = 0f; private var lastTY = 0f
    private var last2Y = Float.MIN_VALUE; private var lastWheel = 0L
    // session elapsed timer (mm:ss in the status line once live) + a timeout
    // that retries the WebRTC offer if the host never answers it
    private var liveSince = 0L
    private var offerTimeout: Runnable? = null
    private var lastFilePct = -1
    private val sessTicker = android.os.Handler(android.os.Looper.getMainLooper())

    @Volatile private var streaming = false
    @Volatile private var lastRttMs = 0
    @Volatile private var vLocked = false // host put this session in view-only
    private fun startSessTimer() {
        streaming = true
        if (liveSince != 0L) return
        liveSince = System.currentTimeMillis()
        sessTicker.postDelayed(object : Runnable {
            override fun run() {
                if (remoteViewer == null || liveSince == 0L) return
                // don't stomp reconnect/status messages while the link is down
                if (streaming) {
                    val s = (System.currentTimeMillis() - liveSince) / 1000
                    if (s % 3 == 0L) remoteViewer?.rtt { ms -> lastRttMs = ms }
                    viewerStatus.text = (if (vLocked) "👁 View-only — watch only · %d:%02d"
                        else "Live — touch to control · %d:%02d").format(s / 60, s % 60) +
                        (if (lastRttMs > 0) " · ${lastRttMs}ms" else "")
                }
                sessTicker.postDelayed(this, 1000)
            }
        }, 1000)
    }
    // remote frame dims — the video is letterboxed (aspect-fit) inside the
    // view, so raw view coords must be mapped through the visible video rect
    private var vidW = 0; private var vidH = 0; private var vidRot = 0

    /** Rendered frame size inside the view for the current scaling mode. */
    private fun renderRect(vw: Float, vh: Float): FloatArray? {
        val fw = if (vidRot == 90 || vidRot == 270) vidH.toFloat() else vidW.toFloat()
        val fh = if (vidRot == 90 || vidRot == 270) vidW.toFloat() else vidH.toFloat()
        if (fw <= 0f || fh <= 0f) return null
        val scale = if (fillMode) maxOf(vw / fw, vh / fh) else minOf(vw / fw, vh / fh)
        return floatArrayOf(fw * scale, fh * scale, (vw - fw * scale) / 2f, (vh - fh * scale) / 2f)
    }

    /** Map a touch point on the view → normalized coords inside the remote frame. */
    private fun mapTouch(vw: Float, vh: Float, x: Float, y: Float): Pair<Float, Float>? {
        val r = renderRect(vw, vh) ?: return null // no frame yet — fall back to view coords
        val nx = ((x - r[2]) / r[0]).coerceIn(0f, 1f)
        val ny = ((y - r[3]) / r[1]).coerceIn(0f, 1f)
        return Pair(nx, ny)
    }

    private fun sendMouse(t: String, x: Float, y: Float, b: Int = 0) {
        if (vLocked) { lockedHint(); return }
        val v = remoteSurface ?: return
        val p = mapTouch(v.width.toFloat(), v.height.toFloat(), x, y) ?: Pair(x / v.width, y / v.height)
        remoteViewer?.sendInput(JsonObject().apply {
            addProperty("t", t); addProperty("x", p.first); addProperty("y", p.second); addProperty("b", b)
        })
    }

    // ── Touchpad mode — phone screen acts like a laptop trackpad ──
    // Swiping moves a cursor over the remote screen (relative, precise — good
    // for tiny desktop targets); tap = click at cursor, two-finger tap = right
    // click, long-press+drag = remote drag. "👆 Touch" mode keeps the old
    // tap-exactly-where-you-touch behaviour.
    private var padMode = false
    private var padCursor: View? = null
    private var padCurX = 0.5f; private var padCurY = 0.5f // normalized in the REMOTE frame
    private var padDrag = false; private var padTwoTap = false
    private var padLastX = 0f; private var padLastY = 0f
    private var padMoveAt = 0L

    private fun sendPad(t: String, b: Int = 0) {
        if (vLocked) { lockedHint(); return }
        remoteViewer?.sendInput(JsonObject().apply {
            addProperty("t", t); addProperty("x", padCurX); addProperty("y", padCurY); addProperty("b", b)
        })
    }

    /** Draw the on-screen cursor at padCurX/Y — transformed like the video. */
    private fun updatePadCursor() {
        val c = padCursor ?: return
        val v = remoteSurface ?: run { c.visibility = View.GONE; return }
        if (!padMode || vidW <= 0) { c.visibility = View.GONE; return }
        val r = renderRect(v.width.toFloat(), v.height.toFloat()) ?: run { c.visibility = View.GONE; return }
        // point inside the surface's local frame rect → apply the surface's
        // own scale/pan transform (pivot = center) to land in pane coords.
        val lx = r[2] + padCurX * r[0]; val ly = r[3] + padCurY * r[1]
        val px = (lx - v.width / 2f) * vScale + v.width / 2f + panX
        val py = (ly - v.height / 2f) * vScale + v.height / 2f + panY
        c.translationX = px - c.width / 2f; c.translationY = py - c.height * 0.08f
        c.visibility = View.VISIBLE
    }

    // ── Voice/video call (Zoom-style) — mic/cam toggles on both roles ──
    // Transceivers are pre-negotiated sendrecv, so toggles are instant.
    private fun toggleCall(kind: String, viewer: Boolean) {
        val isMic = kind == "mic"
        val need = if (isMic) arrayOf(android.Manifest.permission.RECORD_AUDIO)
            else arrayOf(android.Manifest.permission.CAMERA, android.Manifest.permission.RECORD_AUDIO)
        val missing = need.filter {
            androidx.core.content.ContextCompat.checkSelfPermission(this, it) !=
                android.content.pm.PackageManager.PERMISSION_GRANTED }
        if (missing.isNotEmpty() && !(if (isMic) micOn else camOn)) {
            androidx.core.app.ActivityCompat.requestPermissions(this, missing.toTypedArray(), 77)
            toast("Allow ${missing.joinToString { it.substringAfterLast('.') }} then tap again")
            return
        }
        val next = !(if (isMic) micOn else camOn)
        val ok = if (viewer) {
            if (isMic) remoteViewer?.setMic(next) == true else remoteViewer?.setCam(next) == true
        } else {
            if (isMic) service?.peer()?.setMic(next) == true else service?.peer()?.setCam(next) == true
        }
        if (!ok) { toast("Peer doesn't support calls — update the other app"); return }
        if (isMic) micOn = next else camOn = next
        SessionBubbleService.micState = micOn
        SessionBubbleService.camState = camOn
        // self-preview PIP while our own camera is on (Zoom-style self view)
        if (!isMic) showSelfCam(
            if (next) (if (viewer) remoteViewer?.camVideoTrack() else service?.peer()?.camVideoTrack()) else null)
        paintCallBtn()
        chatAppend("System", (if (isMic) "Microphone " else "Camera ") + (if (next) "on — the other side can " +
            (if (isMic) "hear you" else "see you") else "off"), false)
    }

    private fun paintCallBtn() {
        vMicBtn.setBackgroundColor(Color.parseColor(if (micOn) "#15803d" else "#1e293b"))
        vCamBtn.setBackgroundColor(Color.parseColor(if (camOn) "#15803d" else "#1e293b"))
        if (::hostMicBtn.isInitialized) {
            hostMicBtn.setBackgroundColor(Color.parseColor(if (micOn) "#15803d" else "#1e293b"))
            hostCamBtn.setBackgroundColor(Color.parseColor(if (camOn) "#15803d" else "#1e293b"))
        }
    }

    /** Remote camera arrived → draggable picture-in-picture. tap = maximize. */
    private var selfView: org.webrtc.SurfaceViewRenderer? = null
    private var pipBox: LinearLayout? = null
    private var pipBig = false

    private fun pipContainer(): LinearLayout? {
        val parent = rootFrame ?: return null
        if (pipBox == null) {
            pipBox = LinearLayout(this).apply {
                orientation = LinearLayout.HORIZONTAL
                background = GradientDrawable().apply {
                    cornerRadius = dp(14).toFloat()
                    setColor(Color.parseColor("#0b1220"))
                    setStroke(dp(1), Color.parseColor("#334155"))
                }
                setPadding(dp(4), dp(4), dp(4), dp(4))
            }
            parent.addView(pipBox, android.widget.FrameLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT, LinearLayout.LayoutParams.WRAP_CONTENT,
                Gravity.BOTTOM or Gravity.END).apply { marginEnd = dp(14); bottomMargin = dp(90) })
            attachPipTouch(pipBox!!)
        }
        return pipBox
    }

    // free-drag anywhere; a short tap toggles the remote feed small↔big
    private fun attachPipTouch(box: View) {
        var ox = 0f; var oy = 0f; var moved = false
        box.setOnTouchListener { v, e ->
            when (e.action) {
                android.view.MotionEvent.ACTION_DOWN -> { ox = e.rawX - v.translationX; oy = e.rawY - v.translationY; moved = false; true }
                android.view.MotionEvent.ACTION_MOVE -> {
                    val nx = e.rawX - ox; val ny = e.rawY - oy
                    if (kotlin.math.abs(nx - v.translationX) > 3 || kotlin.math.abs(ny - v.translationY) > 3) moved = true
                    v.translationX = nx; v.translationY = ny; true
                }
                android.view.MotionEvent.ACTION_UP -> { if (!moved) togglePipSize(); true }
                else -> false
            }
        }
    }

    private fun togglePipSize() {
        pipBig = !pipBig
        val w = dp(if (pipBig) 240 else 110)
        pipView?.layoutParams?.let { it.width = w; it.height = w * 3 / 4; pipView?.layoutParams = it }
    }

    private fun showPip(track: org.webrtc.VideoTrack) {
        ui {
            val box = pipContainer() ?: return@ui
            val eglCtx = remoteViewer?.egl?.eglBaseContext ?: serviceEglContext() ?: return@ui
            if (pipView == null) {
                pipView = org.webrtc.SurfaceViewRenderer(this)
                pipView!!.init(eglCtx, null)
                pipView!!.setZOrderMediaOverlay(true)
                val w = dp(110)
                box.addView(pipView, LinearLayout.LayoutParams(w, w * 3 / 4))
            }
            pipView!!.visibility = View.VISIBLE
            track.addSink(pipView)
        }
    }

    // our own camera → tiny self-preview next to the remote feed
    private fun showSelfCam(track: org.webrtc.VideoTrack?) {
        ui {
            if (track == null) { hideSelfCam(); return@ui }
            val box = pipContainer() ?: return@ui
            val eglCtx = remoteViewer?.egl?.eglBaseContext ?: serviceEglContext() ?: return@ui
            if (selfView == null) {
                selfView = org.webrtc.SurfaceViewRenderer(this)
                selfView!!.init(eglCtx, null)
                selfView!!.setZOrderMediaOverlay(true)
                selfView!!.setMirror(true) // selfie camera looks natural mirrored
                val w = dp(56)
                box.addView(selfView, 0, LinearLayout.LayoutParams(w, w * 3 / 4).apply { marginEnd = dp(4) })
            }
            selfView!!.visibility = View.VISIBLE
            track.addSink(selfView)
        }
    }

    private fun hideSelfCam() {
        selfView?.let { (it.parent as? android.view.ViewGroup)?.removeView(it); runCatching { it.release() } }
        selfView = null
        // remote pip alone doesn't need the box chrome if nothing is in it
        if (pipView == null) pipBox?.let { (it.parent as? android.view.ViewGroup)?.removeView(it); pipBox = null }
    }

    // the host peer's egl is private — the PIP needs SOME egl context; if the
    // viewer isn't up (we're hosting), spin a tiny one for the pip sink.
    private var hostEgl: org.webrtc.EglBase? = null
    private fun serviceEglContext(): org.webrtc.EglBase.Context? {
        if (hostEgl == null) hostEgl = org.webrtc.EglBase.create()
        return hostEgl?.eglBaseContext
    }

    private fun stopCallMedia() {
        micOn = false; camOn = false; lastPipTrack = null
        if (::vMicBtn.isInitialized) paintCallBtn()
        pipView?.let { (it.parent as? android.view.ViewGroup)?.removeView(it); runCatching { it.release() } }
        pipView = null
        hideSelfCam()
        pipBox?.let { (it.parent as? android.view.ViewGroup)?.removeView(it); pipBox = null }
        pipBig = false
    }

    private fun setPadMode(on: Boolean) {
        padMode = on
        getSharedPreferences("bmydesk", Context.MODE_PRIVATE).edit().putBoolean("pad_mode", on).apply()
        vPadBtn.text = if (on) "👆 Touch" else "🖱 Pad"
        vPadBtn.setBackgroundColor(Color.parseColor(if (on) "#b45309" else "#1e293b"))
        padCursor?.visibility = if (on) View.VISIBLE else View.GONE
        if (on) updatePadCursor()
        if (chatPanel.visibility == View.VISIBLE) chatAppend("System",
            if (on) "Touchpad mode — swipe moves the cursor, tap clicks it"
            else "Touch mode — tap exactly where you want to click", false)
    }

    private fun bindViewerTouch() {
        val slop = android.view.ViewConfiguration.get(this).scaledTouchSlop * 2f
        val scaleDet = android.view.ScaleGestureDetector(this, object : android.view.ScaleGestureDetector.SimpleOnScaleGestureListener() {
            override fun onScale(d: android.view.ScaleGestureDetector): Boolean {
                vScale = (vScale * d.scaleFactor).coerceIn(1f, 5f)
                remoteSurface?.let { it.scaleX = vScale; it.scaleY = vScale }
                clampPan()
                updatePadCursor()
                return true
            }
        })
        val tapDet = android.view.GestureDetector(this, object : android.view.GestureDetector.SimpleOnGestureListener() {
            override fun onDoubleTap(e: android.view.MotionEvent): Boolean {
                vScale = 1f; panX = 0f; panY = 0f
                remoteSurface?.let { it.scaleX = 1f; it.scaleY = 1f; it.translationX = 0f; it.translationY = 0f }
                updatePadCursor()
                return true
            }
            override fun onLongPress(e: android.view.MotionEvent) {
                if (touchMoved) return // not a stationary press
                if (padMode) { // press-and-hold → remote drag starts at the cursor
                    padDrag = true; sendPad("down"); return
                }
                // long-press = right click (context menus on the remote desktop)
                sendMouse("move", e.x, e.y)
                sendMouse("down", e.x, e.y, 2)
                sendMouse("up", e.x, e.y, 2)
            }
        })
        remoteSurface?.setOnTouchListener { _, ev ->
            scaleDet.onTouchEvent(ev); tapDet.onTouchEvent(ev)
            if (ev.pointerCount >= 2) {
                // pinch starts mid-gesture — release any held remote button
                if (dragging) { sendMouse("up", ev.x, ev.y); dragging = false }
                if (padDrag) { sendPad("up"); padDrag = false }
                if (ev.actionMasked == android.view.MotionEvent.ACTION_MOVE) { padTwoTap = false; touchMoved = true }
                // two-finger drag on a fit view = remote scroll (wheel events)
                if (ev.actionMasked == android.view.MotionEvent.ACTION_MOVE && !fillMode && vScale <= 1.01f) {
                    val ay = (ev.getY(0) + ev.getY(1)) / 2f
                    if (last2Y != Float.MIN_VALUE) {
                        val now = android.os.SystemClock.elapsedRealtime()
                        if (now - lastWheel >= 50) {
                            lastWheel = now
                            remoteViewer?.sendInput(JsonObject().apply {
                                addProperty("t", "wheel"); addProperty("dy", (ay - last2Y) * 3); addProperty("dx", 0)
                            })
                        }
                    }
                    last2Y = ay
                } else if (ev.actionMasked == android.view.MotionEvent.ACTION_POINTER_DOWN) {
                    last2Y = (ev.getY(0) + ev.getY(1)) / 2f
                    padTwoTap = true // potential two-finger tap → right click in pad mode
                } else if (ev.actionMasked == android.view.MotionEvent.ACTION_POINTER_UP && padTwoTap) {
                    padTwoTap = false
                    if (padMode) { sendPad("move"); sendPad("down", 2); sendPad("up", 2) }
                }
                return@setOnTouchListener true
            }
            last2Y = Float.MIN_VALUE
            when (ev.actionMasked) {
                android.view.MotionEvent.ACTION_DOWN -> {
                    touchMoved = false; dragging = false
                    downX = ev.x; downY = ev.y; lastTX = ev.x; lastTY = ev.y
                    padLastX = ev.x; padLastY = ev.y
                }
                android.view.MotionEvent.ACTION_MOVE -> {
                    if (padMode) {
                        // relative cursor move — finger distance scaled up a bit
                        // so one swipe crosses a desktop screen comfortably
                        remoteSurface?.let { v ->
                            val r = renderRect(v.width.toFloat(), v.height.toFloat())
                            if (r != null && (r[0] > 0 && r[1] > 0)) {
                                padCurX = (padCurX + (ev.x - padLastX) * 1.4f / r[0]).coerceIn(0f, 1f)
                                padCurY = (padCurY + (ev.y - padLastY) * 1.4f / r[1]).coerceIn(0f, 1f)
                            }
                        }
                        padLastX = ev.x; padLastY = ev.y
                        if (kotlin.math.hypot(ev.x - downX, ev.y - downY) > slop * 0.5f) touchMoved = true
                        updatePadCursor()
                        val now = android.os.SystemClock.elapsedRealtime()
                        if (now - padMoveAt >= 33) { padMoveAt = now; sendPad("move") }
                    } else {
                    val moved = kotlin.math.hypot(ev.x - downX, ev.y - downY) > slop
                    if (vScale > 1.01f || fillMode) { // zoomed/fill → pan locally instead of remote-drag
                        panX += ev.x - lastTX; panY += ev.y - lastTY; lastTX = ev.x; lastTY = ev.y
                        clampPan()
                        remoteSurface?.let { it.translationX = panX; it.translationY = panY }
                        updatePadCursor()
                        touchMoved = true
                    } else {
                        if (moved) touchMoved = true
                        if (touchMoved && !dragging) { // drag starts here — press the button
                            dragging = true
                            sendMouse("down", ev.x, ev.y)
                        }
                        if (dragging || !touchMoved) {
                            val now = android.os.SystemClock.elapsedRealtime()
                            if (now - lastMove >= 33) {
                                lastMove = now
                                sendMouse("move", ev.x, ev.y)
                            }
                        }
                    }
                    }
                }
                android.view.MotionEvent.ACTION_UP -> {
                    if (padMode) {
                        if (padDrag) { sendPad("move"); sendPad("up"); padDrag = false }
                        else if (!touchMoved) { // tap = click wherever the cursor sits
                            sendPad("move"); sendPad("down"); sendPad("up")
                        }
                    } else if (dragging) {
                        sendMouse("move", ev.x, ev.y)
                        sendMouse("up", ev.x, ev.y)
                        dragging = false
                    } else if (!touchMoved) { // clean tap = left click
                        sendMouse("move", ev.x, ev.y)
                        sendMouse("down", ev.x, ev.y)
                        sendMouse("up", ev.x, ev.y)
                    }
                }
            }
            true
        }
    }
    private fun clampPan() {
        remoteSurface?.let { v ->
            if (fillMode) { // pan range = how much of the cropped frame hangs off-view
                val r = renderRect(v.width.toFloat(), v.height.toFloat())
                val mx = if (r != null) maxOf(0f, (r[0] - v.width) / 2f) * vScale else 0f
                val my = if (r != null) maxOf(0f, (r[1] - v.height) / 2f) * vScale else 0f
                panX = panX.coerceIn(-mx, mx); panY = panY.coerceIn(-my, my)
            } else {
                val mx = v.width * (vScale - 1) / 2f; val my = v.height * (vScale - 1) / 2f
                panX = panX.coerceIn(-mx, mx); panY = panY.coerceIn(-my, my)
            }
        }
    }

    // Immersive full-screen while viewing — system bars eat real estate the
    // remote desktop needs.
    @Suppress("DEPRECATION")
    private fun enterImmersive() {
        window.decorView.systemUiVisibility = (View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
            or View.SYSTEM_UI_FLAG_FULLSCREEN or View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
            or View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN or View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
            or View.SYSTEM_UI_FLAG_LAYOUT_STABLE)
    }
    @Suppress("DEPRECATION")
    private fun exitImmersive() { window.decorView.systemUiVisibility = View.SYSTEM_UI_FLAG_VISIBLE }

    // Metered link (mobile data) — auto-drop to SD so the stream stays fluid.
    private fun isMetered(): Boolean =
        (getSystemService(Context.CONNECTIVITY_SERVICE) as android.net.ConnectivityManager).isActiveNetworkMetered

    // ── Session minimize/restore — back NEVER kills a live session; it just
    // drops a floating bubble and the peer keeps streaming underneath ──
    private var viewerMinimized = false

    private fun minimizeViewer() {
        if (remoteViewer == null && vJoin == null && service == null) return
        viewerMinimized = true
        viewerPane.visibility = View.GONE
        chatPanel.visibility = View.GONE
        exitImmersive()
        // foreground service keeps the process (and the peer) warm + shows
        // the overlay bubble / persistent "return" notification
        startService(Intent(this, SessionBubbleService::class.java))
        moveTaskToBack(true)
    }

    private fun restoreViewer() {
        if (remoteViewer == null && vJoin == null && service == null) { viewerMinimized = false; return }
        viewerMinimized = false
        stopService(Intent(this, SessionBubbleService::class.java))
        if (remoteViewer != null || vJoin != null) { viewerPane.visibility = View.VISIBLE; enterImmersive() }
    }

    // ── viewer toolbar ↔ floating button — full-screen remote, controls on tap ──
    private fun expandVBar() {
        vBar.visibility = View.VISIBLE
        viewerFab.visibility = View.GONE
        vBarHideRunnable?.let { sessTicker.removeCallbacks(it) }
        val r = Runnable { collapseVBar() }
        vBarHideRunnable = r
        sessTicker.postDelayed(r, 7000) // idle → back to full-screen
    }

    private fun collapseVBar() {
        if (remoteViewer == null || !streaming) return // keep bar while connecting/reconnecting
        vBar.visibility = View.GONE
        viewerFab.visibility = View.VISIBLE
    }

    // bubble toolbar taps land here — no UI needed for mic/cam/ctl/end
    private fun onBubbleAction(cmd: String) {
        ui {
            when (cmd) {
                "mic", "cam" -> toggleCall(cmd, viewer = remoteViewer != null)
                "ctl" -> toggleViewOnlyLive() // host unlocks/locks remote control from the bubble
            }
            SessionBubbleService.micState = micOn
            SessionBubbleService.camState = camOn
            SessionBubbleService.viewOnlyState = viewOnlyLive
        }
    }

    /** 'confirm_disconnect' intent → the bubble's ✕: explicit Yes ends it. */
    private fun maybeConfirmDisconnect(i: Intent?) {
        if (i?.getBooleanExtra("confirm_disconnect", false) != true) return
        i.removeExtra("confirm_disconnect")
        val viewerLive = remoteViewer != null || vJoin != null
        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("End session?")
            .setMessage("Disconnect this remote session?")
            .setPositiveButton("Yes, end it") { _, _ -> if (viewerLive) exitViewer() else endSession() }
            .setNegativeButton("No, keep it", null)
            .show()
    }

    private fun confirmExitViewer() {
        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("Disconnect?")
            .setMessage("End this remote session? The other device will see you leave.\n\nTip: press Back instead to keep the session running in the background bubble.")
            .setPositiveButton("Yes, disconnect") { _, _ -> exitViewer() }
            .setNegativeButton("No, keep it", null)
            .show()
    }

    private fun exitViewer() {
        viewerMinimized = false
        stopService(Intent(this, SessionBubbleService::class.java))
        runCatching { signaling.sendViewer("end", JsonObject()) }
        vJoin = null // stops the viewer poll loop
        // clean exit — a later crash must not resurrect a finished session
        getSharedPreferences("bmydesk", Context.MODE_PRIVATE).edit().remove("resume_code").apply()
        remoteViewer?.stop(); remoteViewer = null
        signaling.disconnectViewer()
        remoteSurface?.let { viewerPane.removeView(it); runCatching { it.release() } }
        remoteSurface = null
        offerTimeout?.let { sessTicker.removeCallbacks(it) }
        streaming = false; liveSince = 0L; lastFilePct = -1; lastRttMs = 0; vLocked = false
        padCursor?.visibility = View.GONE; padDrag = false; padTwoTap = false
        stopCallMedia()
        closeLiveKb()
        fillMode = false; vFitBtn.text = "⛶ Fill"
        requestedOrientation = android.content.pm.ActivityInfo.SCREEN_ORIENTATION_UNSPECIFIED
        vScale = 1f; panX = 0f; panY = 0f; vidW = 0; vidH = 0; vidRot = 0
        exitImmersive()
        keysRow.visibility = View.GONE
        viewerPane.visibility = View.GONE
        vBar.visibility = View.VISIBLE; viewerFab.visibility = View.GONE
        vBarHideRunnable?.let { sessTicker.removeCallbacks(it) }; vBarHideRunnable = null
        vSessRow.visibility = View.GONE; chatPanel.visibility = View.GONE
        chatLog.removeAllViews(); chatTickRows.clear(); unseenChatIds.clear()
        vProgReset()
    }

    // Back NEVER kills a remote session — it minimizes to the floating
    // bubble so the user can keep using the phone while connected.
    // Disconnect only happens via the explicit Yes/No confirm.
    @Deprecated("Deprecated in Java")
    override fun onBackPressed() {
        if (viewerPane.visibility == View.VISIBLE) { minimizeViewer(); return }
        maybeRateThenExit()
    }

    private fun maybeRateThenExit() {
        val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        if (System.currentTimeMillis() < prefs.getLong("rate_next_at", 0L)) { finish(); return }
        prefs.edit().putLong("rate_next_at", System.currentTimeMillis() + 7L * 24 * 3600_000L).apply()
        var stars = 0
        val wrap = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL; setPadding(dp(20), dp(4), dp(20), 0) }
        val starRow = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER }
        val starTvs = (1..5).map {
            TextView(this).apply {
                text = "☆"; textSize = 32f
                setTextColor(Color.parseColor("#fbbf24")); setPadding(dp(7), 0, dp(7), 0)
            }
        }
        fun paintStars() { starTvs.forEachIndexed { i, tv -> tv.text = if (i < stars) "★" else "☆" } }
        starTvs.forEachIndexed { i, tv -> tv.setOnClickListener { stars = i + 1; paintStars() }; starRow.addView(tv) }
        val fbInput = android.widget.EditText(this).apply {
            hint = "Anything to add? (optional)"; textSize = 13f
            setHintTextColor(Color.parseColor("#64748b")); setTextColor(Color.WHITE)
        }
        wrap.addView(starRow); wrap.addView(fbInput)
        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("How was BMyDesk?")
            .setView(wrap)
            .setPositiveButton("Submit & exit") { _, _ ->
                val r = stars; val fb = fbInput.text.toString().trim().ifBlank { null }
                if (r > 0) reg?.let { rr -> thread { api.appRate(rr.code, rr.agentToken, r, fb, "android") } }
                finish()
            }
            .setNeutralButton("Just exit") { _, _ -> finish() }
            .setNegativeButton("Stay", null)
            .show()
    }

    override fun onDestroy() {
        runCatching { endSession() }
        runCatching { exitViewer() }
        signaling.disconnect()
        super.onDestroy()
    }
}
