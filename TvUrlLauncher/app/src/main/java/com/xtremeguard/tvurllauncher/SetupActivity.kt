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

    private lateinit var urlInput: EditText
    private lateinit var saveOpenBtn: Button

    private val DEFAULT_URL = "http://192.168.2.28:3000/alarm/tvmonitor1"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // If URL already exists, go straight to WebView
        val savedUrl = Prefs.getUrl(this)
        if (savedUrl.isNotBlank()) {
            openWebView(savedUrl)
            finish()
            return
        }

        setContentView(R.layout.activity_setup)

        urlInput = findViewById(R.id.urlInput)
        saveOpenBtn = findViewById(R.id.saveOpenBtn)

        // Prefill default URL and focus input
        urlInput.setText(DEFAULT_URL)
        urlInput.setSelection(urlInput.text.length)
        urlInput.requestFocus()
        showKeyboard(urlInput)

        saveOpenBtn.setOnClickListener {
            val raw = urlInput.text.toString().trim()
            if (raw.isEmpty()) {
                Toast.makeText(this, "Enter a URL", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            val normalized = normalizeUrl(raw)
            Prefs.setUrl(this, normalized)

            openWebView(normalized)
            finish()
        }
    }

    private fun normalizeUrl(input: String): String {
        val v = input.trim()
        if (v.startsWith("http://", true) || v.startsWith("https://", true)) return v
        return "http://$v"
    }

    private fun openWebView(url: String) {
        val i = Intent(this, WebViewActivity::class.java)
        i.putExtra("url", url)
        startActivity(i)
    }

    private fun showKeyboard(view: View) {
        // On many Android TV devices, soft keyboard may not appear unless a TV keyboard is installed,
        // but this will still request it and ensure focus is correct.
        view.postDelayed({
            try {
                val imm = getSystemService(INPUT_METHOD_SERVICE) as InputMethodManager
                imm.showSoftInput(view, InputMethodManager.SHOW_IMPLICIT)
            } catch (_: Exception) {
                // ignore
            }
        }, 250)
    }
}
