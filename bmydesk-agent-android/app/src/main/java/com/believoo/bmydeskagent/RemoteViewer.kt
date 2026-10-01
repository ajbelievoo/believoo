package com.believoo.bmydeskagent

import android.content.Context
import com.google.gson.Gson
import com.google.gson.JsonObject
import org.webrtc.*

/**
 * Viewer-side WebRTC: offers to receive the remote screen, renders it onto a
 * SurfaceViewRenderer, and sends touch/keyboard input back over a DataChannel.
 */
class RemoteViewer(context: Context) {

    interface Listener {
        fun sendSignal(payload: JsonObject)
        fun onPeerConnected()
        fun onPeerDisconnected()
        fun onTrack(track: VideoTrack)
        fun onError(message: String)
    }

    var listener: Listener? = null
    private val gson = Gson()

    val egl: EglBase = EglBase.create()
    private val factory: PeerConnectionFactory
    private var peer: PeerConnection? = null
    private var dc: DataChannel? = null
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
            override fun onDataChannel(d: DataChannel) {}
            override fun onRenegotiationNeeded() {}
            override fun onAddTrack(r: RtpReceiver?, s: Array<out MediaStream>?) {}
            override fun onTrack(t: RtpTransceiver?) {
                (t?.receiver?.track() as? VideoTrack)?.let { listener?.onTrack(it) }
            }
        }) ?: run { listener?.onError("peer create failed"); return }

        peer!!.addTransceiver(MediaStreamTrack.MediaType.MEDIA_TYPE_VIDEO,
            RtpTransceiver.RtpTransceiverInit(RtpTransceiver.RtpTransceiverDirection.RECV_ONLY))

        val init = DataChannel.Init()
        init.ordered = false; init.maxRetransmits = 0
        dc = peer!!.createDataChannel("input", init)

        val constraints = MediaConstraints()
        constraints.mandatory.add(MediaConstraints.KeyValuePair("OfferToReceiveVideo", "true"))
        constraints.mandatory.add(MediaConstraints.KeyValuePair("OfferToReceiveAudio", "true"))

        peer!!.createOffer(object : WebRtcHost.SdpAdapter() {
            override fun onCreateSuccess(offer: SessionDescription?) {
                peer!!.setLocalDescription(object : WebRtcHost.SdpAdapter() {
                    override fun onSetSuccess() {
                        val o = JsonObject()
                        o.addProperty("kind", "offer")
                        o.addProperty("sdp", peer!!.localDescription.description)
                        listener?.sendSignal(o)
                    }
                }, offer)
            }
            override fun onCreateFailure(err: String?) { listener?.onError("offer: $err") }
        }, constraints)
    }

    fun onSignal(m: JsonObject) {
        when (m.get("kind")?.asString) {
            "answer" -> peer?.setRemoteDescription(object : WebRtcHost.SdpAdapter() {
                override fun onSetSuccess() {
                    remoteSet = true
                    iceQueue.forEach { peer?.addIceCandidate(it) }
                    iceQueue.clear()
                }
                override fun onSetFailure(err: String?) { listener?.onError("answer: $err") }
            }, SessionDescription(SessionDescription.Type.ANSWER, m.get("sdp").asString))
            "ice" -> m.getAsJsonObject("candidate")?.let { addIce(it) }
        }
    }

    fun addIce(c: JsonObject) {
        val cand = IceCandidate(c.get("sdpMid")?.asString, c.get("sdpMLineIndex")?.asInt ?: 0, c.get("candidate")?.asString)
        if (remoteSet && peer != null) peer!!.addIceCandidate(cand) else iceQueue.add(cand)
    }

    fun sendInput(m: JsonObject) {
        val d = dc ?: return
        if (d.state() == DataChannel.State.OPEN) {
            runCatching { d.send(DataChannel.Buffer(java.nio.ByteBuffer.wrap(gson.toJson(m).toByteArray()), false)) }
        }
    }

    fun stop() {
        runCatching { dc?.close() }
        runCatching { peer?.close() }
        dc = null; peer = null; remoteSet = false; iceQueue.clear()
    }
}
