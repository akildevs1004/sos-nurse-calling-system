package com.xtremeguard.tvurllauncher

import android.content.Context

object Prefs {
    private const val PREF_NAME = "tv_kiosk_prefs"
    private const val KEY_URL = "kiosk_url"
    private const val KEY_ADMIN_PIN = "admin_pin"

    fun getUrl(ctx: Context): String =
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .getString(KEY_URL, "") ?: ""

    fun setUrl(ctx: Context, url: String) {
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .edit().putString(KEY_URL, url).apply()
    }

    // Optional: you can change PIN later; default is 1234
    fun getAdminPin(ctx: Context): String =
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .getString(KEY_ADMIN_PIN, "1234") ?: "1234"

    fun setAdminPin(ctx: Context, pin: String) {
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .edit().putString(KEY_ADMIN_PIN, pin).apply()
    }
}
