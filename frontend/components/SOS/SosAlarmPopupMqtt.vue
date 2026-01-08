<template>
  <div>
    <!-- SOS Alarm Popup (UI only) -->
    <v-dialog v-model="dialog2" max-width="520" persistent>
      <v-card class="sosCard pa-2">
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2" color="red">mdi-bell-alert</v-icon>
          <div class="font-weight-bold">SOS Alarm</div>
          <v-spacer />
          <v-chip small color="red" dark>{{ current?.status || "ON" }}</v-chip>
        </v-card-title>

        <v-card-text>
          <v-alert type="error" dense text class="mb-3">
            Emergency call received. Please respond immediately.
          </v-alert>

          <v-list dense>
            <v-list-item>
              <v-list-item-content>
                <v-list-item-title class="font-weight-bold">Room</v-list-item-title>
                <v-list-item-subtitle>
                  {{ current?.name || "-" }} (Room ID: {{ current?.roomId ?? "-" }})
                </v-list-item-subtitle>
              </v-list-item-content>
              <v-list-item-icon>
                <v-icon color="blue">mdi-bed</v-icon>
              </v-list-item-icon>
            </v-list-item>

            <v-list-item>
              <v-list-item-content>
                <v-list-item-title class="font-weight-bold">Duration</v-list-item-title>
                <v-list-item-subtitle class="font-weight-bold red--text">
                  {{ currentDuration }}
                </v-list-item-subtitle>
              </v-list-item-content>
              <v-list-item-icon>
                <v-icon color="orange">mdi-timer</v-icon>
              </v-list-item-icon>
            </v-list-item>
          </v-list>
        </v-card-text>

        <v-divider />

        <v-card-actions>
          <v-spacer />
          <!-- UI close ONLY -->
          <v-btn text color="grey" @click="closeDialogOnly">
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script>
import mqtt from "mqtt";

export default {
  name: "SosAlarmPopupMqtt",

  props: {
    // allowedSerials: { type: Array, default: () => [] },
    // mqttUrl: {
    //   type: String,
    //   default: () => process.env.MQTT_SOCKET_HOST || "wss://YOUR_BROKER:8083/mqtt",
    // },
  },

  data() {
    return {
      mqttUrl: null,
      allowedSerials: [],
      dialog2: false,
      client: null,
      isConnected: false,

      dialog: false,
      current: null,

      activeMap: {}, // serial:id -> alarm
      queue: [],

      tick: null,
      currentDuration: "00:00:00",
    };
  },

  // watch: {
  //   // 🔔 Trigger dashboard refresh ONLY when popup closes
  //   dialog(val, oldVal) {
  //     // if (oldVal === true && val === false)
  //     {
  //       this.$emit("triggerUpdateDashboard");
  //     }
  //   },
  // },

  async mounted() {

    // alert("Connected");


    // if ((this.allowedSerials.length))
    {
      this.mqttUrl = process.env.MQTT_SOCKET_HOST;
      this.connectMqtt();
    }

    await this.getDevicesFromApi();

    // Duration timer
    this.tick = setInterval(this.updateCurrentDuration, 1000);
  },

  // ❗ IMPORTANT
  // NO disconnectMqtt() here
  // MQTT remains alive even if popup closes or page navigates

  methods: {

    async getDevicesFromApi() {

      await this.loadAllowedSerials();

      return false;

      // this.loading = true;
      // this.apiError = "";

      try {
        const { data } = await this.$axios.get("device-list", {
          params: {
            company_id: this.$auth?.user?.company_id || process.env.TV_COMPANY_ID,

          },
        });

        // supports array or paginator {data:[...]}
        this.allowedSerials = data.map(d => d.serial_number);

        console.log(" this.allowedSerials", this.allowedSerials);


      } catch (err) {
        console.log(" this.allowedSerials Err", err);

        // this.apiError =
        //   err?.response?.data?.message ||
        //   err?.message ||
        //   "Failed to load dashboard rooms";
      } finally {
        // this.loading = false;
      }
    },


    async fetchDeviceListViaMqtt() {
      const companyId =
        this.$auth?.user?.company_id || process.env.TV_COMPANY_ID;

      const reqTopic = `tv/${companyId}/device-list/get`;
      const respTopic = `tv/${companyId}/device-list/resp`;

      console.log("reqTopic", reqTopic);


      // unique request id
      const requestId = `dl_${Date.now()}_${Math.random().toString(16).slice(2, 8)}`;

      // Ensure we are subscribed BEFORE publishing
      await this.mqttSubscribeOnce(respTopic);

      return new Promise((resolve, reject) => {
        const timeoutMs = 1000 * 50;

        const onMessage = (topic, payload) => {
          if (topic !== respTopic) return;

          let msg;
          try {
            msg = JSON.parse(payload.toString());

            // console.log("msg", msg);

          } catch (e) {
            return; // ignore non-json
          }

          // only accept the matching response
          if (msg?.requestId !== requestId) return;

          // cleanup listener + timeout
          cleanup();

          if (!msg?.ok) {
            reject(new Error(msg?.message || "Device list request failed"));
            return;
          }

          const list = Array.isArray(msg?.data)
            ? msg.data
            : Array.isArray(msg?.data?.data)
              ? msg.data.data
              : [];

          resolve(list);
        };

        const t = setTimeout(() => {
          cleanup();
          reject(new Error("MQTT device list timeout"));
        }, timeoutMs);

        const cleanup = () => {
          clearTimeout(t);
          if (this.client?.off) this.client.off("message", onMessage);
          else this.client?.removeListener?.("message", onMessage);
        };

        // attach listener
        this.client.on("message", onMessage);

        // publish request
        const reqPayload = {
          requestId,
          company_id: companyId,
          ts: Date.now(),
        };

        this.client.publish(reqTopic, JSON.stringify(reqPayload), { qos: 1 }, (err) => {
          if (err) {
            cleanup();
            reject(err);
          }
        });
      });
    },

    // helper: subscribe and wait until broker acknowledges (mqtt.js callback)
    mqttSubscribeOnce(topic) {
      return new Promise((resolve, reject) => {
        if (!this.client) return reject(new Error("MQTT client not available"));
        this.client.subscribe(topic, { qos: 1 }, (err) => {
          if (err) reject(err);
          else resolve();
        });
      });
    },

    async loadAllowedSerials() {
      try {
        const list = await this.fetchDeviceListViaMqtt();

        this.allowedSerials = list
          .map(d => d?.serial_number)
          .filter(Boolean);

        console.log("this.allowedSerials", this.allowedSerials);
      } catch (err) {
        console.log("this.allowedSerials Err", err);
      }
    },


    // ================= MQTT =================
    connectMqtt() {
      if (this.client) return;

      this.client = mqtt.connect(this.mqttUrl, {
        reconnectPeriod: 3000,
        keepalive: 30,
        clean: true,
      });

      this.client.on("connect", () => {
        console.log(" this.isConnected", "Connected");

        this.isConnected = true;
        this.client.subscribe("xtremesos/+/sosalarm", { qos: 1 });
      });
      // alert("Connected")
      this.client.on("message", this.onMqttMessage);
      this.client.on("close", () => (this.isConnected = false));
      this.client.on("offline", () => (this.isConnected = false));
      this.client.on("error", () => (this.isConnected = false));
    },

    onMqttMessage(topic, payload) {

      // console.log("payload", payload);
      let msg;
      try {
        msg = JSON.parse(payload.toString());
      } catch (e) {
        // console.log("onMqttMessage", e, msg);

        return;
      }

      if (!msg || msg.type !== "sos") {

        // console.log("msg.type", msg.type);

        return
      };
      if (!msg.serialNumber || msg.id == null) {

        console.log("msg.id", msg.id);

        return
      };

      // Filter by company devices
      if (
        this.allowedSerials.length &&
        !this.allowedSerials.includes(msg.serialNumber)
      ) {
        return;
      }

      const key = `${msg.serialNumber}:${msg.id}`;
      const status = String(msg.status).toUpperCase();

      // alert("Connected" + msg.serialNumber);

      this.$emit("triggerUpdateDashboard");



      if (status === "ON") {
        const startedAtMs = Date.now();

        this.$set(this.activeMap, key, {
          ...msg,
          status: "ON",
          startedAtMs,
        });

        if (!this.queue.includes(key) && this.currentKey() !== key) {
          this.queue.push(key);
        }

        if (!this.dialog) {
          this.showNextAlarm();
        }
      }

      if (status === "OFF") {
        const wasCurrent = this.currentKey() === key;

        this.$delete(this.activeMap, key);
        this.queue = this.queue.filter(k => k !== key);

        if (wasCurrent) {
          this.dialog = false;
          this.current = null;
          this.currentDuration = "00:00:00";
          setTimeout(this.showNextAlarm, 200);
        }
      }
    },

    // ================= UI / QUEUE =================
    currentKey() {
      return this.current
        ? `${this.current.serialNumber}:${this.current.id}`
        : null;
    },

    showNextAlarm() {
      while (this.queue.length) {
        const key = this.queue.shift();
        if (this.activeMap[key]) {
          this.current = this.activeMap[key];
          this.dialog = true;
          return;
        }
      }

      const keys = Object.keys(this.activeMap);
      if (keys.length) {
        this.current = this.activeMap[keys[0]];
        this.dialog = true;
      }
    },

    closeDialogOnly() {
      // UI ONLY
      this.dialog = false;
    },

    // ================= TIMER =================
    updateCurrentDuration() {
      if (!this.current?.startedAtMs) {
        this.currentDuration = "00:00:00";
        return;
      }

      const diffSec = Math.floor((Date.now() - this.current.startedAtMs) / 1000);
      this.currentDuration = this.formatHHMMSS(Math.max(0, diffSec));
    },

    formatHHMMSS(sec) {
      const h = Math.floor(sec / 3600);
      const m = Math.floor((sec % 3600) / 60);
      const s = sec % 60;
      return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
    },
  },
};
</script>

<style scoped>
.sosCard {
  border: 2px solid rgba(239, 68, 68, 0.9);
  box-shadow: 0 0 18px rgba(239, 68, 68, 0.18);
  border-radius: 12px;
}
</style>
