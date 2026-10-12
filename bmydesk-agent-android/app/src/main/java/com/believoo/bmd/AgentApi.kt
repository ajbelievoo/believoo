package com.believoo.bmd

import com.google.gson.Gson
import com.google.gson.JsonObject
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import java.util.concurrent.TimeUnit

/** Talks to the BMyDesk agent REST API (register / status / end / broadcast-auth). */
class AgentApi(private val base: String = "https://bmydesk.believoo.com/api/v1/bmydesk/agent") {

    private val http = OkHttpClient.Builder()
        .connectTimeout(15, TimeUnit.SECONDS)
        .readTimeout(20, TimeUnit.SECONDS)
        .addInterceptor { chain ->
            chain.proceed(chain.request().newBuilder().header("Accept", "application/json").build())
        }
        .build()
    private val gson = Gson()
    private val json = "application/json".toMediaType()

    /** Parses the response body as a JsonObject or returns null — a null means
     *  the server/proxy answered with a non-JSON body (HTML error page, edge
     *  block, etc.) which must not be fed to Gson unchecked. */
    private fun bodyObj(res: okhttp3.Response): JsonObject? =
        runCatching { gson.fromJson(res.body?.string().orEmpty(), JsonObject::class.java) }.getOrNull()

    private fun errorOf(obj: JsonObject, fallback: String, code: Int): RuntimeException =
        RuntimeException(
            obj.get("error")?.takeIf { !it.isJsonNull }?.asString
                ?: obj.get("message")?.takeIf { !it.isJsonNull }?.asString
                ?: "$fallback (HTTP $code)"
        )

    data class Registration(
        val code: String,
        val agentToken: String,
        val channel: String,
        val expiresAt: String?,
        val iceServers: com.google.gson.JsonArray?,
        val autoAccepted: Boolean = false,
    )

    fun register(hostName: String, os: String = "android", memberToken: String? = null, deviceId: String? = null, fcmToken: String? = null): Registration {
        val fields = mutableMapOf<String, String>("host_name" to hostName, "version" to BuildConfig.VERSION_NAME, "os" to os)
        memberToken?.let { fields["member_token"] = it }
        deviceId?.let { fields["device_id"] = it }
        fcmToken?.let { fields["fcm_token"] = it }
        val body = gson.toJson(fields)
            .toRequestBody(json)
        val req = Request.Builder().url("$base/register").post(body).build()
        http.newCall(req).execute().use { res ->
            val obj = bodyObj(res)
                ?: throw RuntimeException("Invalid server response (HTTP ${res.code})")
            if (obj.get("ok")?.asBoolean != true) throw errorOf(obj, "register failed", res.code)
            return Registration(
                code = obj.get("session_code").asString,
                agentToken = obj.get("agent_token").asString,
                channel = obj.get("channel").asString,
                expiresAt = obj.get("expires_at")?.asString,
                iceServers = obj.getAsJsonArray("ice_servers"),
            )
        }
    }

    /** Returns (latestVersion, downloadUrl) or null — used for the in-app update prompt. */
    fun latestVersion(platform: String): Pair<String, String>? {
        val req = Request.Builder().url("$base/version?platform=$platform").build()
        return runCatching {
            http.newCall(req).execute().use {
                val o = gson.fromJson(it.body!!.string(), JsonObject::class.java)
                if (o.get("ok").asBoolean) o.get("latest").asString to o.get("url").asString else null
            }
        }.getOrNull()
    }

    data class LoginResult(val name: String, val company: String?, val memberToken: String)

    fun login(email: String, password: String): LoginResult {
        val body = gson.toJson(mapOf("email" to email, "password" to password)).toRequestBody(json)
        val req = Request.Builder().url("$base/login").post(body).build()
        http.newCall(req).execute().use { res ->
            val obj = bodyObj(res)
                ?: throw RuntimeException("Invalid server response (HTTP ${res.code})")
            if (obj.get("ok")?.asBoolean != true) throw errorOf(obj, "login failed", res.code)
            return LoginResult(obj.get("name").asString, obj.get("company")?.asString, obj.get("member_token").asString)
        }
    }

    data class JoinResult(
        val viewerToken: String,
        val channel: String,
        val hostLabel: String?,
        val iceServers: com.google.gson.JsonArray?,
        val autoAccepted: Boolean = false,
    )

    /** Join another session as a viewer (agent-to-agent remote). Throws on error. */
    fun join(code: String, pin: String? = null, name: String? = null, note: String? = null,
             viewerDevice: String? = null): JoinResult {
        val body = mutableMapOf<String, String>()
        pin?.takeIf { it.isNotBlank() }?.let { body["pin"] = it }
        name?.takeIf { it.isNotBlank() }?.let { body["name"] = it }
        note?.takeIf { it.isNotBlank() }?.let { body["note"] = it }
        viewerDevice?.takeIf { it.isNotBlank() }?.let { body["viewer_device"] = it }
        val req = Request.Builder().url("$base/$code/join")
            .post(gson.toJson(body).toRequestBody(json)).build()
        http.newCall(req).execute().use { res ->
            val obj = bodyObj(res)
                ?: throw RuntimeException("Invalid server response (HTTP ${res.code})")
            if (obj.get("ok")?.asBoolean != true) throw errorOf(obj, "join failed", res.code)
            return JoinResult(
                viewerToken = obj.get("viewer_token").asString,
                channel = obj.get("channel").asString,
                hostLabel = obj.get("host_label")?.asString,
                iceServers = obj.getAsJsonArray("ice_servers"),
                autoAccepted = obj.get("auto_accepted")?.asBoolean == true,
            )
        }
    }

    /** Set/clear the unattended-access PIN for this device (empty = remove). */
    fun setPin(code: String, token: String, pin: String): Boolean {
        val req = Request.Builder().url("$base/$code/set-pin")
            .post(gson.toJson(mapOf("pin" to pin)).toRequestBody(json))
            .header("Authorization", "Bearer $token").build()
        return runCatching {
            http.newCall(req).execute().use {
                gson.fromJson(it.body!!.string(), JsonObject::class.java).get("ok").asBoolean
            }
        }.getOrDefault(false)
    }

    /** HTTP fallback accept/reject — server broadcasts the client-event. */
    fun respond(code: String, token: String, action: String) {
        runCatching {
            val req = Request.Builder().url("$base/$code/respond")
                .post(gson.toJson(mapOf("action" to action)).toRequestBody(json))
                .header("Authorization", "Bearer $token").build()
            http.newCall(req).execute().close()
        }
    }

    fun status(code: String, token: String): JsonObject? {
        val req = Request.Builder().url("$base/$code/status")
            .header("Authorization", "Bearer $token").build()
        return runCatching {
            http.newCall(req).execute().use { gson.fromJson(it.body!!.string(), JsonObject::class.java) }
        }.getOrNull()
    }

    fun end(code: String, token: String) {
        runCatching {
            val req = Request.Builder().url("$base/$code/end")
                .post("{}".toRequestBody(json))
                .header("Authorization", "Bearer $token").build()
            http.newCall(req).execute().close()
        }
    }

    /** HTTP signaling path — server broadcasts to ws peers AND queues for
     *  the peer's poll, so signaling works even when one ws is dead. */
    fun signal(code: String, token: String, payload: JsonObject) {
        runCatching {
            val req = Request.Builder().url("$base/$code/signal")
                .post(gson.toJson(payload).toRequestBody(json))
                .header("Authorization", "Bearer $token").build()
            http.newCall(req).execute().close()
        }
    }

    /** Drains queued signals for the caller's role (agent or viewer token). */
    fun drainSignals(code: String, token: String): List<JsonObject> {
        val req = Request.Builder().url("$base/$code/signals")
            .header("Authorization", "Bearer $token").build()
        return runCatching {
            http.newCall(req).execute().use {
                val o = gson.fromJson(it.body!!.string(), JsonObject::class.java)
                o.getAsJsonArray("signals")?.map { e -> e.asJsonObject } ?: emptyList()
            }
        }.getOrDefault(emptyList())
    }

    // ── Address book (saved devices under this device's owner key) ──
    data class SavedDevice(val id: Int, val code: String, val label: String?, val online: Boolean)

    fun savedDevices(code: String, token: String): List<SavedDevice> {
        val req = Request.Builder().url("$base/$code/devices")
            .header("Authorization", "Bearer $token").build()
        return runCatching {
            http.newCall(req).execute().use {
                val arr = gson.fromJson(it.body!!.string(), JsonObject::class.java)
                    .getAsJsonArray("devices") ?: return@use emptyList()
                arr.map { d -> d.asJsonObject.let { o ->
                    SavedDevice(o.get("id").asInt, o.get("code").asString,
                        o.get("label")?.let { l -> if (l.isJsonNull) null else l.asString },
                        o.get("online")?.asBoolean == true) } }
            }
        }.getOrDefault(emptyList())
    }

    fun saveDevice(code: String, token: String, targetCode: String, label: String) {
        runCatching {
            val req = Request.Builder().url("$base/$code/devices")
                .post(gson.toJson(mapOf("target_code" to targetCode, "label" to label)).toRequestBody(json))
                .header("Authorization", "Bearer $token").build()
            http.newCall(req).execute().close()
        }
    }

    fun forgetDevice(code: String, token: String, id: Int) {
        runCatching {
            val req = Request.Builder().url("$base/$code/devices/$id").delete()
                .header("Authorization", "Bearer $token").build()
            http.newCall(req).execute().close()
        }
    }
}
