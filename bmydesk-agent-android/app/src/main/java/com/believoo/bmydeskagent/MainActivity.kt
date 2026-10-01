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
        register()
    }

    // ── UI ─────────────────────────────────────────────────────────
    private fun buildUi() {
        val bg = Color.parseColor("#0b1220")
        val panel = Color.parseColor("#0f172a")
        val rose = Color.parseColor("#fb7185")
        val green = Color.parseColor("#22c55e")

        val root = ScrollView(this).apply { setBackgroundColor(bg) }
        val col = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER_HORIZONTAL
            setPadding(48, 60, 48, 40)
        }
        root.addView(col)

        col.addView(TextView(this).apply {
            text = "BMyDesk Agent"
            setTextColor(Color.WHITE); textSize = 22f; typeface = Typeface.DEFAULT_BOLD
            gravity = Gravity.CENTER
        })
        col.addView(TextView(this).apply {
            text = "Share your screen with a session code"
            setTextColor(Color.parseColor("#94a3b8")); textSize = 12f; gravity = Gravity.CENTER
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

        col.addView(TextView(this).apply {
            text = "v" + BuildConfig.VERSION_NAME
            setTextColor(Color.parseColor("#475569")); textSize = 10f; gravity = Gravity.CENTER
            setPadding(0, 20, 0, 0)
        })

        setContentView(root)
    }

    private fun ui(block: () -> Unit) = runOnUiThread(block)
    private fun setStatus(t: String, color: String = "#94a3b8") {
        statusView.text = t; statusView.setTextColor(Color.parseColor(color))
    }

    // ── Register + signaling ───────────────────────────────────────
    private fun register() {
        thread {
            try {
                val r = api.register(android.os.Build.MODEL ?: "android-device", "android")
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

    override fun onDestroy() {
        runCatching { endSession() }
        signaling.disconnect()
        super.onDestroy()
    }
}
