package com.xtremeguard.tvurllauncher

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.view.inputmethod.InputMethodManager
import android.widget.Button
import android.widget.EditText
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity

class SetupActivity : AppCompatActivity() {

    // ✅ DECLARE ALL VIEWS FIRST
    private lateinit var ipInput: EditText
    private lateinit var portInput: EditText
    private lateinit var pathInput: EditText
    private lateinit var saveOpenBtn: Button

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

        // ✅ MUST BE BEFORE findViewById
        setContentView(R.layout.activity_setup)

        // ✅ NOW INITIALIZE VIEWS
        ipInput = findViewById(R.id.ipInput)
        portInput = findViewById(R.id.portInput)
        pathInput = findViewById(R.id.pathInput)
        saveOpenBtn = findViewById(R.id.saveOpenBtn)

        // Load saved values (or defaults)
        ipInput.setText(Prefs.getIp(this))
        portInput.setText(Prefs.getPort(this))
        pathInput.setText(Prefs.getPath(this))

        // Focus first field for TV
        ipInput.requestFocus()
        showKeyboard(ipInput)

        saveOpenBtn.setOnClickListener {
            val ip = ipInput.text.toString().trim()
            val port = portInput.text.toString().trim()
            val pathRaw = pathInput.text.toString().trim()

            if (ip.isEmpty()) {
                toast("Enter IP address")
                ipInput.requestFocus()
                return@setOnClickListener
            }

            if (port.isEmpty()) {
                toast("Enter port number")
                portInput.requestFocus()
                return@setOnClickListener
            }

            val path = normalizePath(pathRaw)
            val url = "http://$ip:$port$path"

            // Save everything
            Prefs.setNetworkFields(this, ip, port, path)
            Prefs.setUrl(this, url)

            openWebView(url)
            finish()
        }
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
                val imm =
                    getSystemService(INPUT_METHOD_SERVICE) as InputMethodManager
                imm.showSoftInput(view, InputMethodManager.SHOW_IMPLICIT)
            } catch (_: Exception) {
            }
        }, 250)
    }

    private fun toast(msg: String) {
        Toast.makeText(this, msg, Toast.LENGTH_SHORT).show()
    }
}
