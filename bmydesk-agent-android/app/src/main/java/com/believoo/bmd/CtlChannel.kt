package com.believoo.bmd

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
        /** chat carrying a delivery id — default keeps old signature working */
        fun onChatMsg(id: String, from: String, text: String) { onChat(from, text) }
        fun onChatAck(id: String) {}   // peer received it (✓✓)
        fun onChatSeen(id: String) {}  // peer read it (blue ✓✓)
        fun onClip(text: String) {}
        fun onFile(name: String, data: ByteArray) {}
        fun onFileProgress(received: Int, total: Int) {}
        fun onQuality(mode: String) {}
        fun onViewOnly(on: Boolean) {}
        fun onTyping(from: String) {}
        fun onWantCtl() {}
        fun onLock() {}
        fun onVol(dir: String) {}
        fun onFsList(path: String, items: com.google.gson.JsonArray?, err: String?) {}
        fun onCallState(kind: String, on: Boolean) {} // peer toggled voice/video
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
            when (m.get("t").s()) {
                "chat" -> {
                    val mid = m.get("id").s()
                    // delivery receipt — the ✓✓ the sender watches for
                    if (!mid.isNullOrBlank()) send(JsonObject().apply {
                        addProperty("t", "chat-ack"); addProperty("id", mid) })
                    sink?.let {
                        if (mid.isNullOrBlank()) it.onChat(m.get("from").s() ?: "peer", m.get("text").s() ?: "")
                        else it.onChatMsg(mid, m.get("from").s() ?: "peer", m.get("text").s() ?: "")
                    }
                }
                "chat-ack" -> m.get("id").s()?.let { sink?.onChatAck(it) }
                "chat-seen" -> m.get("id").s()?.let { sink?.onChatSeen(it) }
                "typing" -> sink?.onTyping(m.get("from").s() ?: "peer")
                // viewer asked the host to lift view-only — AnyDesk-style ask
                "want-ctl" -> sink?.onWantCtl()
                // remote device actions an Android host CAN honour
                "lock" -> sink?.onLock()
                "vol" -> sink?.onVol(m.get("dir").s() ?: "up")
                // peer started/stopped a voice call or camera — UI badge
                "call" -> sink?.onCallState(m.get("k").s() ?: "", m.get("on")?.asBoolean == true)
                "clip" -> m.get("text").s()?.let { sink?.onClip(it) }
                "file-meta" -> {
                    rxName = m.get("name").s() ?: "file.bin"
                    rxSize = m.get("size")?.asInt ?: 0
                    rxBuf.reset()
                }
                "file-cancel" -> { rxName = null; rxBuf.reset(); sink?.onChat("System", "Transfer cancelled by peer") }
                // remote-terminal output + host mode notices render in the chat
                "cmd-out" -> sink?.onChat("Host ▸", m.get("out").s() ?: "(no output)")
                "view-only" -> { val on = m.get("on")?.asBoolean == true
                    sink?.onViewOnly(on)
                    sink?.onChat("System", if (on) "Host locked remote input (view-only mode)"
                        else "Host re-enabled remote input") }
                // fs-list is a host→viewer REPLY (dir listing); fs-list-req/fs-get are
                // viewer→host requests an Android host can't serve yet
                "fs-list" -> sink?.onFsList(
                    m.get("path").s() ?: "",
                    m.getAsJsonArray("items"),
                    m.get("err").s())
                "quality" -> sink?.onQuality(m.get("mode").s() ?: "auto")
                // host-side actions — Android can't exec shell/reboot/browse files
                "cmd", "reboot", "screen", "fs-get", "procs", "kill" -> send(JsonObject().apply {
                    addProperty("t", "cmd-out")
                    addProperty("out", "'${m.get("t").s()}' is not supported on this host")
                })
                "fs-list-req" -> send(JsonObject().apply {
                    addProperty("t", "fs-list")
                    addProperty("path", "")
                    add("items", com.google.gson.JsonArray())
                    addProperty("err", "File browsing is not supported on this host")
                })
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

    fun sendChat(from: String, text: String, id: String? = null) = send(JsonObject().apply {
        addProperty("t", "chat"); addProperty("from", from); addProperty("text", text)
        if (!id.isNullOrBlank()) addProperty("id", id)
    })

    // WhatsApp-style read receipts — sent when the message was actually visible
    fun sendChatSeen(id: String) = send(JsonObject().apply {
        addProperty("t", "chat-seen"); addProperty("id", id)
    })
    // peer-facing call state — "voice on/off", "camera on/off" notices
    fun sendCallState(kind: String, on: Boolean) = send(JsonObject().apply {
        addProperty("t", "call"); addProperty("k", kind); addProperty("on", on)
    })

    fun sendClip(text: String) = send(JsonObject().apply {
        addProperty("t", "clip"); addProperty("text", text)
    })

    // viewer → host remote-control actions (desktop hosts only)
    fun sendCmd(cmd: String) = send(JsonObject().apply {
        addProperty("t", "cmd"); addProperty("cmd", cmd)
    })
    fun sendScreen() = send(JsonObject().apply { addProperty("t", "screen") })
    fun sendReboot() = send(JsonObject().apply { addProperty("t", "reboot") })
    fun sendLock() = send(JsonObject().apply { addProperty("t", "lock") })
    fun sendWantCtl() = send(JsonObject().apply { addProperty("t", "want-ctl") })
    fun sendVol(dir: String) = send(JsonObject().apply { addProperty("t", "vol"); addProperty("dir", dir) })
    fun sendTyping(from: String) = send(JsonObject().apply { addProperty("t", "typing"); addProperty("from", from) })
    fun sendQuality(mode: String) = send(JsonObject().apply { addProperty("t", "quality"); addProperty("mode", mode) })

    // host → viewer: input is locked for this session (view-only accept)
    fun sendViewOnly(on: Boolean) = send(JsonObject().apply { addProperty("t", "view-only"); addProperty("on", on) })

    // viewer → host: remote file explorer
    fun sendFsListReq(path: String) = send(JsonObject().apply { addProperty("t", "fs-list-req"); addProperty("path", path) })
    fun sendFsGet(path: String) = send(JsonObject().apply { addProperty("t", "fs-get"); addProperty("path", path) })

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
