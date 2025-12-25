package com.xtremeguard.tvurllauncher

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.view.inputmethod.InputMethodManager
import android.widget.Button
import android.widget.EditText
import android.widget.RadioButton
import android.widget.RadioGroup
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity

class SetupActivity : AppCompatActivity() {

    private lateinit var ipInput: EditText
    private lateinit var portInput: EditText
    private lateinit var pathInput: EditText
    private lateinit var saveOpenBtn: Button

    private lateinit var loadingOverlay: View
    private lateinit var contentLayout: View

    private lateinit var protocolGroup: RadioGroup
    private lateinit var rbHttp: RadioButton
    private lateinit var rbHttps: RadioButton

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val forceSettings = intent.getBooleanExtra("forceSettings", false)

        // If URL exists AND not forced → go directly to WebView
        val savedUrl = Prefs.getUrl(this)
        if (savedUrl.isNotBlank() && !forceSettings) {
            openWebView(savedUrl)
            finish()
            return
        }

        setContentView(R.layout.activity_setup)

        // Protocol UI
        protocolGroup = findViewById(R.id.protocolGroup)
        rbHttp = findViewById(R.id.rbHttp)
        rbHttps = findViewById(R.id.rbHttps)

        // Overlay views
        contentLayout = findViewById(R.id.contentLayout)
        loadingOverlay = findViewById(R.id.loadingOverlay)

        // Inputs
        ipInput = findViewById(R.id.ipInput)
        portInput = findViewById(R.id.portInput)
        pathInput = findViewById(R.id.pathInput)
        saveOpenBtn = findViewById(R.id.saveOpenBtn)

        // Load saved values (or defaults)
        ipInput.setText(Prefs.getIp(this))
        portInput.setText(Prefs.getPort(this))
        pathInput.setText(Prefs.getPath(this))

        // Load saved protocol
        val savedProtocol = Prefs.getProtocol(this)
        if (savedProtocol.equals("https", true)) rbHttps.isChecked = true else rbHttp.isChecked = true

        ipInput.requestFocus()
        showKeyboard(ipInput)

        saveOpenBtn.setOnClickListener {
            val ip = ipInput.text.toString().trim()
            val portRaw = portInput.text.toString().trim()
            val pathRaw = pathInput.text.toString().trim()

            if (ip.isEmpty()) {
                toast("IP address is required")
                ipInput.requestFocus()
                return@setOnClickListener
            }

            // Port optional, validate if entered
            if (portRaw.isNotEmpty()) {
                val portNum = portRaw.toIntOrNull()
                if (portNum == null || portNum !in 1..65535) {
                    toast("Port must be 1 - 65535")
                    portInput.requestFocus()
                    return@setOnClickListener
                }
            }

            val protocol = if (rbHttps.isChecked) "https" else "http"
            val path = normalizePath(pathRaw)

            val url = if (portRaw.isNotEmpty()) {
                "$protocol://$ip:$portRaw$path"
            } else {
                "$protocol://$ip$path"
            }

            // Save everything
            Prefs.setNetworkFields(this, ip, portRaw, path, protocol)
            Prefs.setUrl(this, url)

            showLoading(true)

            saveOpenBtn.postDelayed({
                openWebView(url)
                finish()
            }, 600)
        }
    }

    private fun showLoading(show: Boolean) {
        loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        contentLayout.isEnabled = !show
        ipInput.isEnabled = !show
        portInput.isEnabled = !show
        pathInput.isEnabled = !show
        saveOpenBtn.isEnabled = !show
    }

    private fun normalizePath(pathRaw: String): String {
        val p = pathRaw.trim()
        return when {
            p.isBlank() -> ""
            p.startsWith("/") -> p
            else -> "/$p"
        }
    }

    private fun openWebView(url: String) {
        startActivity(
            Intent(this, WebViewActivity::class.java)
                .putExtra("url", url)
        )
    }

    private fun showKeyboard(view: View) {
        view.postDelayed({
            try {
                val imm = getSystemService(INPUT_METHOD_SERVICE) as InputMethodManager
                imm.showSoftInput(view, InputMethodManager.SHOW_IMPLICIT)
            } catch (_: Exception) {
                // Some TVs won't show soft keyboard; focus highlight still works.
            }
        }, 250)
    }

    private fun toast(msg: String) {
        Toast.makeText(this, msg, Toast.LENGTH_SHORT).show()
    }
}
