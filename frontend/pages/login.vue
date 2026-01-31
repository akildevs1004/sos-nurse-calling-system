<template>
  <div class="loginRoot" :class="[isTv ? 'tvRoot' : 'webRoot']">
    <!-- ===== WhatsApp OTP Dialog ===== -->
    <v-dialog persistent v-model="dialogWhatsapp" :width="isTv ? tvDialogWidth : 600">
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

    <!-- ===== TV/Compact Layout ===== -->
    <div v-if="isTv && this.screenW > 600" class="tvStage">
      <v-card class="tvCard" outlined :style="tvCardStyle">
        <div class="tvGrid" :style="tvGridStyle">
          <!-- LEFT: Logo / Title -->
          <div class="tvLeft" :style="tvLeftStyle">
            <v-img class="tvLogo" :style="tvLogoStyle" src="/logo22.png" contain />
            <div class="tvWelcome" :style="tvWelcomeStyle">
              Welcome To <span class="tvBrand">Xtreme Guard - SOS</span>
            </div>

            <div class="tvSupport" :style="tvSupportStyle">
              <div class="tvSupportLine">
                Support:
                <a target="_blank" class="supportLink"
                  href="https://wa.me/971529048025?text=Hello%20Xtreme%20Guard.%20I%20need%20your%20support.">
                  <v-icon small>mdi-whatsapp</v-icon>
                </a>
                <a class="supportLink" href="tel:+971529048025">+971 52 904 8025</a>
              </div>
              <div class="tvSupportLine">
                <a class="supportLink" href="mailto:support@xtremeguard.org">support@xtremeguard.org</a>
              </div>
            </div>
          </div>

          <!-- RIGHT: Form -->
          <div class="tvRight" :style="tvRightStyle">
            <v-form ref="form" v-model="valid" lazy-validation autocomplete="off">
              <v-text-field label="Email" v-model="credentials.email" dense outlined type="email"
                prepend-inner-icon="mdi-account" append-icon="mdi-at" autocomplete="off" :disabled="loading"
                class="tvField" :style="tvFieldStyle" />

              <v-text-field label="Password" v-model="credentials.password" dense outlined prepend-inner-icon="mdi-lock"
                :append-icon="show_password ? 'mdi-eye' : 'mdi-eye-off'" :type="show_password ? 'text' : 'password'"
                autocomplete="off" :disabled="loading" class="tvField" :style="tvFieldStyle"
                @click:append="show_password = !show_password" />

              <div class="tvActionsRow" :style="tvActionsStyle">
                <v-btn text small @click="openForgotPassword" :disabled="loading">
                  Forgot password?
                </v-btn>
              </div>

              <div class="pt-1">
                <span v-if="msg" class="errorText">{{ msg }}</span>

                <v-btn :loading="loading" :disabled="loading" @click="login()" class="primaryBtn" block
                  :style="tvButtonStyle">
                  Login
                </v-btn>
              </div>

              <div class="tvFooter" :style="tvFooterStyle">
                Don't Have an Account? Contact Admin
              </div>
            </v-form>
          </div>
        </div>
      </v-card>
    </div>

    <!-- ===== Regular Website Layout ===== -->
    <v-row v-else class="webStage" style="height: 100%">
      <v-col cols="12" lg="5" class="webLeft">
        <div class="webCardWrap">
          <div class="logoWrap">
            <v-img class="webLogo" src="/logo22.png" contain />
            <h3 class="webWelcome">
              Welcome To <span class="webBrand">Xtreme Guard - SOS </span>
            </h3>
          </div>

          <v-form ref="form" v-model="valid" lazy-validation autocomplete="off">
            <v-text-field label="Email" v-model="credentials.email" dense outlined type="email"
              prepend-inner-icon="mdi-account" append-icon="mdi-at" autocomplete="off" :disabled="loading" />
            <v-text-field label="Password" v-model="credentials.password" dense outlined prepend-inner-icon="mdi-lock"
              :append-icon="show_password ? 'mdi-eye' : 'mdi-eye-off'" :type="show_password ? 'text' : 'password'"
              autocomplete="off" :disabled="loading" @click:append="show_password = !show_password" />

            <v-row>
              <v-col cols="6" />
              <v-col cols="6" class="text-right">
                <v-btn text small @click="openForgotPassword" :disabled="loading">
                  Forgot password?
                </v-btn>
              </v-col>
            </v-row>

            <div class="pt-2">
              <span v-if="msg" class="errorText">{{ msg }}</span>
              <v-btn :loading="loading" :disabled="loading" @click="login()" class="primaryBtn" block>
                Login
              </v-btn>
            </div>
          </v-form>

          <div class="text-center pt-3">Don't Have an Account? Contact Admin</div>

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

      <v-col cols="12" lg="7" class="webRight d-none d-lg-flex">
        <div class="about-content">
          <h3>About Xtreme Guard</h3>
          <div class="aboutText">
            Xtreme Guard is an innovative and comprehensive platform.
            <br />
            Monitoring humidity and temperature alongside fire alarms, smoke alarms, water leakage alarms, power-off
            alarms, and door open status monitors ensures full protection.
          </div>

          <h3 class="pt-6">Features</h3>
          <ul class="aboutList">
            <li>Temperature Monitoring</li>
            <li>Humidity Monitoring</li>
            <li>Fire/Smoke Detection Alarm Systems</li>
            <li>Water Leakage Alarm Systems</li>
            <li>Power-Off Alarm Systems</li>
            <li>Door Open Status Monitoring</li>
          </ul>

          <div class="pt-8">
            <h3>Technical Support</h3>
            <div class="aboutSupport">
              <a target="_blank" class="supportLink whiteLink"
                href="https://wa.me/971529048025?text=Hello%20XtremeGuard.%20I%20need%20your%20support.">
                <v-icon color="white" small>mdi-whatsapp</v-icon>
              </a>
              <a class="supportLink whiteLink" href="tel:+971529048025">+971 52 904 8025</a>
              <br />
              <a class="supportLink whiteLink" href="mailto:support@xtremeguard.org">support@xtremeguard.org</a>
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
    autoRedirecting: false,

    // responsive detection
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
    // TV mode detection:
    // width 900..1100 OR height < 700
    isTv() {


      const w = this.screenW || 0;
      const h = this.screenH || 0;

      console.log(w, h);

      return this.isTVUserAgent() || (w < 700) || (h < 900) || (w >= 900 && w <= 1100) || (h > 0 && h < 700) || (w === 0 && h === 0); // treat 0x0 as TV for server-side rendering
    },

    // Are saved credentials present?
    hasSavedLogin() {
      const email =
        this.$store?.state?.email || (process.client ? localStorage.getItem("saved_email") : "");
      const password =
        this.$store?.state?.password || (process.client ? localStorage.getItem("saved_password") : "");
      return !!(email && password);
    },

    // scale factor based on height, clamped
    s() {
      const h = this.screenH || 540;
      const raw = h / 540; // 960x540 baseline
      return Math.max(0.85, Math.min(1.25, raw));
    },

    tvDialogWidth() {
      return Math.round(520 * this.s);
    },

    // Dynamic styles for TV layout
    tvCardStyle() {
      const maxW = Math.min(this.screenW - 40, 1000);
      const maxH = Math.min(this.screenH - 40, 620);
      return {
        width: `95%`,
        height: `${Math.max(440, Math.min(560, maxH))}px`,
        borderRadius: `${Math.round(12 * this.s)}px`,
        padding: `${Math.round(16 * this.s)}px`,


      };
    },

    tvGridStyle() {
      return {
        height: "100%",
        display: "grid",
        gridTemplateColumns: "1fr 1.2fr",
        gap: `${Math.round(16 * this.s)}px`,
        alignItems: "center",
      };
    },

    tvLeftStyle() {
      return {
        height: "100%",
        display: "flex",
        flexDirection: "column",
        justifyContent: "center",
        padding: `${Math.round(10 * this.s)}px`,
      };
    },

    tvRightStyle() {
      return {
        height: "100%",
        display: "flex",
        flexDirection: "column",
        justifyContent: "center",
        padding: `${Math.round(10 * this.s)}px`,
      };
    },

    tvLogoStyle() {
      const w = Math.round(260 * this.s);
      const h = Math.round(90 * this.s);
      return {
        width: `${Math.max(190, Math.min(300, w))}px`,
        height: `${Math.max(60, Math.min(110, h))}px`,
      };
    },

    tvWelcomeStyle() {
      return {
        marginTop: `${Math.round(10 * this.s)}px`,
        fontSize: `${Math.round(20 * this.s)}px`,
        fontWeight: 700,
        color: "#111",
      };
    },

    tvSupportStyle() {
      return {
        marginTop: `${Math.round(18 * this.s)}px`,
        fontSize: `${Math.round(12 * this.s)}px`,
        color: "#333",
      };
    },

    tvFieldStyle() {
      const font = Math.round(16 * this.s);
      return { fontSize: `${font}px` };
    },

    tvActionsStyle() {
      return { marginTop: `${Math.round(4 * this.s)}px`, textAlign: "right" };
    },

    tvButtonStyle() {
      return { height: `${Math.round(48 * this.s)}px`, fontSize: `${Math.round(16 * this.s)}px` };
    },

    tvFooterStyle() {
      return {
        marginTop: `${Math.round(10 * this.s)}px`,
        fontSize: `${Math.round(12 * this.s)}px`,
        textAlign: "center",
        color: "#444",
      };
    },
  },

  created() {

  },

  async mounted() {
    await this.loadEnv();
    this.$vuetify.theme.dark = false;

    this.updateViewport();
    try {
      window.addEventListener("resize", this.updateViewport, { passive: true });
    } catch (e) { }


    this.$auth.logout();
    // Logout only for website (do NOT logout for TV, else auto-redirect will never work)
    if (!this.isTv) {
      try {
        this.$auth.logout();
      } catch (e) { }
    }

    this.verifyToken();

    // TV: auto redirect
    this.tvAutoRedirectIfSaved();

    await this.loadEnv();
  },

  beforeDestroy() {
    try {
      window.removeEventListener("resize", this.updateViewport);
    } catch (e) { }
  },

  methods: {
    async loadEnv() {
      // Load runtime env from store
      try {
        // 1. Call backend envsettings API
        const res = await this.$axios.get("/envsettings");

        // 2. Store env data in Vuex
        this.$store.commit("SET_ENV", res.data);

        // 3. Use it immediately if needed
        //console.log("MQTT HOST:", this.$store.state.env.MQTT_SOCKET_HOST);
        //console.log("COMPANY ID:", this.$store.state.env.TV_COMPANY_ID);


      } catch (e) {
        console.error("Failed to load env settings", e);
        // alert("Failed to load environment settings");
      }
    },
    isTVUserAgent() {
      try {
        if (navigator === undefined) return false;
        const ua = navigator.userAgent.toLowerCase();

        console.log("ua", ua);


        return (
          ua.includes("android tv") ||
          ua.includes("smart-tv") ||
          ua.includes("smarttv") ||
          ua.includes("googletv") ||
          ua.includes("hbbtv") ||
          ua.includes("tizen") ||
          ua.includes("webos") ||
          ua.includes("aft") // Amazon Fire TV
        );
      } catch (e) {
        return false;
      }
    },
    updateViewport() {
      try {
        this.screenW = window.innerWidth || 0;
        this.screenH = window.innerHeight || 0;
      } catch (e) {
        this.screenW = 0;
        this.screenH = 0;
      }
      // Re-check after resize (TV shells can resize after load)
      this.$nextTick(() => this.tvAutoRedirectIfSaved());
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

    async tvAutoRedirectIfSaved() {
      if (!this.isTv || this.autoRedirecting) return;

      console.log("this.$auth", this.$auth);


      // Consider token existence as logged-in
      const hasToken =
        !!this.$auth?.strategy?.token?.get() ||
        !!this.$auth?.$storage?.getUniversal?.("auth._token.local");

      if (this.$auth?.loggedIn || hasToken) {
        this.autoRedirecting = true;
        this.$router.replace("/alarm/tvmonitor1");
        return;
      }

      if (!this.hasSavedLogin) return;

      this.autoRedirecting = true;

      // Load saved credentials
      const email =
        this.$store?.state?.email || (process.client ? localStorage.getItem("saved_email") : "");
      const password =
        this.$store?.state?.password || (process.client ? localStorage.getItem("saved_password") : "");

      this.credentials.email = email || "";
      this.credentials.password = password || "";

      if (!this.credentials.email || !this.credentials.password) {
        this.autoRedirecting = false;
        return;
      }

      try {
        const ok = await this.login();
        if (ok) {
          this.$router.replace("/alarm/tvmonitor14");
          return;
        }
        this.autoRedirecting = false;
      } catch (e) {
        this.autoRedirecting = false;
      }
    },

    async isBackendUp() {
      try {
        // Adjust if your backend health endpoint differs
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

        // alert(this.isTv ? "TV/MQTT Login" : "Website/Backend Login");

        // If backend is down OR TV => MQTT

        console.log("this.isTv", this.isTv);

        if (this.isTv) {

          await this.loadEnv();

          let res = await this.mqttLoginVerify(this.credentials);
          console.log("res ERROR", res);
          res = res.data;


          if (!res || !res.status) {
            this.msg = res?.message + this.$store.state.env?.MQTT_SOCKET_HOST + " ERROR Detected." || "Invalid Login Details";
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

          // Treat security user as success
          // return res.user?.user_type === "security";

          if (res.user?.user_type === "security") {
            this.$vuetify.theme.dark = false;

            // redirect("/alarm/tvmonitor1");
            this.$router.push("/alarm/tvmonitor1");
          }



          else if (res.user?.user_type === "master")
            this.$router.push("/master/");


          else {
            this.$router.push("/alarm/dashboard");
            // redirect("/alarm/dashboard");
            this.$vuetify.theme.dark = false;
          }


          return;
        }

        // Backend UP => normal Nuxt Auth
        if (!this.$refs.form || !this.$refs.form.validate()) {
          this.snackbar = true;
          this.snackbarMessage = "Invalid Email and Password";
          return false;
        }

        // console.log("res.user?.user_type", res.user?.user_type);


        const { data } = await this.$auth.loginWith("local", { data: this.credentials });
        const user = data?.user || this.$auth.user;

        console.log("user", user);


        if (user?.user_type === "security")
          this.$router.push("/alarm/tvmonitor1");

        else if (user?.user_type === "master" || user?.user_type === "" || user?.is_master === true || user?.user_type === null)
          this.$router.push("/master/");

        else
          this.$router.push("/alarm/dashboard");

        return;

        if (user?.branch_id == 0 && user?.is_master == false) {
          this.snackbar = true;
          this.snackbarMessage = "You do not have Permission to access this page";
          this.msg = this.snackbarMessage;
          return false;
        }

        if (user?.role_id == 0 && user?.user_type == "employee") {
          try {
            // res.user?.user_type === "security"
            window.location.href = process.env.EMPLOYEE_APP_URL;
          } catch (e) { }
          return true;
        }

        // Save credentials for TV auto-login
        if (process.client) {
          localStorage.setItem("saved_email", this.credentials.email);
          localStorage.setItem("saved_password", this.credentials.password);
        }

        this.$router.replace("/alarm/dashboard");

        return true;
      } catch (e) {
        this.msg = e?.message + ' Error' || "Login failed";
        this.snackbar = true;
        this.snackbarMessage = this.msg;
        return false;
      } finally {
        this.loading = false;
      }
    },

    async mqttLoginVerify(credentials) {
      alert(this.$store.state.env?.MQTT_SOCKET_HOST);
      const host = this.$store.state.env?.MQTT_SOCKET_HOST;//process.env.MQTT_SOCKET_HOST;
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
        console.log("MQTT Login Timeout - Unable to connect MQTT " + host)
        const timeout = setTimeout(() => finish(new Error("MQTT Login Timeout")), 1000 * 10);

        try {
          client = mqtt.connect(host, { clientId, clean: true, connectTimeout: 1000 * 20 });
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
.loginRoot {
  min-height: 100vh;
}

/* Website background */
.webRoot {
  padding-top: 5%;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
}

@media (min-width: 1300px) {
  .webRoot {
    background-image: url("../static/login/bgimage3.png") !important;
  }
}

/* TV root */
.tvRoot {
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.tvStage {
  width: 95%;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* TV card + layout */
.tvCard {
  box-sizing: border-box;
}

.tvGrid {
  width: 100%;
}

.tvLogo {
  max-width: 100%;
}

/* Web layout */
.webLeft {
  padding: 0;
}

.webCardWrap {
  padding: 3rem !important;
  max-width: 500px;
  margin: auto;
  text-align: center;
}

.webLogo {
  width: 250px;
  margin: auto;
}

.webWelcome {
  padding-top: 18px;
  padding-bottom: 18px;
  color: #111;
}

.webBrand {
  font-size: 20px;
}

/* About panel */
.webRight {
  align-items: stretch;
}

.about-content {
  padding-left: 30%;
  padding-top: 1%;
  padding-right: 15%;
  color: #fff;
}

.aboutText,
.aboutList {
  font-weight: 300;
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
.supportLink {
  text-decoration: none;
  color: #111;
  margin-left: 6px;
}

.whiteLink {
  color: #fff !important;
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
