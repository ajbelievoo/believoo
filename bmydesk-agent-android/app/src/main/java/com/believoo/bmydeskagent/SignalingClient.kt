package com.believoo.bmydeskagent

import com.google.gson.Gson
import com.google.gson.JsonObject
import com.pusher.client.Pusher
import com.pusher.client.PusherOptions
import com.pusher.client.channel.PrivateChannel
import com.pusher.client.channel.PrivateChannelEventListener
import com.pusher.client.channel.PusherEvent
import com.pusher.client.connection.ConnectionEventListener
import com.pusher.client.connection.ConnectionState
import com.pusher.client.connection.ConnectionStateChange
import com.pusher.client.ChannelAuthorizer
import java.io.OutputStreamWriter
import java.net.HttpURLConnection
import java.net.URL
import java.util.concurrent.ConcurrentHashMap

/**
 * Reverb (Pusher-protocol) signaling for the agent — mirrors the Electron
 * renderer: subscribes to private-remote-agent.<CODE>, receives
 * client-join-request / client-signal / client-end, replies with
 * client-join-accept / client-join-reject / client-signal / client-end.
 */
class SignalingClient(
    private val appKey: String = "zenjc9spcwqz8nzdzvtn",
    private val wsHost: String = "believoo.com",
    private val wssPort: Int = 443,
    private val authEndpoint: String = "https://bmydesk.believoo.com/api/v1/bmydesk/agent/broadcast-auth",
) {
    interface Listener {
        fun onConnected()
        fun onDisconnected()
        fun onJoinRequest(name: String)
        fun onSignal(payload: JsonObject)
        fun onEnd()
        fun onSubscribed() {}
        fun onJoinAccept() {}
        fun onJoinReject() {}
    }

    private val gson = Gson()
    private var pusher: Pusher? = null
    private var channel: PrivateChannel? = null
    // second subscription on the SAME ws connection (viewer channel)
    private var viewerChannel: PrivateChannel? = null
    private val channelTokens = ConcurrentHashMap<String, String>()
    @Volatile var isConnected = false; private set
    @Volatile var hostSubscribed = false; private set
    var listener: Listener? = null

    // Per-channel token authorizer — one ws connection carries both the host
    // channel (agent_token) and a joined viewer channel (viewer_token).
    private inner class MapAuthorizer : ChannelAuthorizer {
        override fun authorize(channelName: String, socketId: String): String {
            val token = channelTokens[channelName] ?: return "{}"
            val c = URL(authEndpoint).openConnection() as HttpURLConnection
            return try {
                c.requestMethod = "POST"
                c.setRequestProperty("Content-Type", "application/json")
                c.setRequestProperty("Accept", "application/json")
                c.doOutput = true
                val body = """{"socket_id":"$socketId","channel_name":"$channelName","agent_token":"$token"}"""
                OutputStreamWriter(c.outputStream).use { it.write(body) }
                c.inputStream.bufferedReader().readText()
            } catch (e: Exception) { "{}" } finally { c.disconnect() }
        }
    }

    fun connect(channelName: String, agentToken: String) {
        if (pusher != null) { runCatching { pusher?.disconnect() }; pusher = null; channel = null; viewerChannel = null }
        channelTokens[if (channelName.startsWith("private-")) channelName else "private-$channelName"] = agentToken
        val opts = PusherOptions()
            .setHost(wsHost)
            .setWssPort(wssPort)
            .setEncrypted(true)
            .setChannelAuthorizer(MapAuthorizer())

        pusher = Pusher(appKey, opts)
        pusher!!.connect(object : ConnectionEventListener {
            override fun onConnectionStateChange(change: ConnectionStateChange) {
                when (change.currentState) {
                    ConnectionState.CONNECTED -> { isConnected = true; listener?.onConnected() }
                    ConnectionState.DISCONNECTED -> { isConnected = false; listener?.onDisconnected() }
                    else -> {}
                }
            }
            override fun onError(message: String?, code: String?, e: Exception?) {}
        })

        // subscribePrivate expects the FULL name incl. the private- prefix
        val fullName = if (channelName.startsWith("private-")) channelName else "private-$channelName"
        channel = pusher!!.subscribePrivate(fullName, object : PrivateChannelEventListener {
            override fun onEvent(event: PusherEvent) {
                val data = gson.fromJson(event.data ?: "{}", JsonObject::class.java)
                when (event.eventName) {
                    "client-join-request" -> listener?.onJoinRequest(data.get("name")?.asString ?: "Someone")
                    "client-join-accept" -> listener?.onJoinAccept()
                    "client-join-reject" -> listener?.onJoinReject()
                    "client-signal" -> listener?.onSignal(data)
                    "client-end" -> listener?.onEnd()
                }
            }
            override fun onSubscriptionSucceeded(channelName: String?) { hostSubscribed = true; listener?.onSubscribed() }
            override fun onAuthenticationFailure(message: String?, e: Exception?) {
                isConnected = false
                hostSubscribed = false
                listener?.onDisconnected()
            }
        })
    }

    /**
     * Subscribe a viewer channel on the SAME ws connection (multiplexed) —
     * avoids a second socket which some networks/proxies throttle.
     */
    fun connectViewer(channelName: String, viewerToken: String, viewerListener: Listener) {
        val p = pusher ?: run { viewerListener.onDisconnected(); return }
        val full = if (channelName.startsWith("private-")) channelName else "private-$channelName"
        channelTokens[full] = viewerToken
        runCatching { p.unsubscribe(full) }
        viewerChannel = p.subscribePrivate(full, object : PrivateChannelEventListener {
            override fun onEvent(event: PusherEvent) {
                val data = gson.fromJson(event.data ?: "{}", JsonObject::class.java)
                when (event.eventName) {
                    "client-join-accept" -> viewerListener.onJoinAccept()
                    "client-join-reject" -> viewerListener.onJoinReject()
                    "client-signal" -> viewerListener.onSignal(data)
                    "client-end" -> viewerListener.onEnd()
                }
            }
            override fun onSubscriptionSucceeded(channelName: String?) { viewerListener.onSubscribed() }
            override fun onAuthenticationFailure(message: String?, e: Exception?) { viewerListener.onDisconnected() }
        })
    }

    // Optional HTTP relays — signal payloads are ALSO POSTed to the API so
    // they reach a peer whose ws is dead (server broadcasts + queues). The
    // shared "n" nonce lets the receiver dedupe ws + queued copies.
    var relay: ((payload: JsonObject) -> Unit)? = null
    var viewerRelay: ((payload: JsonObject) -> Unit)? = null
    private fun nonce() = java.util.UUID.randomUUID().toString().replace("-", "").take(12)

    fun sendViewer(event: String, payload: JsonObject) {
        if (event == "signal" && !payload.has("n")) payload.addProperty("n", nonce())
        runCatching { viewerChannel?.trigger("client-$event", gson.toJson(payload)) }
        when (event) {
            "signal" -> viewerRelay?.invoke(payload)
            "end" -> viewerRelay?.invoke(JsonObject().apply { addProperty("kind", "end") })
        }
    }

    fun disconnectViewer() {
        runCatching {
            viewerChannel?.let { pusher?.unsubscribe(it.name) }
        }
        viewerChannel = null
    }

    fun send(event: String, payload: JsonObject) {
        if (event == "signal" && !payload.has("n")) payload.addProperty("n", nonce())
        runCatching { channel?.trigger("client-$event", gson.toJson(payload)) }
        if (event == "signal") relay?.invoke(payload)
        else if (event == "end") relay?.invoke(JsonObject().apply { addProperty("kind", "end") })
    }

    fun disconnect() {
        isConnected = false
        runCatching { pusher?.disconnect() }
        pusher = null; channel = null; viewerChannel = null
    }
}
