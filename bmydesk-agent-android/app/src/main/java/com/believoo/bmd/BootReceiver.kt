package com.believoo.bmd

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent

/**
 * After a reboot the agent re-registers so the device's permanent code is
 * reachable again — no one has to open the app (unattended devices stay online).
 */
class BootReceiver : BroadcastReceiver() {
    override fun onReceive(c: Context, i: Intent) {
        if (i.action != Intent.ACTION_BOOT_COMPLETED) return
        // startForegroundService from BOOT_COMPLETED is allowed for the
        // dataSync type — an activity launch from the background is NOT.
        runCatching { c.startForegroundService(Intent(c, AgentService::class.java)) }
    }
}
