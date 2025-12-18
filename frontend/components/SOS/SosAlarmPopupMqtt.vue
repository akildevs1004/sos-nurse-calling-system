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
    allowedSerials: { type: Array, default: () => [] },
    mqttUrl: {
      type: String,
      default: () => process.env.MQTT_SOCKET_HOST || "wss://YOUR_BROKER:8083/mqtt",
    },
  },

  data() {
    return {
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

  mounted() {
    this.connectMqtt();

    // Duration timer
    this.tick = setInterval(this.updateCurrentDuration, 1000);
  },

  // ❗ IMPORTANT
  // NO disconnectMqtt() here
  // MQTT remains alive even if popup closes or page navigates

  methods: {
    // ================= MQTT =================
    connectMqtt() {
      if (this.client) return;

      this.client = mqtt.connect(this.mqttUrl, {
        reconnectPeriod: 3000,
        keepalive: 30,
        clean: true,
      });

      this.client.on("connect", () => {
        this.isConnected = true;
        this.client.subscribe("xtremesos/+/sosalarm", { qos: 1 });
      });

      this.client.on("message", this.onMqttMessage);
      this.client.on("close", () => (this.isConnected = false));
      this.client.on("offline", () => (this.isConnected = false));
      this.client.on("error", () => (this.isConnected = false));
    },

    onMqttMessage(topic, payload) {
      let msg;
      try {
        msg = JSON.parse(payload.toString());
      } catch {
        return;
      }

      if (!msg || msg.type !== "sos") return;
      if (!msg.serialNumber || msg.id == null) return;

      // Filter by company devices
      if (
        this.allowedSerials.length &&
        !this.allowedSerials.includes(msg.serialNumber)
      ) {
        return;
      }

      const key = `${msg.serialNumber}:${msg.id}`;
      const status = String(msg.status).toUpperCase();


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
