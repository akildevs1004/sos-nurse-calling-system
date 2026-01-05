<template>
  <div class="loginRoot">
    <!-- ===== WhatsApp OTP Dialog ===== -->
    <v-dialog persistent v-model="dialogWhatsapp" :width="isTv ? 520 : 600">
      <v-card>
        <v-card-title dense class="otpTitle">
          Whatsapp Verification
          <v-spacer></v-spacer>
          <v-icon @click="dialogWhatsapp = false" outlined dark color="white">
            mdi mdi-close-circle
          </v-icon>
        </v-card-title>

        <v-card-text class="otpBody">
          <v-row dense class="pb-2">
            <v-col cols="12" class="text-center">
              <h2 class="brandTitle">MyTime2Cloud</h2>
            </v-col>
          </v-row>

          <h5 class="otpSubtitle">
            Two Step Whatsapp OTP Verification
            <v-icon color="green">mdi-whatsapp</v-icon>
          </h5>

          <p class="otpDesc">
            We sent a verification code to your mobile number. Enter the code below.
          </p>

          <div class="maskedNumber">
            {{ maskMobileNumber }}
          </div>

          <label class="otpLabel">Type your 6 Digit Security Code</label>

          <div class="otpInputWrap">
            <v-otp-input v-model="otp" length="6" :rules="requiredRules" :disabled="loading"></v-otp-input>
          </div>

          <div class="text-center pt-2">
            <span v-if="msg" class="errorText">{{ msg }}</span>

            <v-btn :loading="loading" :disabled="loading" @click="checkOTP(otp)" class="primaryBtn" block>
              Verify OTP
            </v-btn>
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- ===== Forgot Password Dialog ===== -->
    <v-dialog v-model="dialogForgotPassword" :width="isTv ? 420 : 400">
      <v-card>
        <v-card-title dense class="popup_background">
          Forgot Password
          <v-spacer></v-spacer>
          <v-icon @click="dialogForgotPassword = false" outlined>
            mdi mdi-close-circle
          </v-icon>
        </v-card-title>
        <v-card-text>
          <ForgotPassword />
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- ===== Snackbar ===== -->
    <v-snackbar v-model="snackbar" top="top" color="error" elevation="24">
      {{ snackbarMessage }}
    </v-snackbar>

    <!-- ===== SINGLE LAYOUT (default TV mode) ===== -->
    <v-row class="loginLayout" no-gutters>
      <!-- LEFT: Logo/Support (TV only) -->
      <v-col v-if="isTv" cols="12" md="5" class="stageCol">
        <div class="panel">
          <div class="logoWrap">
            <v-img src="/logo22.png" style="width:250px;height:50px;" contain />


            <h3 class="welcomeText">
              Welcome To <span class="brand">Xtreme Guard TV</span>
            </h3>
          </div>

          <div class="supportBlock">
            <div>
              For Technical Support:
              <a target="_blank" class="supportLink"
                href="https://wa.me/971529048025?text=Hello%20Xtreme%20Guard.%20I%20need%20your%20support.">
                <v-icon small>mdi-whatsapp</v-icon>
              </a>
              <a class="supportLink" href="tel:+971529048025">+971 52 904 8025</a>
            </div>
            <div>
              <a class="supportLink" href="mailto:support@xtremeguard.org">support@xtremeguard.org</a>
            </div>
          </div>
        </div>
      </v-col>

      <!-- RIGHT: Form (always) -->
      <!-- {{ isTv }} -->
      <v-col :cols="12" :md="isTv ? 7 : 12" class="stageCol">
        <div class="panel">
          <!-- Logo on top for NON-TV -->
          <div v-if="!isTv" class="logoWrap">
            <v-img class="appLogo" src="/logo22.png" contain />
            <h3 class="welcomeText">
              Welcome To <span class="brand">Xtreme Guard NOTV</span>
            </h3>
          </div>

          <v-form ref="form" v-model="valid" lazy-validation autocomplete="off">
            <v-text-field label="Email" v-model="credentials.email" dense outlined type="email"
              prepend-inner-icon="mdi-account" append-icon="mdi-at" autocomplete="off" :disabled="loading" />

            <v-text-field label="Password" v-model="credentials.password" dense outlined prepend-inner-icon="mdi-lock"
              :append-icon="show_password ? 'mdi-eye' : 'mdi-eye-off'" :type="show_password ? 'text' : 'password'"
              autocomplete="off" :disabled="loading" @click:append="show_password = !show_password" />

            <div class="actionsRow">
              <v-btn text small @click="openForgotPassword" :disabled="loading">
                Forgot password?
              </v-btn>
            </div>

            <div class="pt-2">
              <span v-if="msg" class="errorText">{{ msg }}</span>
              <v-btn :loading="loading" :disabled="loading" @click="login()" class="primaryBtn" block>
                Login
              </v-btn>
            </div>
          </v-form>

          <div class="text-center pt-3">Don't Have an Account? Contact Admin</div>

          <!-- Support block for NON-TV (optional, same classes) -->
          <div v-if="!isTv" class="supportBlock">
            <div>
              For Technical Support:
              <a target="_blank" class="supportLink"
                href="https://wa.me/971529048025?text=Hello%20Xtreme%20Guard.%20I%20need%20your%20support.">
                <v-icon small>mdi-whatsapp</v-icon>
              </a>
              <a class="supportLink" href="tel:+971529048025">+971 52 904 8025</a>
            </div>
            <div>
              <a class="supportLink" href="mailto:support@xtremeguard.org">support@xtremeguard.org</a>
            </div>
          </div>
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script>
import mqtt from "mqtt";
import ForgotPassword from "../components/ForgotPassword.vue";

export default {
  layout: "login",
  components: { ForgotPassword },

  data: () => ({
    // ✅ DEFAULTS: light + TV
    isTvMode: true,
    screenW: 0,
    screenH: 0,

    // dialogs
    dialogForgotPassword: false,
    dialogWhatsapp: false,

    // otp
    otp: "",
    userId: "",
    maskMobileNumber: "",

    // ui
    valid: true,
    loading: false,
    snackbar: false,
    snackbarMessage: "",
    show_password: false,
    msg: "",

    requiredRules: [(v) => !!v || "Required"],

    credentials: {
      email: "",
      password: "",
      source: "admin",
    },
  }),

  computed: {
    // ✅ pure getter
    isTv() {
      return this.isTvMode;
    },
  },

  created() {
    this.$vuetify.theme.dark = false;
    // ✅ DEFAULT LIGHT THEME (no flash)
    this.forceLightTheme();

    this.verifyToken();
  },

  mounted() {
    this.$vuetify.theme.dark = false;
    // ✅ keep light always
    this.forceLightTheme();

    // Measure viewport (optional). We keep default TV unless clearly desktop.
    this.$nextTick(() => {
      this.updateViewport();
      setTimeout(this.updateViewport, 200);
      setTimeout(this.updateViewport, 500);
    });

    try {
      window.addEventListener("resize", this.updateViewport, { passive: true });
    } catch (e) { }

    // Logout only for website (do NOT logout for TV)
    if (!this.isTvMode) {
      try {
        this.$auth.logout();
      } catch (e) { }
    }
  },

  beforeDestroy() {
    try {
      window.removeEventListener("resize", this.updateViewport);
    } catch (e) { }
  },

  methods: {
    forceLightTheme() {
      try {
        if (this.$vuetify?.theme) this.$vuetify.theme.dark = false;
      } catch (e) { }
    },

    // Optional hard override by query: ?tv=1 or ?tv=0
    applyTvQueryOverride() {
      try {
        const q = this.$route?.query?.tv;
        if (q === "1" || q === 1) return true;
        if (q === "0" || q === 0) return false;
      } catch (e) { }
      return null;
    },

    updateViewport() {
      if (!process.client) return;

      const w = window.innerWidth || document.documentElement.clientWidth || 0;
      const h = window.innerHeight || document.documentElement.clientHeight || 0;

      this.screenW = w;
      this.screenH = h;

      // 1) query override if present
      const override = this.applyTvQueryOverride();
      if (override !== null) {
        this.isTvMode = override;
      } else {
        // ✅ DEFAULT TV MODE
        // only switch to NON-TV if clearly desktop
        const looksLikeDesktop = w > 1200 && h > 800;
        this.isTvMode = w == 0 || !looksLikeDesktop;
      }

      // Always light
      this.forceLightTheme();
    },

    openForgotPassword() {
      this.dialogForgotPassword = true;
    },

    verifyToken() {
      if (this.$route.query.email && this.$route.query.password) {
        this.credentials.email = this.$route.query.email;
        this.credentials.password = this.$route.query.password;
        this.loginWithOTP();
      }
    },

    hideMobileNumber(inputString) {
      if (typeof inputString !== "string" || inputString.length < 4) return inputString;
      const regex = /^(.*)(\d{5})$/;
      const matches = inputString.match(regex);
      if (!matches) return inputString;
      return "*".repeat(matches[1].length) + matches[2];
    },

    async isBackendUp() {
      try {
        await this.$axios.get("/test", { timeout: 2000, params: { _t: Date.now() } });
        return true;
      } catch (e) {
        return false;
      }
    },

    checkOTP(otp) {
      if (!otp) {
        alert("Enter OTP");
        return;
      }

      const payload = { userId: this.userId };
      this.loading = true;

      this.$axios
        .post(`check_otp/${otp}`, payload)
        .then(({ data }) => {
          if (!data.status) {
            alert("Invalid OTP. Please try again");
            return;
          }
          this.dialogWhatsapp = false;
          this.login();
        })
        .catch(() => alert("OTP check failed"))
        .finally(() => (this.loading = false));
    },

    loginWithOTP() {
      if (!this.$refs.form || !this.$refs.form.validate()) {
        this.snackbar = true;
        this.snackbarMessage = "Invalid Email and Password";
        return;
      }

      this.loading = true;
      this.msg = "";

      this.$axios
        .post("loginwith_otp", this.credentials)
        .then(({ data }) => {
          if (!data.status) {
            this.snackbar = true;
            this.snackbarMessage = "Invalid Login Details";
            this.msg = "Invalid Login Details";
            return;
          }

          if (data.user_id) {
            if (data.enable_whatsapp_otp == 1) {
              this.dialogWhatsapp = true;
              this.userId = data.user_id;
              if (data.mobile_number) this.maskMobileNumber = this.hideMobileNumber(data.mobile_number);
              return;
            }
            this.login();
            return;
          }

          this.snackbar = true;
          this.snackbarMessage = "Invalid Login Details";
          this.msg = "Invalid Login Details";
        })
        .catch(() => {
          this.snackbar = true;
          this.snackbarMessage = "Login OTP request failed";
        })
        .finally(() => (this.loading = false));
    },

    async login() {
      this.msg = "";
      this.snackbar = false;
      this.snackbarMessage = "";
      this.loading = true;

      try {
        const backendOk = await this.isBackendUp();

        // If backend is down OR TV => MQTT
        if (!backendOk || this.isTvMode) {
          let res = await this.mqttLoginVerify(this.credentials);
          res = res.data;

          if (!res || !res.status) {
            this.msg = res?.message || "Invalid Login Details";
            this.snackbar = true;
            this.snackbarMessage = this.msg;
            return false;
          }

          if (res.token) {
            this.$auth.strategy.token.set(`Bearer ${res.token}`);
            if (this.$auth.strategy?.token?.sync) this.$auth.strategy.token.sync();
          }
          if (res.user) this.$auth.setUser(res.user);

          // Save credentials for TV auto-login
          if (process.client) {
            localStorage.setItem("saved_email", this.credentials.email);
            localStorage.setItem("saved_password", this.credentials.password);
          }

          return res.user?.user_type === "security";
        }

        // Backend UP => normal Nuxt Auth
        if (!this.$refs.form || !this.$refs.form.validate()) {
          this.snackbar = true;
          this.snackbarMessage = "Invalid Email and Password";
          return false;
        }

        const { data } = await this.$auth.loginWith("local", { data: this.credentials });
        const user = data?.user || this.$auth.user;

        if (user?.branch_id == 0 && user?.is_master == false) {
          this.snackbar = true;
          this.snackbarMessage = "You do not have Permission to access this page";
          this.msg = this.snackbarMessage;
          return false;
        }

        if (user?.role_id == 0 && user?.user_type == "employee") {
          try {
            window.location.href = process.env.EMPLOYEE_APP_URL;
            return true;
          } catch (error) { }
        }

        // Save credentials for TV auto-login
        if (process.client) {
          localStorage.setItem("saved_email", this.credentials.email);
          localStorage.setItem("saved_password", this.credentials.password);
        }

        return true;
      } catch (e) {
        alert(e?.message || "Login failed");
        this.msg = e?.message || "Login failed";
        this.snackbar = true;
        this.snackbarMessage = this.msg;
        return false;
      } finally {
        this.loading = false;
      }
    },

    async mqttLoginVerify(credentials) {
      const host = process.env.MQTT_SOCKET_HOST;
      const clientId = "vue-client-" + Math.random().toString(16).substr(2, 8);

      const reqTopic = "tv/auth/req";
      const respBase = "tv/auth/resp/";
      const correlationId = Date.now().toString(36) + Math.random().toString(36).slice(2);
      const respTopic = respBase + correlationId;

      return new Promise((resolve, reject) => {
        let client;
        let finished = false;

        const finish = (err, data) => {
          if (finished) return;
          finished = true;

          try {
            if (client) {
              client.unsubscribe(respTopic);
              client.end(true);
            }
          } catch (e) { }

          err ? reject(err) : resolve(data);
        };

        const timeout = setTimeout(() => finish(new Error("MQTT login timeout")), 6000);

        try {
          client = mqtt.connect(host, { clientId, clean: true, connectTimeout: 4000 });
        } catch (e) {
          clearTimeout(timeout);
          finish(new Error("MQTT connect failed"));
          return;
        }

        client.once("error", (err) => {
          clearTimeout(timeout);
          finish(new Error(err?.message || "MQTT error"));
        });

        client.once("connect", () => {
          client.subscribe(respTopic, { qos: 1 }, (err) => {
            if (err) {
              clearTimeout(timeout);
              finish(new Error("MQTT subscribe failed"));
              return;
            }

            client.on("message", (topic, payload) => {
              if (topic !== respTopic) return;
              clearTimeout(timeout);

              let res;
              try {
                res = JSON.parse(payload.toString());
              } catch (e) {
                finish(new Error("Invalid MQTT response"));
                return;
              }
              finish(null, res);
            });

            const payload = {
              action: "login",
              correlationId,
              replyTo: respTopic,
              credentials: {
                email: credentials.email,
                password: credentials.password,
                source: credentials.source || "admin",
              },
              ts: Date.now(),
            };

            client.publish(reqTopic, JSON.stringify(payload), { qos: 1 });
          });
        });
      });
    },
  },
};
</script>

<style scoped>
/* Root */
.loginRoot {
  min-height: 100vh;
}

/* One layout for all */
.loginLayout {
  min-height: 100vh;
}

/* Both columns share same class (no TV-specific classes) */
.stageCol {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}

/* Panel uses the same styling for TV + Web */
.panel {
  width: 100%;
  max-width: 520px;
  text-align: center;
}

/* Same logo class everywhere */
.appLogo {
  width: 260px;
  margin: 0 auto;
}

/* Same headings */
.welcomeText {
  padding-top: 18px;
  padding-bottom: 18px;
  color: #111;
  font-weight: 600;
}

.brand {
  font-size: 20px;
  font-weight: 700;
}

/* Actions row */
.actionsRow {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

/* Buttons */
.primaryBtn {
  background-color: #6c2ac2 !important;
  color: #fff !important;
  font-weight: 700;
  height: 48px;
}

/* Errors */
.errorText {
  display: inline-block;
  color: #ff6b6b;
  margin-bottom: 8px;
}

/* Support links */
.supportBlock {
  margin-top: 16px;
}

.supportLink {
  text-decoration: none;
  color: #111;
  margin-left: 6px;
}

/* OTP styles */
.otpTitle {
  background-color: #6946dd;
  color: #fff !important;
}

.otpBody {
  padding-top: 18px;
}

.maskedNumber {
  font-size: 26px;
  font-weight: 700;
  margin: 8px 0 14px;
}

.otpLabel {
  font-weight: 700;
  font-size: 16px;
  display: block;
  margin-bottom: 10px;
}
</style>
