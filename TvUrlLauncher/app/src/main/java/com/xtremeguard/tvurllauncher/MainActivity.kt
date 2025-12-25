package com.xtremeguard.tvurllauncher

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity

class MainActivity : AppCompatActivity() {

    private lateinit var urlInput: EditText
    private lateinit var openBtn: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        // Hide system UI (full screen immersive)
        hideSystemUI()

        urlInput = findViewById(R.id.urlInput)
        openBtn = findViewById(R.id.openBtn)

        openBtn.setOnClickListener {
            val raw = urlInput.text.toString().trim()
            if (raw.isEmpty()) {
                Toast.makeText(this, "Please enter a URL", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            val url = normalizeUrl(raw)
            openExternalUrl(url)
        }
    }

    private fun normalizeUrl(input: String): String {
        // Allow: youtube.com/... or 192.168.1.10:3000
        return if (input.startsWith("http://", true) || input.startsWith("https://", true)) {
            input
        } else {
            "http://$input"
        }
    }

    private fun openExternalUrl(url: String) {
        val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
        try {
            startActivity(intent) // will open YouTube app or browser
        } catch (e: Exception) {
            Toast.makeText(this, "No app found to open this link", Toast.LENGTH_LONG).show()
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
}
