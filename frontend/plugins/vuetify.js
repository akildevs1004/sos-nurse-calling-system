// src/plugins/vuetify.js
import Vue from "vue";
import Vuetify from "vuetify/lib";

Vue.use(Vuetify);

export default new Vuetify({
  theme: {
    options: { customProperties: true }, // enables CSS variables
    themes: {
      light: {
        // primary: "#1976D2",
        // background: "#f5f7fa",
      },
      dark: {
        // // primary: "#90caf9",
        // // background: "#121212",
        // background: "#10103d", // Background for the entire app (v-main, v-app)
        // surface: "#10154d", // Used for cards, sheets, dialogs , Header Tag
        // primary: "#10154d", // Primary brand color (toolbars, buttons, highlights)
        // secondary: "#03DAC6", // Secondary accent (chips, smaller highlights)
        // accent: "#FF4081", // Accent color (optional highlight color)
        // error: "#CF6679", // Error color (e.g. inputs, alerts)
        // info: "#2196F3", // Informational color (info alerts, chips)
        // success: "#4CAF50", // Success color (green)
        // warning: "#FB8C00", // Warning color (orange)
        // "on-primary": "#000000", // Text color used on top of `primary`
        // "on-secondary": "#000000", // Text color used on top of `secondary`
        // "on-surface": "#FFFFFF", // Text color used on top of surfaces/backgrounds
        // "default-font-color": "#FFF",
        // "default-font-color-reverse": "#000",
      },
      blue: {
        // primary: "#1E88E5",
        // secondary: "#42A5F5",
        // accent: "#82B1FF",
        // error: "#FF5252",
        // info: "#2196F3",
        // success: "#4CAF50",
        // warning: "#FFC107",
        // background: "#e3f2fd", // custom blue background
      },
    },
  },
});
