package com.xtremeguard.tvurllauncher

import android.annotation.SuppressLint
import android.content.Intent
import android.graphics.Bitmap
import android.os.Bundle
import android.view.KeyEvent
import android.view.View
import android.view.ViewGroup
import android.view.WindowManager
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

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
        setContentView(R.layout.activity_webview)

        // Bind views AFTER setContentView
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

        // Reload button
        btnReload.setOnClickListener {
            Toast.makeText(this, "Reloading...", Toast.LENGTH_SHORT).show()
            if (currentUrl.isNotBlank()) {
                webView.loadUrl(currentUrl)
            } else {
                webView.reload()
            }
        }
        attachTvOkKey(btnReload)

        // Bring buttons on top (AFTER view init)
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
        currentUrl = url
        webView.loadUrl(url)

        // Optional admin long-press zone (top-left)
        adminHotZone.setOnLongClickListener {
            Toast.makeText(this, "Use Settings button to change URL", Toast.LENGTH_SHORT).show()
            true
        }
    }

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
                val u = request.url.toString()
                currentUrl = u
                view.loadUrl(u)     // keep navigation inside app
                return true
            }

            override fun onPageStarted(view: WebView, url: String, favicon: Bitmap?) {
                super.onPageStarted(view, url, favicon)
                currentUrl = url

                // Keep buttons on top
                btnReload.bringToFront()
                btnSettings.bringToFront()

                hideSystemUI()
            }
        }

        webView.webChromeClient = object : WebChromeClient() {

            override fun onShowCustomView(view: View?, callback: CustomViewCallback?) {
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

                // hide buttons during fullscreen video
                btnReload.visibility = View.GONE
                btnSettings.visibility = View.GONE

                hideSystemUI()
            }

            override fun onHideCustomView() {
                customView?.let { root.removeView(it) }
                customView = null

                webView.visibility = View.VISIBLE
                customViewCallback?.onCustomViewHidden()
                customViewCallback = null

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
        else Toast.makeText(this, "Back disabled in kiosk mode", Toast.LENGTH_SHORT).show()
    }
}
