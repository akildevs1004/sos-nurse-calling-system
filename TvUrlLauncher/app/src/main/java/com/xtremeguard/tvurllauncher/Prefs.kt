package com.xtremeguard.tvurllauncher

import android.content.Context

object Prefs {
    private const val PREF_NAME = "tv_kiosk_prefs"

    private const val KEY_URL = "kiosk_url"
    private const val KEY_IP = "kiosk_ip"
    private const val KEY_PORT = "kiosk_port"
    private const val KEY_PATH = "kiosk_path"
    private const val KEY_PROTOCOL = "kiosk_protocol"
    private const val KEY_ADMIN_PIN = "admin_pin"

    // Defaults (only used on very first run)
    private const val DEFAULT_IP = "192.168.2.28"
    private const val DEFAULT_PORT = ""              // ✅ optional port default empty
    private const val DEFAULT_PATH = "/alarm/tvmonitor1"
    private const val DEFAULT_PROTOCOL = "http"

    fun getUrl(ctx: Context): String =
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .getString(KEY_URL, "") ?: ""

    fun setUrl(ctx: Context, url: String) {
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .edit().putString(KEY_URL, url).apply()
    }

    fun getIp(ctx: Context): String =
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .getString(KEY_IP, DEFAULT_IP) ?: DEFAULT_IP

    fun getPort(ctx: Context): String =
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .getString(KEY_PORT, DEFAULT_PORT) ?: DEFAULT_PORT

    fun getPath(ctx: Context): String =
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .getString(KEY_PATH, DEFAULT_PATH) ?: DEFAULT_PATH

    fun getProtocol(ctx: Context): String =
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .getString(KEY_PROTOCOL, DEFAULT_PROTOCOL) ?: DEFAULT_PROTOCOL

    fun setNetworkFields(ctx: Context, ip: String, port: String, path: String, protocol: String) {
        val p = if (protocol.equals("https", true)) "https" else "http"
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .edit()
            .putString(KEY_IP, ip)
            .putString(KEY_PORT, port)
            .putString(KEY_PATH, path)
            .putString(KEY_PROTOCOL, p)
            .apply()
    }

    fun getAdminPin(ctx: Context): String =
        ctx.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
            .getString(KEY_ADMIN_PIN, "1234") ?: "1234"
}
