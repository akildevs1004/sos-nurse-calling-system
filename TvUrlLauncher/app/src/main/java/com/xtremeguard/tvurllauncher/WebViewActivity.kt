package com.xtremeguard.tvurllauncher

import android.annotation.SuppressLint
import android.content.Intent
import android.graphics.Bitmap
import android.media.AudioAttributes
import android.media.AudioManager
import android.media.MediaPlayer
import android.os.Bundle
import android.util.Log
import android.view.KeyEvent
import android.view.View
import android.view.ViewGroup
import android.view.WindowManager
import android.webkit.JavascriptInterface
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.FrameLayout
import android.widget.ImageButton
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity

class WebViewActivity : AppCompatActivity() {

    private lateinit var root: FrameLayout
    private lateinit var webView: WebView
    private lateinit var adminHotZone: View
    private lateinit var btnSettings: ImageButton
    private lateinit var btnReload: ImageButton

    private var customView: View? = null
    private var customViewCallback: WebChromeClient.CustomViewCallback? = null
    private var currentUrl: String = ""

    // ===== Alarm =====
    private var alarmPlayer: MediaPlayer? = null
    private var isAlarmPlaying = false

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
        setContentView(R.layout.activity_webview)

        root = findViewById(R.id.root)
        webView = findViewById(R.id.webView)
        adminHotZone = findViewById(R.id.adminHotZone)
        btnSettings = findViewById(R.id.btnSettings)
        btnReload = findViewById(R.id.btnReload)

        hideSystemUI()

        // Settings -> back to setup
        btnSettings.setOnClickListener {
            startActivity(Intent(this, SetupActivity::class.java).putExtra("forceSettings", true))
            finish()
        }
        attachTvOkKey(btnSettings)

        // Reload
        btnReload.setOnClickListener {
            Toast.makeText(this, "Reloading...", Toast.LENGTH_SHORT).show()
            if (currentUrl.isNotBlank()) webView.loadUrl(currentUrl) else webView.reload()
        }
        attachTvOkKey(btnReload)

        btnReload.bringToFront()
        btnSettings.bringToFront()

        // Load URL
        val url = intent.getStringExtra("url") ?: Prefs.getUrl(this)
        if (url.isBlank()) {
            startActivity(Intent(this, SetupActivity::class.java).putExtra("forceSettings", true))
            finish()
            return
        }

        setupWebView()

        // JS → Android bridge (call from website)
        webView.addJavascriptInterface(AndroidBridge(), "AndroidBridge")

        currentUrl = url
        webView.loadUrl(url)

        // Optional admin long-press zone
        adminHotZone.setOnLongClickListener {
            Toast.makeText(this, "Use Settings button to change URL", Toast.LENGTH_SHORT).show()
            true
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        releaseAlarm()
        try {
            webView.removeJavascriptInterface("AndroidBridge")
        } catch (_: Exception) {
        }
    }

    // ================= WebView =================

    @SuppressLint("SetJavaScriptEnabled")
    private fun setupWebView() {
        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            loadWithOverviewMode = true
            useWideViewPort = true
            mediaPlaybackRequiresUserGesture = false
            javaScriptCanOpenWindowsAutomatically = false
            setSupportMultipleWindows(false)
        }

        webView.webViewClient = object : WebViewClient() {

            override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                currentUrl = request.url.toString()
                view.loadUrl(currentUrl)
                return true
            }

            override fun onPageStarted(view: WebView, url: String, favicon: Bitmap?) {
                super.onPageStarted(view, url, favicon)
                currentUrl = url
                btnReload.bringToFront()
                btnSettings.bringToFront()
                hideSystemUI()
            }

            override fun onPageFinished(view: WebView, url: String) {
                super.onPageFinished(view, url)
                injectJsHelpers()
            }
        }

        webView.webChromeClient = object : WebChromeClient() {

            override fun onShowCustomView(view: View?, callback: CustomViewCallback?) {
                // IMPORTANT: view can be null on some devices/pages
                if (view == null) {
                    callback?.onCustomViewHidden()
                    return
                }
                // prevent duplicate fullscreen calls
                if (customView != null) {
                    callback?.onCustomViewHidden()
                    return
                }

                customView = view
                customViewCallback = callback

                webView.visibility = View.GONE

                root.addView(
                    customView,
                    FrameLayout.LayoutParams(
                        ViewGroup.LayoutParams.MATCH_PARENT,
                        ViewGroup.LayoutParams.MATCH_PARENT
                    )
                )

                btnReload.visibility = View.GONE
                btnSettings.visibility = View.GONE
                hideSystemUI()
            }

            override fun onHideCustomView() {
                customView?.let { root.removeView(it) }
                customView = null

                customViewCallback?.onCustomViewHidden()
                customViewCallback = null

                webView.visibility = View.VISIBLE

                btnReload.visibility = View.VISIBLE
                btnSettings.visibility = View.VISIBLE
                btnReload.bringToFront()
                btnSettings.bringToFront()

                hideSystemUI()
            }

            override fun onCreateWindow(
                view: WebView,
                isDialog: Boolean,
                isUserGesture: Boolean,
                resultMsg: android.os.Message
            ): Boolean {
                // Block popups/new windows in kiosk
                return false
            }
        }
    }

    // ================= JS Injection =================
    // After page loads, website can call:
    //   startSOSAlarm() / stopSOSAlarm()
    private fun injectJsHelpers() {
        webView.evaluateJavascript(
            """
            (function(){
              window.startSOSAlarm = function() {
                try { if (window.AndroidBridge) AndroidBridge.startAlarm(); } catch(e) {}
              };
              window.stopSOSAlarm = function() {
                try { if (window.AndroidBridge) AndroidBridge.stopAlarm(); } catch(e) {}
              };
              window.isAndroidTvApp = true;
            })();
            """.trimIndent(),
            null
        )
    }

    // ================= Alarm Control =================

    private fun ensurePlayer(): MediaPlayer? {
        return try {
            if (alarmPlayer == null) {
                val created = MediaPlayer.create(this, R.raw.sos_alarm)
                if (created == null) {
                    Log.e("WebViewActivity", "MediaPlayer.create returned null. Check res/raw/sos_alarm.mp3")
                    return null
                }

                created.setAudioAttributes(
                    AudioAttributes.Builder()
                        .setUsage(AudioAttributes.USAGE_ALARM)
                        .setContentType(AudioAttributes.CONTENT_TYPE_MUSIC)
                        .build()
                )
                created.isLooping = true

                created.setOnErrorListener { mp, what, extra ->
                    Log.e("WebViewActivity", "MediaPlayer error what=$what extra=$extra")
                    try { mp.reset() } catch (_: Exception) {}
                    try { mp.release() } catch (_: Exception) {}
                    alarmPlayer = null
                    isAlarmPlaying = false
                    true
                }

                alarmPlayer = created
            }
            alarmPlayer
        } catch (e: Exception) {
            Log.e("WebViewActivity", "ensurePlayer failed", e)
            null
        }
    }

    private fun startAlarm() {
        if (isAlarmPlaying) return

        val p = ensurePlayer() ?: return

        try {
            // Optional volume bump (remove if not wanted)
            //val audioManager = getSystemService(AUDIO_SERVICE) as AudioManager
           // val max = audioManager.getStreamMaxVolume(AudioManager.STREAM_MUSIC)
           // val target = (max * 0.5).toInt().coerceAtLeast(1)
           // audioManager.setStreamVolume(AudioManager.STREAM_MUSIC, target, 0)

            // Restart from beginning on every start
            try { p.seekTo(0) } catch (_: Exception) {}

            p.start()
            isAlarmPlaying = true
            Log.d("ALARM", "Alarm STARTED")
        } catch (e: Exception) {
            Log.e("WebViewActivity", "startAlarm failed", e)
            isAlarmPlaying = false
        }
    }

    private fun stopAlarm() {
        val p = alarmPlayer ?: run {
            isAlarmPlaying = false
            return
        }

        try {
            if (p.isPlaying) p.pause()
            try { p.seekTo(0) } catch (_: Exception) {}
        } catch (e: Exception) {
            Log.e("WebViewActivity", "stopAlarm failed", e)
        } finally {
            isAlarmPlaying = false
            Log.d("ALARM", "Alarm STOPPED")
        }
    }

    private fun releaseAlarm() {
        val p = alarmPlayer ?: return
        try {
            try { if (p.isPlaying) p.pause() } catch (_: Exception) {}
            try { p.seekTo(0) } catch (_: Exception) {}
            try { p.reset() } catch (_: Exception) {}
            try { p.release() } catch (_: Exception) {}
        } catch (e: Exception) {
            Log.e("WebViewActivity", "releaseAlarm failed", e)
        } finally {
            alarmPlayer = null
            isAlarmPlaying = false
        }
    }

    // ================= JS Bridge =================
    // Call from website JS:
    //   AndroidBridge.startAlarm();
    //   AndroidBridge.stopAlarm();
     

    inner class AndroidBridge {

    @JavascriptInterface
    fun startAlarm() {
        runOnUiThread { this@WebViewActivity.startAlarm() }
    }

    @JavascriptInterface
    fun stopAlarm() {
        runOnUiThread { this@WebViewActivity.stopAlarm() }
    }

    @JavascriptInterface
    fun log(msg: String) {
        Log.d("AndroidBridge", msg)
    }
}

    // ================= UI Helpers =================

    private fun attachTvOkKey(view: View) {
        view.setOnKeyListener { v, keyCode, event ->
            if (event.action == KeyEvent.ACTION_UP &&
                (keyCode == KeyEvent.KEYCODE_DPAD_CENTER ||
                        keyCode == KeyEvent.KEYCODE_ENTER ||
                        keyCode == KeyEvent.KEYCODE_NUMPAD_ENTER)
            ) {
                v.performClick()
                true
            } else false
        }
    }

    private fun hideSystemUI() {
        window.decorView.systemUiVisibility =
            (View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
                    or View.SYSTEM_UI_FLAG_FULLSCREEN
                    or View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
                    or View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
                    or View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
                    or View.SYSTEM_UI_FLAG_LAYOUT_STABLE)
    }

    override fun onWindowFocusChanged(hasFocus: Boolean) {
        super.onWindowFocusChanged(hasFocus)
        if (hasFocus) hideSystemUI()
    }

    override fun onBackPressed() {
        if (customView != null) {
            (webView.webChromeClient as? WebChromeClient)?.onHideCustomView()
            return
        }

        if (webView.canGoBack()) webView.goBack()
        else Toast.makeText(this, "Back disabled", Toast.LENGTH_SHORT).show()
    }
}
