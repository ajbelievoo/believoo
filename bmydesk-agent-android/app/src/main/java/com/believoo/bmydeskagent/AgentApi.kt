package com.believoo.bmydeskagent

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
        .build()
    private val gson = Gson()
    private val json = "application/json".toMediaType()

    data class Registration(
        val code: String,
        val agentToken: String,
        val channel: String,
        val expiresAt: String?,
        val iceServers: com.google.gson.JsonArray?,
    )

    fun register(hostName: String, os: String = "android", memberToken: String? = null): Registration {
        val fields = mutableMapOf<String, String>("host_name" to hostName, "version" to BuildConfig.VERSION_NAME, "os" to os)
        memberToken?.let { fields["member_token"] = it }
        val body = gson.toJson(fields)
            .toRequestBody(json)
        val req = Request.Builder().url("$base/register").post(body).build()
        http.newCall(req).execute().use { res ->
            val obj = gson.fromJson(res.body!!.string(), JsonObject::class.java)
            if (!obj.get("ok").asBoolean) throw RuntimeException(obj.get("error")?.asString ?: "register failed")
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
            val obj = gson.fromJson(res.body!!.string(), JsonObject::class.java)
            if (!obj.get("ok").asBoolean) throw RuntimeException(obj.get("error")?.asString ?: "login failed")
            return LoginResult(obj.get("name").asString, obj.get("company")?.asString, obj.get("member_token").asString)
        }
    }

    data class JoinResult(val viewerToken: String, val channel: String, val hostLabel: String?, val iceServers: com.google.gson.JsonArray?)

    /** Join another session as a viewer (agent-to-agent remote). Throws on error. */
    fun join(code: String): JoinResult {
        val req = Request.Builder().url("$base/$code/join").post("{}".toRequestBody(json)).build()
        http.newCall(req).execute().use { res ->
            val obj = gson.fromJson(res.body!!.string(), JsonObject::class.java)
            if (!obj.get("ok").asBoolean) throw RuntimeException(obj.get("error")?.asString ?: "join failed")
            return JoinResult(
                viewerToken = obj.get("viewer_token").asString,
                channel = obj.get("channel").asString,
                hostLabel = obj.get("host_label")?.asString,
                iceServers = obj.getAsJsonArray("ice_servers"),
            )
        }
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
}
