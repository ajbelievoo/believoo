package com.believoo.bmydeskagent

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
        runCatching {
            val launch = c.packageManager.getLaunchIntentForPackage(c.packageName)
                ?.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK) ?: return
            c.startActivity(launch)
        }
    }
}
