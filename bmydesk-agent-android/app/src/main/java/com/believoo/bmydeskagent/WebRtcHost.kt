package com.believoo.bmydeskagent

import android.content.Context
import android.content.Intent
import com.google.gson.Gson
import com.google.gson.JsonObject
import org.webrtc.*

/**
 * Host-side WebRTC: captures the device screen (MediaProjection), answers the
 * viewer's offer, and exchanges ICE over the signaling channel.
 */
class WebRtcHost(context: Context) {

    interface Listener {
        fun sendSignal(payload: JsonObject)
        fun onPeerConnected()
        fun onPeerDisconnected()
        fun onError(message: String)
    }

    var listener: Listener? = null
    private val gson = Gson()
    val ctl = CtlChannel(gson)

    private val egl = EglBase.create()
    private val factory: PeerConnectionFactory
    private var peer: PeerConnection? = null
    private var capturer: ScreenCapturerAndroid? = null
    private var videoSource: VideoSource? = null
    private var surfaceHelper: SurfaceTextureHelper? = null
    private var localTrack: VideoTrack? = null
    private var iceQueue = mutableListOf<IceCandidate>()
    private var remoteSet = false

    init {
        PeerConnectionFactory.initialize(
            PeerConnectionFactory.InitializationOptions.builder(context)
                .setEnableInternalTracer(false).createInitializationOptions()
        )
        factory = PeerConnectionFactory.builder()
            .setVideoEncoderFactory(DefaultVideoEncoderFactory(egl.eglBaseContext, true, true))
            .setVideoDecoderFactory(DefaultVideoDecoderFactory(egl.eglBaseContext))
            .createPeerConnectionFactory()
    }

    fun startCapture(resultData: Intent, width: Int, height: Int, fps: Int = 15) {
        capturer = ScreenCapturerAndroid(resultData, object : android.media.projection.MediaProjection.Callback() {
            override fun onStop() { listener?.onError("Screen capture stopped") }
        })
        surfaceHelper = SurfaceTextureHelper.create("CaptureThread", egl.eglBaseContext)
        videoSource = factory.createVideoSource(true)
        capturer!!.initialize(surfaceHelper, null, videoSource!!.capturerObserver)
        capturer!!.startCapture(width, height, fps)
        localTrack = factory.createVideoTrack("screen", videoSource)
    }

    fun handleOffer(offerSdp: String, ice: List<PeerConnection.IceServer>) {
        val constraints = MediaConstraints()
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
                        val m = gson.fromJson(String(bytes), JsonObject::class.java)
                        if (m.get("t")?.asString == "ping") {
                            m.addProperty("t", "pong")
                            dc.send(DataChannel.Buffer(java.nio.ByteBuffer.wrap(gson.toJson(m).toByteArray()), false))
                        }
                    }
                })
            }
            override fun onRenegotiationNeeded() {}
            override fun onAddTrack(r: RtpReceiver?, s: Array<out MediaStream>?) {}
            override fun onTrack(t: RtpTransceiver?) {}
        }) ?: run { listener?.onError("peer create failed"); return }

        localTrack?.let { peer!!.addTrack(it, listOf("screen")) }
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
                        o.addProperty("sdp", peer!!.localDescription.description)
                        listener?.sendSignal(o)
                    }
                })
            }
        }, constraints)
    }

    fun addIce(c: JsonObject) {
        val cand = IceCandidate(c.get("sdpMid")?.asString, c.get("sdpMLineIndex")?.asInt ?: 0, c.get("candidate")?.asString)
        if (remoteSet && peer != null) peer!!.addIceCandidate(cand) else iceQueue.add(cand)
    }

    fun stop() {
        ctl.close()
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
}
