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
    private var remoteViewer: RemoteViewer? = null
    private var remoteSurface: org.webrtc.SurfaceViewRenderer? = null
    private lateinit var remoteCodeInput: android.widget.EditText
    private lateinit var remotePinInput: android.widget.EditText
    private lateinit var viewerPane: android.widget.FrameLayout
    private lateinit var viewerStatus: TextView

    // Session tools (chat + file) — shared by host + viewer modes
    private lateinit var sessRow: LinearLayout
    private lateinit var chatPanel: LinearLayout
    private lateinit var chatLog: TextView
    private lateinit var chatScroll: ScrollView
    private lateinit var chatInput: android.widget.EditText
    private var chatCount = 0

    private val conn = object : ServiceConnection {
        override fun onServiceConnected(name: ComponentName?, binder: IBinder?) {
            service = (binder as ScreenShareService.LocalBinder).service
            bound = true
            service?.events = object : ScreenShareService.Events {
                override fun onConnected() = ui { setStatus("Viewer connected — sharing screen", "#22c55e"); sessRow.visibility = View.VISIBLE }
                override fun onDisconnected() = ui { setStatus("Viewer disconnected", "#f59e0b"); sessRow.visibility = View.GONE; chatPanel.visibility = View.GONE }
                override fun onError(msg: String) = ui { setStatus(msg, "#ef4444") }
                override fun onCtlReady() = ui { sessRow.visibility = View.VISIBLE; startClipSync() }
                override fun onChat(from: String, text: String) = ui { chatAppend(from, text, false) }
                override fun onClip(text: String) = clipSet(text)
                override fun onFile(name: String, data: ByteArray) = saveIncoming(name, data)
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
    // nonce dedupe — signals arrive via ws AND the HTTP queue; the shared
    // "n" field collapses double delivery.
    private val seenNonces = java.util.concurrent.ConcurrentHashMap.newKeySet<String>()
    private fun sigNew(p: JsonObject): Boolean {
        val n = p.get("n")?.asString ?: return true
        if (!seenNonces.add(n)) return false
        if (seenNonces.size > 600) seenNonces.clear()
        return true
    }
    // queued signals carry a server timestamp — drop stale ones so leftovers
    // from a dead pairing can't kill a fresh session
    private fun sigFresh(p: JsonObject): Boolean {
        val at = p.get("at")?.asString ?: return true
        return runCatching {
            System.currentTimeMillis() - java.time.Instant.parse(at).toEpochMilli() < 45_000
        }.getOrDefault(true)
    }
    // set when the host answers a join request — polled requests older than
    // this are not re-shown (fixes the "accept loop").
    @Volatile private var respondedAtMs = 0L

    private fun handleHostSignal(p: JsonObject) {
        when (p.get("kind")?.asString) {
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
        promptBatteryOnce()
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

    // Unattended access — let trusted viewers connect with code+PIN, no
    // approval needed. Empty entry removes the PIN (approval required again).
    private fun promptUnattendedPin() {
        val code = reg?.code ?: return
        val tok = reg?.agentToken ?: return
        val input = android.widget.EditText(this).apply {
            hint = "4-12 digit PIN (empty = remove)"
            inputType = android.text.InputType.TYPE_CLASS_NUMBER
        }
        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
            .setTitle("Unattended access")
            .setMessage("Viewers who know this device's code + PIN can connect without approval.")
            .setView(input)
            .setPositiveButton("Save") { _, _ ->
                val pin = input.text.toString().trim()
                thread {
                    val ok = api.setPin(code, tok, pin)
                    ui { setStatus(if (ok) (if (pin.isBlank()) "Unattended access OFF" else "Unattended access ON") else "PIN update failed", if (ok && pin.isNotBlank()) "#22c55e" else "#f59e0b") }
                }
            }
            .setNegativeButton("Cancel", null)
            .show()
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
        remotePinInput = android.widget.EditText(this).apply {
            hint = "PIN — only for unattended devices"
            setTextColor(rose); textSize = 13f; typeface = Typeface.MONOSPACE
            setHintTextColor(Color.parseColor("#475569"))
            filters = arrayOf(android.text.InputFilter.LengthFilter(12))
            inputType = android.text.InputType.TYPE_CLASS_NUMBER
            setPadding(20, 10, 20, 10)
            layoutParams = LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT).apply { topMargin = 10 }
            background = GradientDrawable().apply { setColor(Color.parseColor("#131c2e")); cornerRadius = 16f }
        }
        remoteCard.addView(remotePinInput)
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
        col.addView(TextView(this).apply {
            text = "🔒 Set unattended-access PIN"
            setTextColor(Color.parseColor("#475569")); textSize = 12f; gravity = Gravity.CENTER
            setPadding(0, 10, 0, 0)
            setOnClickListener { promptUnattendedPin() }
        })
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

        endBtn = Button(this).apply {
            text = "End session"; visibility = View.GONE
            setOnClickListener { endSession() }
        }
        col.addView(endBtn)

        // in-session tools: chat + file send (visible while a viewer is connected)
        sessRow = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL; gravity = Gravity.CENTER
            visibility = View.GONE; setPadding(0, 12, 0, 0)
        }
        sessRow.addView(Button(this).apply {
            text = "💬 Chat"; setOnClickListener { toggleChat() }
        })
        sessRow.addView(Button(this).apply {
            text = "📎 Send file"; setOnClickListener { pickFile() }
        })
        col.addView(sessRow)

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
        vSessRow = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL; visibility = View.GONE }
        vSessRow.addView(Button(this).apply { text = "💬"; setOnClickListener { toggleChat() } })
        vSessRow.addView(Button(this).apply { text = "📎"; setOnClickListener { pickFile() } })
        vSessRow.addView(Button(this).apply { text = "⌨"; setOnClickListener { keysRow.visibility = if (keysRow.visibility == View.VISIBLE) View.GONE else View.VISIBLE } })
        vBar.addView(vSessRow)
        vBar.addView(Button(this).apply { text = "Disconnect"; setOnClickListener { exitViewer() } })
        viewerPane.addView(vBar, android.widget.FrameLayout.LayoutParams(
            android.widget.FrameLayout.LayoutParams.MATCH_PARENT, android.widget.FrameLayout.LayoutParams.WRAP_CONTENT,
            Gravity.TOP))
        // special-keys row — sits under the viewer bar, toggled by ⌨
        keysRow = LinearLayout(this).apply {
            orientation = LinearLayout.HORIZONTAL; visibility = View.GONE
            setBackgroundColor(Color.parseColor("#f00f172a")); setPadding(10, 4, 10, 4)
        }
        mapOf("Esc" to "Escape", "Tab" to "Tab", "Ctrl" to "ControlLeft", "Alt" to "AltLeft",
              "◀" to "ArrowLeft", "▲" to "ArrowUp", "▼" to "ArrowDown", "▶" to "ArrowRight",
              "Del" to "Delete", "Home" to "Home", "Win" to "MetaLeft").forEach { (lbl, code) ->
            keysRow.addView(Button(this).apply {
                text = lbl; textSize = 11f
                setOnClickListener { sendKey(code) }
            })
        }
        keysRow.addView(Button(this).apply {
            text = "⌨"; textSize = 11f
            setOnClickListener { typeDialog() }
        })
        viewerPane.addView(keysRow, android.widget.FrameLayout.LayoutParams(
            android.widget.FrameLayout.LayoutParams.MATCH_PARENT, android.widget.FrameLayout.LayoutParams.WRAP_CONTENT,
            Gravity.BOTTOM))
        frame.addView(viewerPane)

        // Session chat panel — overlays bottom of whichever screen is up
        chatPanel = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL; visibility = View.GONE
            setBackgroundColor(Color.parseColor("#f20f172a")); setPadding(16, 10, 16, 10)
        }
        chatScroll = ScrollView(this)
        chatLog = TextView(this).apply { setTextColor(Color.parseColor("#e2e8f0")); textSize = 13f }
        chatScroll.addView(chatLog)
        chatPanel.addView(chatScroll, LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, 0, 1f))
        val chatRow = LinearLayout(this).apply { orientation = LinearLayout.HORIZONTAL }
        chatInput = android.widget.EditText(this).apply {
            hint = "Message…"; setTextColor(Color.WHITE); textSize = 13f
            setHintTextColor(Color.parseColor("#64748b")); setPadding(14, 8, 14, 8)
            background = GradientDrawable().apply { setColor(Color.parseColor("#1e293b")); cornerRadius = 14f }
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
            setOnEditorActionListener { _, _, _ -> sendChat(); true }
        }
        chatRow.addView(chatInput)
        chatRow.addView(Button(this).apply { text = "Send"; setOnClickListener { sendChat() } })
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

    private lateinit var vSessRow: LinearLayout
    private lateinit var keysRow: LinearLayout

    // ── Session tools: chat / file / clipboard (host + viewer share these) ──
    private fun activeCtl(): CtlChannel? =
        if (remoteViewer != null) remoteViewer?.ctl else service?.ctl()

    private fun toggleChat() {
        chatPanel.visibility = if (chatPanel.visibility == View.VISIBLE) View.GONE else View.VISIBLE
        chatCount = 0
    }

    private fun chatAppend(from: String, text: String, mine: Boolean) {
        if (text.isBlank()) return
        chatLog.append((if (mine) "You" else from) + ": " + text + "\n")
        chatScroll.post { chatScroll.fullScroll(View.FOCUS_DOWN) }
        if (chatPanel.visibility != View.VISIBLE) {
            chatCount++
            if (from != "System") toast("💬 $from: ${text.take(60)}")
        }
    }

    private fun sendChat() {
        val t = chatInput.text.toString().trim(); if (t.isEmpty()) return
        val me = getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getString("member_name", null)
            ?: (android.os.Build.MODEL ?: "Android")
        activeCtl()?.sendChat(me, t)
        chatAppend("You", t, true)
        chatInput.setText("")
    }

    private fun pickFile() {
        if (activeCtl()?.open() != true) { toast("No session — connect first"); return }
        startActivityForResult(Intent(Intent.ACTION_OPEN_DOCUMENT).apply {
            addCategory(Intent.CATEGORY_OPENABLE); type = "*/*"
        }, 1002)
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
        clipHandler.removeCallbacks(clipTick)
        lastClip = ""
        clipHandler.postDelayed(clipTick, 2500)
    }

    private fun toast(t: String) = android.widget.Toast.makeText(this, t, android.widget.Toast.LENGTH_SHORT).show()

    // ── Special keys + typing to the remote ──
    private fun sendKey(code: String) {
        remoteViewer?.sendInput(JsonObject().apply { addProperty("t", "key"); addProperty("code", code); addProperty("down", true) })
        remoteViewer?.sendInput(JsonObject().apply { addProperty("t", "key"); addProperty("code", code); addProperty("down", false) })
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
            if (m.second) remoteViewer?.sendInput(JsonObject().apply { addProperty("t", "key"); addProperty("code", "ShiftLeft"); addProperty("down", true) })
            sendKey(m.first)
            if (m.second) remoteViewer?.sendInput(JsonObject().apply { addProperty("t", "key"); addProperty("code", "ShiftLeft"); addProperty("down", false) })
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

    // Ask once to enable the accessibility service that injects viewer input.
    private fun promptAccessibilityOnce() {
        if (RemoteControlService.enabled()) return
        val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        if (prefs.getBoolean("access_prompted", false)) return
        prefs.edit().putBoolean("access_prompted", true).apply()
        ui {
            android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
                .setTitle("Enable remote control?")
                .setMessage("Viewers can tap and control this device if you enable the BMyDesk accessibility service. Without it sessions are view-only.\n\nTurn on 'BMyDesk Agent' in the list that opens.")
                .setPositiveButton("Enable") { _, _ ->
                    runCatching { startActivity(Intent(android.provider.Settings.ACTION_ACCESSIBILITY_SETTINGS)) }
                }
                .setNegativeButton("View only", null)
                .show()
        }
    }

    // Battery optimization kills background agents — ask once to exempt.
    private fun promptBatteryOnce() {
        val prefs = getSharedPreferences("bmydesk", Context.MODE_PRIVATE)
        if (prefs.getBoolean("batt_prompted", false)) return
        prefs.edit().putBoolean("batt_prompted", true).apply()
        thread {
            Thread.sleep(4000) // let registration settle first
            ui {
                runCatching {
                    val pm = getSystemService(Context.POWER_SERVICE) as android.os.PowerManager
                    if (android.os.Build.VERSION.SDK_INT >= 23 && !pm.isIgnoringBatteryOptimizations(packageName)) {
                        android.app.AlertDialog.Builder(this, android.R.style.Theme_Material_Dialog_Alert)
                            .setTitle("Keep BMyDesk reachable")
                            .setMessage("Battery optimization can put the agent to sleep. Exempt it so your code stays reachable?")
                            .setPositiveButton("Exempt") { _, _ ->
                                runCatching { startActivity(Intent(android.provider.Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS, android.net.Uri.parse("package:$packageName"))) }
                            }
                            .setNegativeButton("Later", null)
                            .show()
                    }
                }
            }
        }
    }

    private fun ui(block: () -> Unit) = runOnUiThread(block)
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
                val r = api.register(
                    android.os.Build.MODEL ?: "android-device", "android",
                    getSharedPreferences("bmydesk", Context.MODE_PRIVATE).getString("member_token", null),
                    deviceId()
                )
                reg = r
                ui { codeView.text = r.code; setStatus("Connecting realtime…", "#f59e0b"); spinner.visibility = View.GONE }
                connectSignaling(r)
            } catch (e: Exception) {
                ui { codeView.text = "ERROR"; setStatus("Cannot reach server — retrying…", "#ef4444") }
                Thread.sleep(5000); register()
            }
        }
    }

    private fun connectSignaling(r: AgentApi.Registration) {
        signaling.listener = object : SignalingClient.Listener {
            override fun onConnected() { ui { setStatus("Realtime connected…", "#f59e0b") } }
            override fun onDisconnected() { ui { setStatus("Realtime lost — reconnecting…", "#ef4444") } }
            override fun onSubscribed() { ui { setStatus("Ready — share your code", "#22c55e") } }
            override fun onJoinRequest(name: String) {
                ui { reqName.text = "$name wants to view this device"; reqBox.visibility = View.VISIBLE }
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
        if (statusPollRunning) return
        statusPollRunning = true
        thread {
            Thread.sleep(4000)
            while (reg != null) {
                try {
                    api.drainSignals(r.code, r.agentToken).forEach { p -> if (sigNew(p) && sigFresh(p)) handleHostSignal(p) }
                    if (!signaling.hostSubscribed) {
                        val s = api.status(r.code, r.agentToken)
                        if (s?.get("ok")?.asBoolean == true) {
                            val vj = s.get("viewer_joined_at")?.asString
                            if (vj != null) {
                                val joinMs = java.time.Instant.parse(vj).toEpochMilli()
                                if (joinMs > respondedAtMs && System.currentTimeMillis() - joinMs < 120_000
                                    && reqBox.visibility != View.VISIBLE) {
                                    ui {
                                        reqName.text = "Someone wants to view this device"
                                        reqBox.visibility = View.VISIBLE
                                        setStatus("Connection request…", "#f59e0b")
                                    }
                                }
                            }
                        }
                    }
                } catch (e: Exception) {}
                Thread.sleep(3000)
            }
        }
    }

    // ── Accept flow: projection permission → service → capture ─────
    private fun acceptRequest() {
        reqBox.visibility = View.GONE
        respondedAtMs = System.currentTimeMillis()
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
            respondTo("reject")
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
        respondTo("accept")
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
        respondTo("reject")
        setStatus("Request rejected", "#f59e0b")
    }

    private fun endSession() {
        reg?.let { thread { api.end(it.code, it.agentToken) } }
        signaling.send("end", JsonObject())
        runCatching { startService(Intent(this, ScreenShareService::class.java).setAction(ScreenShareService.ACTION_STOP)) }
        runCatching { if (bound) unbindService(conn); bound = false }
        endBtn.visibility = View.GONE
        sessRow.visibility = View.GONE; chatPanel.visibility = View.GONE
        setStatus("Session ended", "#f59e0b")
    }

    // ── Remote Desk (viewer): join another device's session ────────
    private var vJoin: AgentApi.JoinResult? = null
    @Volatile private var viewerPollRunning = false

    private fun handleViewerSignal(j: AgentApi.JoinResult, p: JsonObject) {
        when (p.get("kind")?.asString) {
            "accept" -> ui { viewerStatus.text = "Accepted — starting stream…"; startViewerPeer(j) }
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
                    api.drainSignals(code, j.viewerToken).forEach { p -> if (sigNew(p) && sigFresh(p)) handleViewerSignal(j, p) }
                    if (remoteViewer == null) {
                        val st = api.status(code, j.viewerToken)
                        val stv = st?.get("status")?.asString
                        if (stv == "rejected") { ui { viewerStatus.text = "Host declined" }; thread { Thread.sleep(1500); ui { exitViewer() } } }
                        else if (stv == "ended") { ui { viewerStatus.text = "Host ended session" }; thread { Thread.sleep(1200); ui { exitViewer() } } }
                    }
                } catch (e: Exception) {}
                Thread.sleep(2000)
            }
            viewerPollRunning = false
        }
    }

    private fun connectRemote() {
        val code = remoteCodeInput.text.toString().trim().uppercase()
        if (code.length < 6) { remoteCodeInput.requestFocus(); return }
        ui {
            viewerPane.visibility = View.VISIBLE
            viewerStatus.text = "Joining $code…"
        }
        if (!signaling.isConnected) {
            ui { viewerStatus.text = "Agent channel not ready yet — wait for \"Ready\" then retry" }
            thread { Thread.sleep(2500); ui { exitViewer() } }
            return
        }
        thread {
            try {
                val j = api.join(code, remotePinInput.text.toString().trim().ifBlank { null })
                vJoin = j
                if (j.autoAccepted) { ui { viewerStatus.text = "Unattended access — starting stream…"; startViewerPeer(j) } }
                signaling.viewerRelay = { payload -> thread { api.signal(code, j.viewerToken, payload) } }
                startViewerPoll(j, code)
                signaling.connectViewer(j.channel, j.viewerToken, object : SignalingClient.Listener {
                    override fun onConnected() {}
                    override fun onDisconnected() { ui { viewerStatus.text = "Signaling lost — relay mode active" } }
                    override fun onJoinRequest(name: String) {}
                    override fun onSubscribed() {
                        signaling.sendViewer("join-request", JsonObject().apply {
                            addProperty("name", (android.os.Build.MODEL ?: "Android") + " (app)")
                        })
                        ui { viewerStatus.text = "Waiting for host approval…" }
                    }
                    override fun onJoinAccept() { ui { viewerStatus.text = "Accepted — starting stream…"; startViewerPeer(j) } }
                    override fun onJoinReject() { ui { viewerStatus.text = "Host declined" }; thread { Thread.sleep(1500); ui { exitViewer() } } }
                    override fun onSignal(p: JsonObject) { if (sigNew(p)) handleViewerSignal(j, p) }
                    override fun onEnd() { ui { viewerStatus.text = "Host ended session"; thread { Thread.sleep(1200); ui { exitViewer() } } } }
                })
            } catch (e: Exception) {
                ui { viewerStatus.text = "Join failed: ${e.message}"; }
                thread { Thread.sleep(2500); ui { exitViewer() } }
            }
        }
    }

    private fun startViewerPeer(j: AgentApi.JoinResult) {
        if (remoteViewer != null) return // accept may arrive via ws + queue both
        remoteViewer = RemoteViewer(this).apply {
            listener = object : RemoteViewer.Listener {
                override fun sendSignal(payload: JsonObject) = signaling.sendViewer("signal", payload)
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
            ctl.sink = object : CtlChannel.Sink {
                override fun onCtlOpen() = ui { vSessRow.visibility = View.VISIBLE; startClipSync() }
                override fun onChat(from: String, text: String) = ui { chatAppend(from, text, false) }
                override fun onClip(text: String) = clipSet(text)
                override fun onFile(name: String, data: ByteArray) = saveIncoming(name, data)
            }
        }
        remoteViewer!!.start(iceServersFrom(j.iceServers))
    }

    // Touch → input events (same JSON protocol as the web/Electron viewer)
    // + 2-finger pinch = local zoom, drag while zoomed = pan, double-tap resets.
    private var touchMoved = false
    private var lastMove = 0L
    private var vScale = 1f
    private var panX = 0f; private var panY = 0f
    private var lastTX = 0f; private var lastTY = 0f
    private fun bindViewerTouch() {
        val scaleDet = android.view.ScaleGestureDetector(this, object : android.view.ScaleGestureDetector.SimpleOnScaleGestureListener() {
            override fun onScale(d: android.view.ScaleGestureDetector): Boolean {
                vScale = (vScale * d.scaleFactor).coerceIn(1f, 5f)
                remoteSurface?.let { it.scaleX = vScale; it.scaleY = vScale }
                clampPan()
                return true
            }
        })
        val tapDet = android.view.GestureDetector(this, object : android.view.GestureDetector.SimpleOnGestureListener() {
            override fun onDoubleTap(e: android.view.MotionEvent): Boolean {
                vScale = 1f; panX = 0f; panY = 0f
                remoteSurface?.let { it.scaleX = 1f; it.scaleY = 1f; it.translationX = 0f; it.translationY = 0f }
                return true
            }
        })
        remoteSurface?.setOnTouchListener { _, ev ->
            scaleDet.onTouchEvent(ev); tapDet.onTouchEvent(ev)
            if (ev.pointerCount >= 2) { touchMoved = true; return@setOnTouchListener true } // pinch = not input
            val w = remoteSurface!!.width.toFloat().coerceAtLeast(1f)
            val h = remoteSurface!!.height.toFloat().coerceAtLeast(1f)
            when (ev.actionMasked) {
                android.view.MotionEvent.ACTION_DOWN -> { touchMoved = false; lastTX = ev.x; lastTY = ev.y }
                android.view.MotionEvent.ACTION_MOVE -> {
                    if (vScale > 1.01f) { // zoomed → pan locally instead of remote-drag
                        panX += ev.x - lastTX; panY += ev.y - lastTY; lastTX = ev.x; lastTY = ev.y
                        clampPan()
                        remoteSurface?.let { it.translationX = panX; it.translationY = panY }
                        touchMoved = true
                    } else {
                        touchMoved = true
                        val now = android.os.SystemClock.elapsedRealtime()
                        if (now - lastMove >= 33) {
                            lastMove = now
                            remoteViewer?.sendInput(JsonObject().apply {
                                addProperty("t", "move"); addProperty("x", ev.x / w); addProperty("y", ev.y / h)
                            })
                        }
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
    private fun clampPan() {
        remoteSurface?.let {
            val mx = it.width * (vScale - 1) / 2f; val my = it.height * (vScale - 1) / 2f
            panX = panX.coerceIn(-mx, mx); panY = panY.coerceIn(-my, my)
        }
    }

    private fun exitViewer() {
        runCatching { signaling.sendViewer("end", JsonObject()) }
        vJoin = null // stops the viewer poll loop
        remoteViewer?.stop(); remoteViewer = null
        signaling.disconnectViewer()
        runCatching { remoteSurface?.release() }
        vScale = 1f; panX = 0f; panY = 0f; keysRow.visibility = View.GONE
        viewerPane.visibility = View.GONE
        vSessRow.visibility = View.GONE; chatPanel.visibility = View.GONE
    }

    override fun onDestroy() {
        runCatching { endSession() }
        runCatching { exitViewer() }
        signaling.disconnect()
        super.onDestroy()
    }
}
