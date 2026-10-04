package com.believoo.bmydeskagent

import com.google.gson.Gson
import com.google.gson.JsonObject
import org.webrtc.DataChannel
import java.io.ByteArrayOutputStream
import java.nio.ByteBuffer

/**
 * Reliable/ordered "ctl" data channel shared by host + viewer.
 * Carries JSON control messages (chat, clipboard, file-meta) and raw binary
 * file chunks. Same wire protocol as the Electron agent and web viewers:
 *   {t:'chat', from, text} | {t:'clip', text} | {t:'file-meta', name, size}
 *   …binary chunks follow file-meta until `size` bytes have arrived.
 */
class CtlChannel(private val gson: Gson = Gson()) {

    interface Sink {
        fun onCtlOpen() {}
        fun onChat(from: String, text: String) {}
        fun onClip(text: String) {}
        fun onFile(name: String, data: ByteArray) {}
        fun onFileProgress(received: Int, total: Int) {}
    }

    var sink: Sink? = null

    private var dc: DataChannel? = null
    private var rxName: String? = null
    private var rxSize = 0
    private val rxBuf = ByteArrayOutputStream()

    fun attach(d: DataChannel) {
        dc = d
        d.registerObserver(object : DataChannel.Observer {
            override fun onBufferedAmountChange(p0: Long) {}
            override fun onStateChange() { if (d.state() == DataChannel.State.OPEN) sink?.onCtlOpen() }
            override fun onMessage(buf: DataChannel.Buffer) = onMsg(buf)
        })
        if (d.state() == DataChannel.State.OPEN) sink?.onCtlOpen()
    }

    private fun onMsg(buf: DataChannel.Buffer) {
        val bytes = ByteArray(buf.data.remaining()); buf.data.get(bytes)
        if (!buf.binary) {
            val m = runCatching { gson.fromJson(String(bytes), JsonObject::class.java) }.getOrNull() ?: return
            when (m.get("t")?.asString) {
                "chat" -> sink?.onChat(m.get("from")?.asString ?: "peer", m.get("text")?.asString ?: "")
                "clip" -> m.get("text")?.asString?.let { sink?.onClip(it) }
                "file-meta" -> {
                    rxName = m.get("name")?.asString ?: "file.bin"
                    rxSize = m.get("size")?.asInt ?: 0
                    rxBuf.reset()
                }
            }
        } else if (rxName != null) {
            rxBuf.write(bytes)
            sink?.onFileProgress(rxBuf.size(), rxSize)
            if (rxBuf.size() >= rxSize) {
                val name = rxName!!; rxName = null
                sink?.onFile(name, rxBuf.toByteArray()); rxBuf.reset()
            }
        }
    }

    fun open(): Boolean = dc?.state() == DataChannel.State.OPEN

    fun send(m: JsonObject) {
        val d = dc ?: return
        if (d.state() == DataChannel.State.OPEN)
            runCatching { d.send(DataChannel.Buffer(ByteBuffer.wrap(gson.toJson(m).toByteArray()), false)) }
    }

    fun sendChat(from: String, text: String) = send(JsonObject().apply {
        addProperty("t", "chat"); addProperty("from", from); addProperty("text", text)
    })

    fun sendClip(text: String) = send(JsonObject().apply {
        addProperty("t", "clip"); addProperty("text", text)
    })

    fun sendFile(name: String, data: ByteArray, chunk: Int = 16384) {
        val d = dc ?: return
        if (d.state() != DataChannel.State.OPEN || data.isEmpty()) return
        send(JsonObject().apply {
            addProperty("t", "file-meta"); addProperty("name", name); addProperty("size", data.size)
        })
        var off = 0
        while (off < data.size) {
            val n = minOf(chunk, data.size - off)
            val bb = ByteBuffer.allocate(n); bb.put(data, off, n); bb.flip()
            d.send(DataChannel.Buffer(bb, true))
            off += n
        }
    }

    fun close() {
        runCatching { dc?.close() }
        dc = null; rxName = null; rxBuf.reset()
    }
}
