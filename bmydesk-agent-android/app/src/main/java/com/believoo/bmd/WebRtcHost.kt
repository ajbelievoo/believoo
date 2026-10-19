package com.believoo.bmd

import android.content.Context
import android.content.Intent
import com.google.gson.Gson
import com.google.gson.JsonObject
import org.webrtc.*
import org.webrtc.audio.JavaAudioDeviceModule

/**
 * Host-side WebRTC: captures the device screen (MediaProjection), answers the
 * viewer's offer, and exchanges ICE over the signaling channel.
 */
class WebRtcHost(context: Context) {
    private val appContext = context.applicationContext

    interface Listener {
        fun sendSignal(payload: JsonObject)
        fun onPeerConnected()
        fun onPeerDisconnected()
        /** viewer's camera — picture-in-picture feed on the host side */
        fun onPipTrack(track: VideoTrack) {}
        fun onError(message: String)
    }

    var listener: Listener? = null
    private val gson = Gson()
    val ctl = CtlChannel(gson)
    private val inputDropWarned = java.util.concurrent.atomic.AtomicBoolean(false)

    private val egl = EglBase.create()
    private val factory: PeerConnectionFactory
    private var peer: PeerConnection? = null
    private var capturer: ScreenCapturerAndroid? = null
    private var videoSource: VideoSource? = null
    private var surfaceHelper: SurfaceTextureHelper? = null
    private var localTrack: VideoTrack? = null
    private var iceQueue = mutableListOf<IceCandidate>()
    private var remoteSet = false
    private var capW = 0
    private var capH = 0

    // call media — the viewer's offer carries sendrecv audio+video slots;
    // toggling is sender.setTrack() on the matching transceiver, no reneg.
    private var adm: JavaAudioDeviceModule? = null
    private var micTrack: AudioTrack? = null
    private var micSource: AudioSource? = null
    private var camTrack: VideoTrack? = null
    private var camCapturer: Camera2Capturer? = null
    private var camSource: VideoSource? = null
    private var camHelper: SurfaceTextureHelper? = null

    init {
        PeerConnectionFactory.initialize(
            PeerConnectionFactory.InitializationOptions.builder(context)
                .setEnableInternalTracer(false).createInitializationOptions()
        )
        adm = JavaAudioDeviceModule.builder(context)
            .setUseHardwareAcousticEchoCanceler(true)
            .setUseHardwareNoiseSuppressor(true)
            .createAudioDeviceModule()
        factory = PeerConnectionFactory.builder()
            .setAudioDeviceModule(adm!!)
            .setVideoEncoderFactory(DefaultVideoEncoderFactory(egl.eglBaseContext, true, true))
            .setVideoDecoderFactory(DefaultVideoDecoderFactory(egl.eglBaseContext))
            .createPeerConnectionFactory()
    }

    @Volatile private var stopping = false

    fun startCapture(resultData: Intent, width: Int, height: Int, fps: Int = 15) {
        stopping = false
        try {
            capturer = ScreenCapturerAndroid(resultData, object : android.media.projection.MediaProjection.Callback() {
                // onStop also fires on our own stop() — only surface it as an
                // error when the SYSTEM revoked the projection mid-session
                override fun onStop() {
                    if (!stopping) listener?.onError("Screen capture stopped")
                }
            })
            surfaceHelper = SurfaceTextureHelper.create("CaptureThread", egl.eglBaseContext)
            videoSource = factory.createVideoSource(true)
            capturer!!.initialize(surfaceHelper, appContext, videoSource!!.capturerObserver)
            // stale/reused projection tokens + dead virtual displays throw
            // here — without this catch the host looks "connected" while the
            // viewer watches black
            capturer!!.startCapture(width, height, fps)
        } catch (e: Exception) {
            listener?.onError("Screen capture failed: ${e.message}")
            return
        }
        capW = width; capH = height
        localTrack = factory.createVideoTrack("screen", videoSource)
    }

    // Post-connect sanity: is the encoder actually emitting? A dead
    // MediaProjection/virtual-display produces ZERO encoded frames while the
    // link itself stays green — the classic "connected but black" state.
    fun watchEncoding() {
        val p = peer ?: return
        android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
            runCatching {
                p.getStats { report ->
                    val encoded = report?.statsMap?.values
                        ?.filter { it.type == "outbound-rtp" }
                        ?.mapNotNull { (it.members["framesEncoded"] as? Number)?.toLong() }
                        ?.sum() ?: 0L
                    android.util.Log.d("BMyDesk", "host framesEncoded=$encoded")
                    if (encoded == 0L)
                        listener?.onError("Screen capture produced no frames — end the session and accept again")
                }
            }
        }, 12000)
    }

    /** true once the capture pipeline produced a screen track to send. */
    fun hasTrack(): Boolean = localTrack != null

    fun handleOffer(offerSdp: String, ice: List<PeerConnection.IceServer>) {
        // answering with no screen track = the viewer connects to silence
        // (black forever). The service queues offers until capture is up —
        // reaching here with no track is a bug, surface it loudly.
        if (localTrack == null) { listener?.onError("Screen capture isn't running — accept again and tap 'Start now'"); return }
        val constraints = MediaConstraints()
        // A reconnecting viewer sends a FRESH offer — the old peer (probably a
        // dead/failed ICE) must be disposed or two peers leak and fight over
        // the same capture, freezing the stream.
        peer?.let { old ->
            runCatching { old.close() }
            iceQueue.clear(); remoteSet = false
        }
        peer = factory.createPeerConnection(ice, object : PeerConnection.Observer {
            override fun onIceCandidate(c: IceCandidate) {
                val o = JsonObject()
                o.addProperty("kind", "ice")
                val cand = JsonObject()
                cand.addProperty("candidate", c.sdp)
                cand.addProperty("sdpMid", c.sdpMid)
                cand.addProperty("sdpMLineIndex", c.sdpMLineIndex)
                o.add("candidate", cand)
                listener?.sendSignal(o)
            }
            override fun onConnectionChange(state: PeerConnection.PeerConnectionState?) {
                when (state) {
                    PeerConnection.PeerConnectionState.CONNECTED -> listener?.onPeerConnected()
                    PeerConnection.PeerConnectionState.FAILED,
                    PeerConnection.PeerConnectionState.DISCONNECTED,
                    PeerConnection.PeerConnectionState.CLOSED -> listener?.onPeerDisconnected()
                    else -> {}
                }
            }
            override fun onSignalingChange(s: PeerConnection.SignalingState?) {}
            override fun onIceConnectionChange(s: PeerConnection.IceConnectionState?) {}
            override fun onIceConnectionReceivingChange(b: Boolean) {}
            override fun onIceGatheringChange(s: PeerConnection.IceGatheringState?) {}
            override fun onIceCandidatesRemoved(c: Array<out IceCandidate>?) {}
            override fun onAddStream(s: MediaStream?) {}
            override fun onRemoveStream(s: MediaStream?) {}
            override fun onDataChannel(dc: DataChannel) {
                if (dc.label() == "ctl") { ctl.attach(dc); return }
                dc.registerObserver(object : DataChannel.Observer {
                    override fun onBufferedAmountChange(p0: Long) {}
                    override fun onStateChange() {}
                    override fun onMessage(buf: DataChannel.Buffer) {
                        val bytes = ByteArray(buf.data.remaining()); buf.data.get(bytes)
                        val m = runCatching { gson.fromJson(String(bytes), JsonObject::class.java) }.getOrNull() ?: return
                        if (m.get("t").s() == "ping") {
                            m.addProperty("t", "pong")
                            dc.send(DataChannel.Buffer(java.nio.ByteBuffer.wrap(gson.toJson(m).toByteArray()), false))
                        } else {
                            // remote-control injection — AccessibilityService
                            // turns viewer touch/keys into real gestures
                            val dm = appContext.resources.displayMetrics
                            val svc = RemoteControlService.instance
                            if (svc == null) {
                                // input arrived but no accessibility service —
                                // means the user hasn't enabled it (or the OEM
                                // killed it). Log once so it's diagnosable.
                                if (inputDropWarned.compareAndSet(false, true))
                                    android.util.Log.w("BMyDesk",
                                        "remote input arriving but RemoteControlService is null — accessibility not enabled?")
                            } else svc.handleInput(m, dm.widthPixels, dm.heightPixels)
                        }
                    }
                })
            }
            override fun onRenegotiationNeeded() {}
            override fun onAddTrack(r: RtpReceiver?, s: Array<out MediaStream>?) {}
            override fun onTrack(t: RtpTransceiver?) {
                val tr = t?.receiver?.track() ?: return
                // viewer mic → auto-played by the audio device module;
                // viewer camera → PIP surface on the host side
                // gate on the first real frame — the viewer's sendrecv cam
                // slot creates a pending track that draws nothing until they
                // actually enable their camera
                if (tr.kind() == "video" && tr !== localTrack) (tr as? VideoTrack)?.let { vt ->
                    vt.addSink(object : org.webrtc.VideoSink {
                        private var fired = false
                        override fun onFrame(f: org.webrtc.VideoFrame) {
                            if (fired) return
                            fired = true
                            vt.removeSink(this)
                            listener?.onPipTrack(vt)
                        }
                    })
                }
            }
        }) ?: run { listener?.onError("peer create failed"); return }

        localTrack?.let { peer!!.addTrack(it, listOf("screen")) }
        // screen content: high bitrate + never trade resolution for fps
        runCatching {
            peer!!.senders.firstOrNull { it.track()?.kind() == "video" }?.let { snd ->
                val prm = snd.parameters
                prm.encodings.forEach { it.maxBitrateBps = 8_000_000 }
                prm.degradationPreference = RtpParameters.DegradationPreference.MAINTAIN_RESOLUTION
                snd.parameters = prm
            }
        }
        applyOffer(offerSdp, constraints)
    }

    // Some peers emit SDP attributes this libwebrtc build can't parse
    // (a=max-message-size outside m=application, LF endings). Retry once
    // with exotic attribute lines dropped before giving up.
    private fun applyOffer(offerSdp: String, constraints: MediaConstraints, retried: Boolean = false) {
        peer!!.setRemoteDescription(object : SdpAdapter() {
            override fun onSetSuccess() = onOfferApplied(constraints)
            override fun onSetFailure(err: String?) {
                if (!retried) {
                    val cleaned = offerSdp.replace("\r\n", "\n").split("\n")
                        .filter { it.isNotBlank() && !it.startsWith("a=max-message-size") }
                        .joinToString("\r\n")
                    if (cleaned != offerSdp) { applyOffer(cleaned, constraints, true); return }
                }
                listener?.onError("setRemote: $err")
            }
        }, SessionDescription(SessionDescription.Type.OFFER, offerSdp))
    }

    private fun onOfferApplied(constraints: MediaConstraints) {
        remoteSet = true
        iceQueue.forEach { peer!!.addIceCandidate(it) }
        iceQueue.clear()
        peer!!.createAnswer(object : SdpAdapter() {
            override fun onCreateSuccess(answer: SessionDescription?) {
                peer!!.setLocalDescription(object : SdpAdapter() {
                    override fun onSetSuccess() {
                        val o = JsonObject()
                        o.addProperty("kind", "answer")
                        o.addProperty("sdp", sanitizeSdp(peer!!.localDescription.description))
                        listener?.sendSignal(o)
                    }
                })
            }
        }, constraints)
    }

    /** The peer's shared GL context — decoded/captured frames are texture
        buffers bound to THIS context group. Any renderer surface (PIP,
        mini-view) init()ed with a different EglBase draws them as black. */
    fun eglContext(): EglBase.Context = egl.eglBaseContext

    /** Viewer quality toggle — retune the capture instead of leaving it hot. */
    fun setQuality(mode: String) {
        if (capW == 0 || capH == 0) return
        // capture cap is 1440px — 'auto' rides it at 20fps; 'fhd' upscales
        // the virtual display past the cap for sharpness when the link allows
        val (scale, fps, kbps) = when (mode) {
            "sd" -> Triple(0.5f, 8, 1200)
            "hd" -> Triple(1.0f, 24, 8000)
            "fhd" -> Triple(1.5f, 30, 14000)
            else -> Triple(1.0f, 20, 5000) // auto — light enough to stay fluid
        }
        runCatching {
            capturer?.changeCaptureFormat(
                (capW * scale).toInt().coerceAtLeast(320),
                (capH * scale).toInt().coerceAtLeast(240), fps)
        }
        // cap the encoder bitrate to match — 'fhd' needs headroom or text
        // smears, 'sd' must stay small or the point of the mode is lost.
        runCatching {
            peer?.senders?.firstOrNull { it.track()?.kind() == "video" }?.let { snd ->
                val prm = snd.parameters
                prm.encodings.forEach { it.maxBitrateBps = kbps * 1000 }
                snd.parameters = prm
            }
        }
    }

    fun addIce(c: JsonObject) {
        val cand = IceCandidate(c.get("sdpMid").s(), c.get("sdpMLineIndex")?.asInt ?: 0, c.get("candidate").s())
        if (remoteSet && peer != null) peer!!.addIceCandidate(cand) else iceQueue.add(cand)
    }

    // ── Voice/video call — Zoom-style, inside the remote session ──
    // Finds the offer's call slots: the audio transceiver, and the video
    // transceiver that is NOT carrying the screen track.
    private fun callAudioTransceiver(): RtpTransceiver? =
        peer?.transceivers?.firstOrNull { it.mediaType == MediaStreamTrack.MediaType.MEDIA_TYPE_AUDIO }
    private fun callCamTransceiver(): RtpTransceiver? {
        val screenSender = peer?.senders?.firstOrNull { it.track() === localTrack }
        return peer?.transceivers?.firstOrNull {
            it.mediaType == MediaStreamTrack.MediaType.MEDIA_TYPE_VIDEO && it.sender !== screenSender
        }
    }

    fun setMic(on: Boolean): Boolean {
        val t = callAudioTransceiver() ?: return false
        // defensive: if negotiation left the audio slot unable to send, our
        // mic track would attach but never hit the wire — force sendrecv.
        runCatching { t.direction = RtpTransceiver.RtpTransceiverDirection.SEND_RECV }
        if (on) {
            if (micTrack == null) {
                micSource = factory.createAudioSource(MediaConstraints())
                micTrack = factory.createAudioTrack("mic0", micSource)
            }
            micTrack?.let { t.sender.setTrack(it, true); it.setEnabled(true) }
        } else {
            // detach + dispose — a muted-but-attached source keeps the mic
            // hardware busy (green dot stays on) and burns battery
            t.sender.setTrack(null, true)
            micTrack?.setEnabled(false)
            runCatching { micTrack?.dispose(); micSource?.dispose() }
            micTrack = null; micSource = null
        }
        android.util.Log.d("BMyDesk", "host setMic on=$on dir=${t.direction} current=${t.currentDirection}")
        ctl.sendCallState("voice", on)
        return true
    }

    private var camName: String? = null

    fun setCam(on: Boolean): Boolean {
        val t = callCamTransceiver() ?: return false
        if (on && camTrack == null) {
            try {
                camHelper = camHelper ?: SurfaceTextureHelper.create("CamThread", egl.eglBaseContext)
                camSource = factory.createVideoSource(false)
                val camEnum = Camera2Enumerator(appContext)
                val camName = camEnum.deviceNames.firstOrNull { camEnum.isFrontFacing(it) }
                    ?: camEnum.deviceNames.firstOrNull() ?: return false
                this.camName = camName
                camCapturer = Camera2Capturer(appContext, camName, null)
                camCapturer!!.initialize(camHelper, appContext, camSource!!.capturerObserver)
                camCapturer!!.startCapture(640, 480, 15)
                camTrack = factory.createVideoTrack("cam0", camSource)
            } catch (e: Exception) { listener?.onError("camera: ${e.message}"); return false }
        } else if (on && camCapturer != null) {
            // restart a stopped capturer (toggled off → on in one session)
            camCapturer!!.startCapture(640, 480, 15)
            camTrack?.setEnabled(true)
        }
        camTrack?.let { t.sender.setTrack(if (on) it else null, true) }
        if (!on) {
            // actually RELEASE the camera — just detaching the track left the
            // capturer running, so the OS camera indicator stayed on forever
            camTrack?.setEnabled(false)
            runCatching { camCapturer?.stopCapture() }
        }
        ctl.sendCallState("camera", on)
        return true
    }

    /** Our own camera track — for the local self-preview PIP. */
    fun camVideoTrack(): VideoTrack? = camTrack

    fun stop() {
        stopping = true
        ctl.close()
        runCatching { camCapturer?.stopCapture() }
        runCatching { micSource?.dispose(); camSource?.dispose(); camHelper?.dispose(); adm?.release() }
        adm = null
        camCapturer = null; micTrack = null; camTrack = null; micSource = null; camSource = null; camName = null
        runCatching { capturer?.stopCapture() }
        runCatching { peer?.close() }
        capturer = null; peer = null; remoteSet = false; iceQueue.clear()
    }

    abstract class SdpAdapter : SdpObserver {
        override fun onCreateSuccess(p0: SessionDescription?) {}
        override fun onSetSuccess() {}
        override fun onCreateFailure(p0: String?) {}
        override fun onSetFailure(p0: String?) {}
    }

    // Strip SDP lines some libwebrtc/Chrome builds fail to parse.
    private fun sanitizeSdp(s: String): String =
        (s.split(Regex("\r?\n")).filter { it.isNotEmpty() }.joinToString("\r\n") + "\r\n")
}
