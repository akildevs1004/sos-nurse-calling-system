package com.xtremeguard.tvurllauncher

import android.annotation.SuppressLint
import android.content.Intent
import android.graphics.Bitmap
import android.os.Bundle
import android.view.View
import android.view.ViewGroup
import android.view.WindowManager
import android.webkit.*
import android.widget.EditText
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import android.widget.FrameLayout
import android.widget.Toast

class WebViewActivity : AppCompatActivity() {

    private lateinit var root: FrameLayout
    private lateinit var webView: WebView
    private lateinit var adminHotZone: View

    private var customView: View? = null
    private var customViewCallback: WebChromeClient.CustomViewCallback? = null

    @SuppressLint("SetJavaScriptEnabled")
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
        setContentView(R.layout.activity_webview)

        root = findViewById(R.id.root)
        webView = findViewById(R.id.webView)
        adminHotZone = findViewById(R.id.adminHotZone)

        hideSystemUI()

        val url = intent.getStringExtra("url") ?: Prefs.getUrl(this)
        if (url.isBlank()) {
            startActivity(Intent(this, SetupActivity::class.java))
            finish()
            return
        }

        setupWebView()
        webView.loadUrl(url)

        // Hidden admin unlock: long-press top-left invisible view
        adminHotZone.setOnLongClickListener {
            showAdminPinDialog()
            true
        }
    }

    @SuppressLint("SetJavaScriptEnabled")
    private fun setupWebView() {
        WebView.setWebContentsDebuggingEnabled(true)









        

        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            loadWithOverviewMode = true
            useWideViewPort = true
            mediaPlaybackRequiresUserGesture = false

            // Block popups/new windows
            javaScriptCanOpenWindowsAutomatically = false
            setSupportMultipleWindows(false)
        }

        webView.clearCache(true)
webView.clearHistory() 

        // Force everything inside this WebView (no external browser/apps)
        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                view.loadUrl(request.url.toString())
                return true
            }

            override fun onPageStarted(view: WebView, url: String, favicon: Bitmap?) {
                super.onPageStarted(view, url, favicon)
                hideSystemUI()
            }

override fun onReceivedError(
    view: WebView,
    request: WebResourceRequest,
    error: WebResourceError
  ) {
    // useful logging
    android.util.Log.e("WEBVIEW", "Error: ${error.errorCode} ${error.description} ${request.url}")
  }





                
  override fun shouldInterceptRequest(view: WebView, request: WebResourceRequest): WebResourceResponse? {
    val url = request.url.toString()
    if (url.contains("/_nuxt/")) {
      try {
        val conn = java.net.URL(url).openConnection() as java.net.HttpURLConnection
        conn.instanceFollowRedirects = true
        conn.useCaches = false
        conn.setRequestProperty("Cache-Control", "no-cache")
        conn.setRequestProperty("Pragma", "no-cache")

        val mime = conn.contentType?.substringBefore(";") ?: "application/javascript"
        val encoding = conn.contentEncoding ?: "utf-8"
        val input = java.io.BufferedInputStream(conn.inputStream)

        return WebResourceResponse(mime, encoding, input)
      } catch (e: Exception) {
        android.util.Log.e("WEBVIEW", "Intercept failed: $url", e)
      }
    }
    return super.shouldInterceptRequest(view, request)
  }

        }

        // Fullscreen video support + prevent external windows
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
                hideSystemUI()
            }

            override fun onHideCustomView() {
                customView?.let { root.removeView(it) }
                customView = null
                webView.visibility = View.VISIBLE
                customViewCallback?.onCustomViewHidden()
                customViewCallback = null
                hideSystemUI()
            }

            override fun onCreateWindow(
                view: WebView,
                isDialog: Boolean,
                isUserGesture: Boolean,
                resultMsg: android.os.Message
            ): Boolean {
                // Deny new windows; keep everything in same WebView
                return false
            }
        }
    }

    private fun showAdminPinDialog() {
        val pinInput = EditText(this).apply {
            hint = "Enter Admin PIN"
            inputType = android.text.InputType.TYPE_CLASS_NUMBER or android.text.InputType.TYPE_NUMBER_VARIATION_PASSWORD
        }

        AlertDialog.Builder(this)
            .setTitle("Admin Unlock")
            .setView(pinInput)
            .setPositiveButton("Unlock") { dialog, _ ->
                val entered = pinInput.text.toString().trim()
                if (entered == Prefs.getAdminPin(this)) {
                    dialog.dismiss()
                    openSetupForChange()
                } else {
                    Toast.makeText(this, "Wrong PIN", Toast.LENGTH_SHORT).show()
                }
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun openSetupForChange() {
        // Clear saved URL and go to setup
        Prefs.setUrl(this, "")
        startActivity(Intent(this, SetupActivity::class.java))
        finish()
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
        // If video fullscreen, exit fullscreen first
        if (customView != null) {
            (webView.webChromeClient as? WebChromeClient)?.onHideCustomView()
            return
        }

        // Kiosk behavior: disable exit; only go back within page history
        if (webView.canGoBack()) {
            webView.goBack()
        } else {
            Toast.makeText(this, "Back disabled in kiosk mode", Toast.LENGTH_SHORT).show()
            // do nothing
        }
    }
}
