package com.believoo.bmydeskagent

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
    private var reg: AgentApi.Registration? = null
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

    // Viewer mode (Remote Desk — connect to another code)
    private val vSignaling = SignalingClient()
    private var remoteViewer: RemoteViewer? = null
    private var remoteSurface: org.webrtc.SurfaceViewRenderer? = null
    private lateinit var remoteCodeInput: android.widget.EditText
    private lateinit var viewerPane: android.widget.FrameLayout
    private lateinit var viewerStatus: TextView

    private val conn = object : ServiceConnection {
        override fun onServiceConnected(name: ComponentName?, binder: IBinder?) {
            service = (binder as ScreenShareService.LocalBinder).service
            bound = true
            service?.events = object : ScreenShareService.Events {
                override fun onConnected() = ui { setStatus("Viewer connected — sharing screen", "#22c55e") }
                override fun onDisconnected() = ui { setStatus("Viewer disconnected", "#f59e0b") }
                override fun onError(msg: String) = ui { setStatus(msg, "#ef4444") }
            }
            service?.signaling = signaling
            service?.setIce(iceServersFrom(reg?.iceServers))
            // flush an offer that arrived before binding finished
            waitingOfferSdp?.let { sdp -> waitingOfferSdp = null; handleOffer(sdp) }
        }
        override fun onServiceDisconnected(name: ComponentName?) { bound = false; service = null }
    }

    private val signaling = SignalingClient()

    override fun onCreate(savedInstanceState: Bundle?) {
        // Show the real crash instead of silently dying — screenshot it and share.
        Thread.setDefaultUncaughtExceptionHandler { t, e ->
            val trace = android.util.Log.getStackTraceString(e)
            android.util.Log.e("BMyDesk", "crash", e)
            android.os.Handler(android.os.Looper.getMainLooper()).post {
                runCatching {
                    android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
                        .setTitle("BMyDesk crashed")
                        .setMessage("${e.javaClass.simpleName}: ${e.message}\n\n${trace.take(1200)}")
                        .setPositiveButton("Close") { _, _ -> android.os.Process.killProcess(android.os.Process.myPid()) }
                        .setCancelable(false)
                        .show()
                }
            }
        }
        super.onCreate(savedInstanceState)
        buildUi()
        if (!handleAuthIntent(intent)) register()
        checkUpdate()
    }

    override fun onNewIntent(intent: Intent?) {
        super.onNewIntent(intent)
        if (intent != null && handleAuthIntent(intent)) return
    }

    // bmydesk://auth?token=…&name=… — signed-in handoff from the website /
    // Google OAuth flow. Stores the member token and re-registers linked.
    private fun handleAuthIntent(i: Intent?): Boolean {
        val u = i?.data ?: return false
        if (u.scheme != "bmydesk" || u.host != "auth") return false
        val token = u.getQueryParameter("token") ?: return false
        val name = u.getQueryParameter("name") ?: ""
        getSharedPreferences("bmydesk", Context.MODE_PRIVATE).edit()
            .putString("member_token", token).putString("member_name", name).apply()
        ui { setStatus("Signed in as $name", "#22c55e") }
        register()
        return true
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

    // ── UI ─────────────────────────────────────────────────────────
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
        val scroll = ScrollView(this)
        val col = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER_HORIZONTAL
            setPadding(48, 60, 48, 40)
        }
        scroll.addView(col)
        frame.addView(scroll)

        // theme toggle (top-right)
        col.addView(TextView(this).apply {
            text = if (dark) "☀ Light mode" else "☾ Dark mode"
            setTextColor(txtSub); textSize = 12f; gravity = Gravity.END
            setOnClickListener { prefs.edit().putBoolean("dark", !dark).apply(); recreate() }
        })

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
        val btnRow = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER }
        btnRow.addView(Button(this).apply {
            text = "Accept"
            setOnClickListener { acceptRequest() }
        })
        btnRow.addView(Button(this).apply {
            text = "Reject"; setTextColor(Color.WHITE)
            setOnClickListener { rejectRequest() }
        })
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
        remoteCodeInput = android.widget.EditText(this).apply {
            hint = "ABC123XY"
            setTextColor(rose); textSize = 17f; typeface = Typeface.MONOSPACE
            setHintTextColor(Color.parseColor("#475569"))
            filters = arrayOf(android.text.InputFilter.AllCaps(), android.text.InputFilter.LengthFilter(8))
            inputType = android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_FLAG_NO_SUGGESTIONS
            setPadding(20, 12, 20, 12)
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
            background = GradientDrawable().apply { setColor(Color.parseColor("#131c2e")); cornerRadius = 16f }
        }
        row.addView(remoteCodeInput)
        row.addView(Button(this).apply {
            text = "Connect"
            setOnClickListener { connectRemote() }
        })
        remoteCard.addView(row)
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
            val loginBtn = Button(this).apply { text = "Sign in" }
            val googleBtn = Button(this).apply {
                text = "Sign in with Google"
                setOnClickListener {
                    startActivity(Intent(Intent.ACTION_VIEW, android.net.Uri.parse("https://bmydesk.believoo.com/auth/google?agent=1")))
                }
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

        endBtn = Button(this).apply {
            text = "End session"; visibility = View.GONE
            setOnClickListener { endSession() }
        }
        col.addView(endBtn)

        // ── Fullscreen viewer pane (remote screen + touch control) ──
        viewerPane = android.widget.FrameLayout(this).apply {
            setBackgroundColor(Color.BLACK); visibility = View.GONE
            layoutParams = LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.MATCH_PARENT)
        }
        remoteSurface = org.webrtc.SurfaceViewRenderer(this)
        viewerPane.addView(remoteSurface, android.widget.FrameLayout.LayoutParams(
            android.widget.FrameLayout.LayoutParams.MATCH_PARENT, android.widget.FrameLayout.LayoutParams.MATCH_PARENT))
        val vBar = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER_VERTICAL
            setPadding(20, 10, 20, 10); setBackgroundColor(Color.parseColor("#0f172a"))
        }
        viewerStatus = TextView(this).apply { text = "Connecting…"; setTextColor(Color.parseColor("#94a3b8")); textSize = 12f }
        vBar.addView(viewerStatus, LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f))
        vBar.addView(Button(this).apply { text = "Disconnect"; setOnClickListener { exitViewer() } })
        viewerPane.addView(vBar, android.widget.FrameLayout.LayoutParams(
            android.widget.FrameLayout.LayoutParams.MATCH_PARENT, android.widget.FrameLayout.LayoutParams.WRAP_CONTENT,
            Gravity.TOP))
        frame.addView(viewerPane)

        col.addView(TextView(this).apply {
            text = "v" + BuildConfig.VERSION_NAME
            setTextColor(Color.parseColor("#475569")); textSize = 10f; gravity = Gravity.CENTER
            setPadding(0, 20, 0, 0)
        })

        setContentView(frame)
    }

    private fun ui(block: () -> Unit) = runOnUiThread(block)
    private fun setStatus(t: String, color: String = "#94a3b8") {
        statusView.text = t; statusView.setTextColor(Color.parseColor(color))
    }

    // ── Register + signaling ───────────────────────────────────────
    private fun register() {
        thread {
            try {
                val r = api.register(
                    android.os.Build.MODEL ?: "android-device", "android",
                    getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getString("member_token", null)
                )
                reg = r
                ui { codeView.text = r.code; setStatus("Ready — share your code", "#22c55e"); spinner.visibility = View.GONE }
                connectSignaling(r)
            } catch (e: Exception) {
                ui { codeView.text = "ERROR"; setStatus("Cannot reach server — retrying…", "#ef4444") }
                Thread.sleep(5000); register()
            }
        }
    }

    private fun connectSignaling(r: AgentApi.Registration) {
        signaling.listener = object : SignalingClient.Listener {
            override fun onConnected() {}
            override fun onDisconnected() {}
            override fun onJoinRequest(name: String) {
                ui { reqName.text = "$name wants to view this device"; reqBox.visibility = View.VISIBLE }
            }
            override fun onSignal(p: JsonObject) {
                when (p.get("kind")?.asString) {
                    "offer" -> {
                        val sdp = p.get("sdp").asString
                        if (pendingProjection) waitingOfferSdp = sdp
                        else ui { handleOffer(sdp) }
                    }
                    "ice" -> p.getAsJsonObject("candidate")?.let { service?.addIce(it) }
                }
            }
            override fun onEnd() { ui { endSession() } }
        }
        thread {
            try { signaling.connect(r.channel, r.agentToken) }
            catch (e: Throwable) { ui { setStatus("Realtime failed: ${e.message}", "#ef4444") } }
        }
    }

    // ── Accept flow: projection permission → service → capture ─────
    private fun acceptRequest() {
        reqBox.visibility = View.GONE
        pendingProjection = true
        setStatus("Requesting screen capture permission…", "#f59e0b")
        val mpm = getSystemService(Context.MEDIA_PROJECTION_SERVICE) as MediaProjectionManager
        startActivityForResult(mpm.createScreenCaptureIntent(), 1001)
    }

    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode != 1001) return
        if (resultCode != RESULT_OK || data == null) {
            pendingProjection = false
            signaling.send("join-reject", JsonObject())
            setStatus("Capture permission denied", "#ef4444")
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
        signaling.send("join-accept", JsonObject())
        setStatus("Accepted — waiting for stream…", "#22c55e")
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

    private fun rejectRequest() {
        reqBox.visibility = View.GONE
        signaling.send("join-reject", JsonObject())
        setStatus("Request rejected", "#f59e0b")
    }

    private fun endSession() {
        reg?.let { thread { api.end(it.code, it.agentToken) } }
        signaling.send("end", JsonObject())
        runCatching { startService(Intent(this, ScreenShareService::class.java).setAction(ScreenShareService.ACTION_STOP)) }
        runCatching { if (bound) unbindService(conn); bound = false }
        endBtn.visibility = View.GONE
        setStatus("Session ended", "#f59e0b")
    }

    // ── Remote Desk (viewer): join another device's session ────────
    private var vJoin: AgentApi.JoinResult? = null

    private fun connectRemote() {
        val code = remoteCodeInput.text.toString().trim().uppercase()
        if (code.length < 6) { remoteCodeInput.requestFocus(); return }
        ui {
            viewerPane.visibility = View.VISIBLE
            viewerStatus.text = "Joining $code…"
        }
        thread {
            try {
                val j = api.join(code)
                vJoin = j
                vSignaling.listener = object : SignalingClient.Listener {
                    override fun onConnected() {}
                    override fun onDisconnected() {}
                    override fun onJoinRequest(name: String) {}
                    override fun onSubscribed() {
                        vSignaling.send("join-request", JsonObject().apply {
                            addProperty("name", (android.os.Build.MODEL ?: "Android") + " (app)")
                        })
                        ui { viewerStatus.text = "Waiting for host approval…" }
                    }
                    override fun onJoinAccept() {
                        ui { viewerStatus.text = "Accepted — starting stream…" }
                        startViewerPeer(j)
                    }
                    override fun onJoinReject() {
                        ui { viewerStatus.text = "Host declined"; }
                        thread { Thread.sleep(1500); ui { exitViewer() } }
                    }
                    override fun onSignal(p: JsonObject) { remoteViewer?.onSignal(p) }
                    override fun onEnd() { ui { viewerStatus.text = "Host ended session"; thread { Thread.sleep(1200); ui { exitViewer() } } } }
                }
                vSignaling.connect(j.channel, j.viewerToken)
            } catch (e: Exception) {
                ui { viewerStatus.text = "Join failed: ${e.message}"; }
                thread { Thread.sleep(2500); ui { exitViewer() } }
            }
        }
    }

    private fun startViewerPeer(j: AgentApi.JoinResult) {
        remoteViewer = RemoteViewer(this).apply {
            listener = object : RemoteViewer.Listener {
                override fun sendSignal(payload: JsonObject) = vSignaling.send("signal", payload)
                override fun onPeerConnected() = ui { viewerStatus.text = "Connected — touch to control" }
                override fun onPeerDisconnected() = ui { viewerStatus.text = "Disconnected" }
                override fun onTrack(track: org.webrtc.VideoTrack) = ui {
                    remoteSurface!!.init(remoteViewer!!.egl.eglBaseContext, null)
                    remoteSurface!!.setScalingType(org.webrtc.RendererCommon.ScalingType.SCALE_ASPECT_FIT)
                    track.addSink(remoteSurface)
                    bindViewerTouch()
                    viewerStatus.text = "Live — touch to control"
                }
                override fun onError(message: String) = ui { viewerStatus.text = message }
            }
        }
        remoteViewer!!.start(iceServersFrom(j.iceServers))
    }

    // Touch → input events (same JSON protocol as the web/Electron viewer)
    private var touchMoved = false
    private var lastMove = 0L
    private fun bindViewerTouch() {
        remoteSurface?.setOnTouchListener { _, ev ->
            val w = remoteSurface!!.width.toFloat().coerceAtLeast(1f)
            val h = remoteSurface!!.height.toFloat().coerceAtLeast(1f)
            when (ev.action) {
                android.view.MotionEvent.ACTION_DOWN -> touchMoved = false
                android.view.MotionEvent.ACTION_MOVE -> {
                    touchMoved = true
                    val now = android.os.SystemClock.elapsedRealtime()
                    if (now - lastMove >= 33) {
                        lastMove = now
                        remoteViewer?.sendInput(JsonObject().apply {
                            addProperty("t", "move"); addProperty("x", ev.x / w); addProperty("y", ev.y / h)
                        })
                    }
                }
                android.view.MotionEvent.ACTION_UP -> {
                    if (!touchMoved) { // tap = left click
                        remoteViewer?.sendInput(JsonObject().apply {
                            addProperty("t", "move"); addProperty("x", ev.x / w); addProperty("y", ev.y / h)
                        })
                        remoteViewer?.sendInput(JsonObject().apply { addProperty("t", "down"); addProperty("b", 0) })
                        remoteViewer?.sendInput(JsonObject().apply { addProperty("t", "up"); addProperty("b", 0) })
                    }
                }
            }
            true
        }
    }

    private fun exitViewer() {
        runCatching { vSignaling.send("end", JsonObject()) }
        remoteViewer?.stop(); remoteViewer = null
        vSignaling.disconnect()
        runCatching { remoteSurface?.release() }
        viewerPane.visibility = View.GONE
    }

    override fun onDestroy() {
        runCatching { endSession() }
        runCatching { exitViewer() }
        signaling.disconnect()
        super.onDestroy()
    }
}
