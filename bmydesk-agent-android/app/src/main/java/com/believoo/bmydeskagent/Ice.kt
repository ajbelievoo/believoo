package com.believoo.bmydeskagent

import com.google.gson.JsonArray
import org.webrtc.PeerConnection

/** Parse the ice_servers array returned by the agent API into WebRTC IceServers. */
fun iceServersFrom(json: JsonArray?): List<PeerConnection.IceServer> {
    val list = mutableListOf<PeerConnection.IceServer>()
    json?.forEach { el ->
        val o = el.asJsonObject
        val urls = o.get("urls").let {
            if (it.isJsonArray) it.asJsonArray.map { u -> u.asString } else listOf(it.asString)
        }
        val b = PeerConnection.IceServer.builder(urls)
        o.get("username")?.let { u -> b.setUsername(u.asString) }
        o.get("credential")?.let { c -> b.setPassword(c.asString) }
        list.add(b.createIceServer())
    }
    if (list.isEmpty()) list.add(PeerConnection.IceServer.builder("stun:stun.l.google.com:19302").createIceServer())
    return list
}
