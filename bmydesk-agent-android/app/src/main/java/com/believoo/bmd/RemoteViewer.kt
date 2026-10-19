package com.believoo.bmd

import android.content.Context
import com.google.gson.Gson
import com.google.gson.JsonObject
import org.webrtc.*
import org.webrtc.audio.JavaAudioDeviceModule

/**
 * Viewer-side WebRTC: offers to receive the remote screen, renders it onto a
 * SurfaceViewRenderer, and sends touch/keyboard input back over a DataChannel.
 */
class RemoteViewer(context: Context) {

    interface Listener {
        fun sendSignal(payload: JsonObject)
        fun onPeerConnected()
        fun onPeerDisconnected()
        fun onPeerUnstable() {} // transient DISCONNECTED — heals on its own
        fun onTrack(track: VideoTrack)
        /** remote (host) camera track — the picture-in-picture call feed */
        fun onPipTrack(track: VideoTrack) {}
        fun onError(message: String)
    }

    var listener: Listener? = null
    private val gson = Gson()
    val ctl = CtlChannel(gson)

    val egl: EglBase = EglBase.create()
    private var adm: JavaAudioDeviceModule? = null
    private val factory: PeerConnectionFactory
    private var peer: PeerConnection? = null
    private var dc: DataChannel? = null
    private var iceQueue = mutableListOf<IceCandidate>()
    private var remoteSet = false

    // Call plumbing — transceivers are negotiated UP FRONT (sendrecv, no
    // track), so starting/stopping mic/camera later is replaceTrack(), never
    // a renegotiation. screenT carries the remote screen; callCamT the PIP.
    private var screenT: RtpTransceiver? = null
    private var callAudioT: RtpTransceiver? = null
    private var callCamT: RtpTransceiver? = null
    private var micTrack: AudioTrack? = null
    private var micSource: AudioSource? = null
    private var camTrack: VideoTrack? = null
    private var camName: String? = null
    private var camCapturer: Camera2Capturer? = null
    private var camSource: VideoSource? = null
    private var camHelper: SurfaceTextureHelper? = null
    private val appContext: Context = context.applicationContext

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

    fun start(ice: List<PeerConnection.IceServer>) {
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
                    PeerConnection.PeerConnectionState.DISCONNECTED -> listener?.onPeerUnstable()
                    PeerConnection.PeerConnectionState.FAILED,
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
            override fun onDataChannel(d: DataChannel) {}
            override fun onRenegotiationNeeded() {}
            override fun onAddTrack(r: RtpReceiver?, s: Array<out MediaStream>?) {}
            override fun onTrack(t: RtpTransceiver?) {
                val tr = t?.receiver?.track() ?: return
                // === on the Java proxy objects is NOT reliable — libwebrtc can
                // hand a different RtpTransceiver instance for the same native
                // slot (screen once rendered into the tiny PIP). Match by mid
                // or by the recvonly direction we created the slot with.
                val isScreen = t === screenT
                    || (t.mid != null && screenT?.mid != null && t.mid == screenT?.mid)
                    || (t.direction == RtpTransceiver.RtpTransceiverDirection.RECV_ONLY && tr.kind() == "video")
                when {
                    isScreen -> (tr as? VideoTrack)?.let { listener?.onTrack(it) }
                    tr.kind() == "audio" -> tr.setEnabled(true) // call audio plays via the audio device module
                    // the empty sendrecv cam slot fires onTrack with a pending
                    // track that NEVER draws — gate on the first real frame or
                    // the PIP shows as a dead black box on every connect
                    tr.kind() == "video" -> (tr as? VideoTrack)?.let { vt ->
                        vt.addSink(object : org.webrtc.VideoSink {
                            private var fired = false
                            override fun onFrame(f: VideoFrame) {
                                if (fired) return
                                fired = true
                                vt.removeSink(this)
                                listener?.onPipTrack(vt)
                            }
                        })
                    }
                }
            }
        }) ?: run { listener?.onError("peer create failed"); return }

        // slot order in the offer: [0]=screen recv, [1]=call audio, [2]=camera
        screenT = peer!!.addTransceiver(MediaStreamTrack.MediaType.MEDIA_TYPE_VIDEO,
            RtpTransceiver.RtpTransceiverInit(RtpTransceiver.RtpTransceiverDirection.RECV_ONLY))
        callAudioT = peer!!.addTransceiver(MediaStreamTrack.MediaType.MEDIA_TYPE_AUDIO,
            RtpTransceiver.RtpTransceiverInit(RtpTransceiver.RtpTransceiverDirection.SEND_RECV))
        callCamT = peer!!.addTransceiver(MediaStreamTrack.MediaType.MEDIA_TYPE_VIDEO,
            RtpTransceiver.RtpTransceiverInit(RtpTransceiver.RtpTransceiverDirection.SEND_RECV))

        val init = DataChannel.Init()
        init.ordered = false; init.maxRetransmits = 0
        dc = peer!!.createDataChannel("input", init)
        ctl.attach(peer!!.createDataChannel("ctl", DataChannel.Init())) // reliable — chat/clip/file

        val constraints = MediaConstraints()
        // no OfferToReceive* flags — the explicit transceivers above carry
        // exactly the m-lines we want (extra flags would mint duplicate slots)

        peer!!.createOffer(object : WebRtcHost.SdpAdapter() {
            override fun onCreateSuccess(offer: SessionDescription?) {
                peer!!.setLocalDescription(object : WebRtcHost.SdpAdapter() {
                    override fun onSetSuccess() {
                        val o = JsonObject()
                        o.addProperty("kind", "offer")
                        o.addProperty("sdp", sanitizeSdp(peer!!.localDescription.description))
                        listener?.sendSignal(o)
                    }
                }, offer)
            }
            override fun onCreateFailure(err: String?) { listener?.onError("offer: $err") }
        }, constraints)
    }

    fun onSignal(m: JsonObject) {
        when (m.get("kind").s()) {
            "answer" -> applyAnswer(m.get("sdp").asString)
            "ice" -> m.getAsJsonObject("candidate")?.let { addIce(it) }
        }
    }

    // Retry once with exotic attribute lines dropped — same defense as host.
    private fun applyAnswer(sdp: String, retried: Boolean = false) {
        peer?.setRemoteDescription(object : WebRtcHost.SdpAdapter() {
            override fun onSetSuccess() {
                remoteSet = true
                iceQueue.forEach { peer?.addIceCandidate(it) }
                iceQueue.clear()
            }
            override fun onSetFailure(err: String?) {
                if (!retried) {
                    val cleaned = sdp.replace("\r\n", "\n").split("\n")
                        .filter { it.isNotBlank() && !it.startsWith("a=max-message-size") }
                        .joinToString("\r\n")
                    if (cleaned != sdp) { applyAnswer(cleaned, true); return }
                }
                listener?.onError("answer: $err")
            }
        }, SessionDescription(SessionDescription.Type.ANSWER, sdp))
    }

    fun addIce(c: JsonObject) {
        val cand = IceCandidate(c.get("sdpMid").s(), c.get("sdpMLineIndex")?.asInt ?: 0, c.get("candidate").s())
        if (remoteSet && peer != null) peer!!.addIceCandidate(cand) else iceQueue.add(cand)
    }

    /** Nominated pair RTT in ms (0 if unknown) — for the viewer's link badge. */
    fun rtt(cb: (Int) -> Unit) {
        val p = peer ?: return
        runCatching {
            p.getStats { report ->
                val ms = report?.statsMap?.values
                    ?.filter { it.type == "candidate-pair" }
                    ?.mapNotNull { (it.members["currentRoundTripTime"] as? Double)?.times(1000)?.toInt() }
                    ?.firstOrNull() ?: 0
                cb(ms)
            }
        }
    }

    fun sendInput(m: JsonObject) {
        val d = dc ?: return
        if (d.state() == DataChannel.State.OPEN) {
            runCatching { d.send(DataChannel.Buffer(java.nio.ByteBuffer.wrap(gson.toJson(m).toByteArray()), false)) }
        }
    }

    // ── Voice/video call (Zoom-style, inside the remote session) ──
    /** Mic toggle — lazily creates the audio track; setEnabled mutes without
        touching the sender so unmute is instant and needs no renegotiation. */
    fun setMic(on: Boolean): Boolean {
        val t = callAudioT ?: return false // old peer — no call slot
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
        ctl.sendCallState("voice", on)
        return true
    }

    /** Camera toggle — sends our camera as a picture-in-picture call feed. */
    fun setCam(on: Boolean): Boolean {
        val t = callCamT ?: return false
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
            camCapturer!!.startCapture(640, 480, 15) // restart after an off toggle
            camTrack?.setEnabled(true)
        }
        camTrack?.let { t.sender.setTrack(if (on) it else null, true) }
        if (!on) {
            // release the hardware — track-detach alone left the capturer
            // running and the camera indicator stuck on
            camTrack?.setEnabled(false)
            runCatching { camCapturer?.stopCapture() }
        }
        ctl.sendCallState("camera", on)
        return true
    }

    fun callActive(): Boolean = micTrack?.enabled() == true || camTrack?.enabled() == true

    /** Our own camera track — for the local self-preview PIP. */
    fun camVideoTrack(): VideoTrack? = camTrack

    fun stop() {
        ctl.close()
        runCatching { camCapturer?.stopCapture() }
        runCatching { micSource?.dispose(); camSource?.dispose(); camHelper?.dispose(); adm?.release() }
        adm = null
        camCapturer = null; micTrack = null; camTrack = null; micSource = null; camSource = null
        runCatching { dc?.close() }
        runCatching { peer?.close() }
        dc = null; peer = null; remoteSet = false; iceQueue.clear()
        screenT = null; callAudioT = null; callCamT = null
    }

    // Strip SDP lines some libwebrtc/Chrome builds fail to parse.
    private fun sanitizeSdp(s: String): String =
        (s.split(Regex("\r?\n")).filter { it.isNotEmpty() }.joinToString("\r\n") + "\r\n")
}
