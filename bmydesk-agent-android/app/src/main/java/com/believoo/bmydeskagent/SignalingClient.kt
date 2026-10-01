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
import com.pusher.client.util.HttpChannelAuthorizer

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
    }

    private val gson = Gson()
    private var pusher: Pusher? = null
    private var channel: PrivateChannel? = null
    var listener: Listener? = null

    fun connect(channelName: String, agentToken: String) {
        val authorizer = HttpChannelAuthorizer(authEndpoint).apply {
            setHeaders(mutableMapOf("Authorization" to "Bearer $agentToken"))
        }
        val opts = PusherOptions()
            .setHost(wsHost)
            .setWssPort(wssPort)
            .setEncrypted(true)
            .setChannelAuthorizer(authorizer)

        pusher = Pusher(appKey, opts)
        pusher!!.connect(object : ConnectionEventListener {
            override fun onConnectionStateChange(change: ConnectionStateChange) {
                when (change.currentState) {
                    ConnectionState.CONNECTED -> listener?.onConnected()
                    ConnectionState.DISCONNECTED -> listener?.onDisconnected()
                    else -> {}
                }
            }
            override fun onError(message: String?, code: String?, e: Exception?) {}
        })

        channel = pusher!!.subscribePrivate(channelName.removePrefix("private-"), object : PrivateChannelEventListener {
            override fun onEvent(event: PusherEvent) {
                val data = gson.fromJson(event.data ?: "{}", JsonObject::class.java)
                when (event.eventName) {
                    "client-join-request" -> listener?.onJoinRequest(data.get("name")?.asString ?: "Someone")
                    "client-signal" -> listener?.onSignal(data)
                    "client-end" -> listener?.onEnd()
                }
            }
            override fun onSubscriptionSucceeded(channelName: String?) {}
            override fun onAuthenticationFailure(message: String?, e: Exception?) {}
        })
    }

    fun send(event: String, payload: JsonObject) {
        runCatching { channel?.trigger("client-$event", gson.toJson(payload)) }
    }

    fun disconnect() {
        runCatching { pusher?.disconnect() }
        pusher = null; channel = null
    }
}
