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

    fun register(hostName: String, os: String = "android"): Registration {
        val body = gson.toJson(mapOf("host_name" to hostName, "version" to "1.0.0", "os" to os))
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
