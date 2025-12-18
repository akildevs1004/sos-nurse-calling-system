<template>
  <div>
    <!-- SOS Alarm Popup -->
    <v-dialog v-model="dialog" max-width="520" persistent>
      <v-card :class="cardClass" class="pa-2">
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2" color="red">mdi-bell-alert</v-icon>
          <div class="font-weight-black">SOS Alarm</div>
          <v-spacer />
          <v-chip small color="red" dark>{{ current?.status || "ON" }}</v-chip>
        </v-card-title>

        <v-card-text>
          <v-alert type="error" dense text class="mb-3">
            Emergency call received. Please respond immediately.
          </v-alert>

          <v-list dense class="transparent">
            <v-list-item>
              <v-list-item-content>
                <v-list-item-title class="font-weight-bold">Device</v-list-item-title>
                <v-list-item-subtitle>{{ current?.serialNumber || "-" }}</v-list-item-subtitle>
              </v-list-item-content>
              <v-list-item-icon>
                <v-icon color="green" v-if="isConnected">mdi-wifi</v-icon>
                <v-icon color="red" v-else>mdi-wifi-off</v-icon>
              </v-list-item-icon>
            </v-list-item>

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
                <v-list-item-title class="font-weight-bold">Code</v-list-item-title>
                <v-list-item-subtitle>{{ current?.code || "-" }}</v-list-item-subtitle>
              </v-list-item-content>
              <v-list-item-icon>
                <v-icon color="grey">mdi-radiobox-marked</v-icon>
              </v-list-item-icon>
            </v-list-item>

            <v-list-item>
              <v-list-item-content>
                <v-list-item-title class="font-weight-bold">Duration</v-list-item-title>
                <v-list-item-subtitle class="font-weight-bold red--text">
                  {{ currentDuration || "00:00:00" }}
                </v-list-item-subtitle>
              </v-list-item-content>
              <v-list-item-icon>
                <v-icon color="orange">mdi-timer</v-icon>
              </v-list-item-icon>
            </v-list-item>
          </v-list>

          <div class="text-caption grey--text mt-2">
            Active alarms: {{ activeCount }} | Queue: {{ queue.length }}
          </div>
        </v-card-text>

        <v-divider />

        <v-card-actions class="pa-4">
          <v-btn text @click="mute = !mute">
            <v-icon left>{{ mute ? "mdi-volume-off" : "mdi-volume-high" }}</v-icon>
            {{ mute ? "Muted" : "Sound" }}
          </v-btn>

          <v-spacer />

          <!-- NOTE: This button is OPTIONAL. You requested auto-close on OFF.
               If you keep it, it only closes UI, does not publish OFF. -->
          <v-btn color="grey" text @click="closeDialogOnly">
            Close
          </v-btn>

          <!-- If you later want to publish ACK to backend/MQTT, add here -->
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
    // If you want to restrict to company devices only, pass serials list here.
    // If empty, we subscribe to xtremesos/+/sosalarm and show all.
    allowedSerials: { type: Array, default: () => [] },

    // Broker settings (or use ENV)
    mqttUrl: { type: String, default: () => process.env.MQTT_SOCKET_HOST || "wss://YOUR_BROKER:8083/mqtt" },
    // mqttUsername: { type: String, default: () => process.env.MQTT_USER || "" },
    // mqttPassword: { type: String, default: () => process.env.MQTT_PASS || "" },
  },

  data() {
    return {
      client: null,
      isConnected: false,

      dialog: false,
      current: null, // currently displayed alarm

      // Active alarms indexed by key "serialNumber:id"
      activeMap: {},

      // queue of keys to display (ensures popups in order)
      queue: [],

      // timer for duration display
      tick: null,
      currentDuration: "00:00:00",

      mute: false,
      audio: null,
    };
  },

  computed: {
    activeCount() {
      return Object.keys(this.activeMap).length;
    },
    cardClass() {
      return "sosCard";
    },
  },

  watch: {
    dialog(val, oldVal) {


      {
        this.$emit("triggerUpdateDashboard");
      }
    },
  },

  mounted() {
    // Optional: alarm sound (simple beep). Replace with your mp3 if required.
    this.audio = new Audio();
    this.audio.src =
      "data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAESsAACJWAAACABAAZGF0YQAAAAA="; // tiny silent placeholder
    // You can replace audio.src with a real sound file URL.

    this.connectMqtt();

    // update duration every second (HH:MM:SS)
    this.tick = setInterval(() => this.updateCurrentDuration(), 1000);
  },

  beforeDestroy() {
    if (this.tick) clearInterval(this.tick);
    this.disconnectMqtt();
  },

  methods: {
    // -------- MQTT --------
    connectMqtt() {
      if (this.client) return;

      this.client = mqtt.connect(this.mqttUrl, {
        username: this.mqttUsername,
        password: this.mqttPassword,
        reconnectPeriod: 3000,
        keepalive: 30,
        clean: true,
      });

      this.client.on("connect", () => {
        this.isConnected = true;

        // Multi-device support:
        // Subscribe wildcard topic to cover all device serials
        const topic = "xtremesos/+/sosalarm";
        this.client.subscribe(topic, { qos: 1 });

        // If you prefer subscribing only specific devices, do:
        // this.allowedSerials.forEach(sn => this.client.subscribe(`xtremesos/${sn}/sosalarm`, { qos: 1 }));
      });

      this.client.on("reconnect", () => (this.isConnected = false));
      this.client.on("close", () => (this.isConnected = false));
      this.client.on("offline", () => (this.isConnected = false));
      this.client.on("error", () => (this.isConnected = false));

      this.client.on("message", (topic, payload) => {
        this.onMqttMessage(topic, payload);
      });
    },

    disconnectMqtt() {
      if (!this.client) return;
      try {
        this.client.end(true);
      } catch (e) { }
      this.client = null;
      this.isConnected = false;
    },

    onMqttMessage(topic, payload) {
      let msg = null;
      try {
        msg = JSON.parse(payload.toString());
      } catch (e) {
        return;
      }

      // Expected payload example:
      // {"type":"sos","serialNumber":"XTSOS251000","id":1,"name":"Room1111","roomId":101,"status":"ON","code":"15728914","timestampMs":10921235}

      if (!msg || msg.type !== "sos") return;
      if (!msg.serialNumber || msg.id == null) return;

      // If restricting to company devices:
      if (this.allowedSerials.length && !this.allowedSerials.includes(msg.serialNumber)) return;

      const key = `${msg.serialNumber}:${msg.id}`;
      const status = String(msg.status || "").toUpperCase();

      if (status === "ON") {
        // store active alarm
        const startedAtMs = Date.now(); // no timezone. Use arrival time as start marker for popup duration
        this.$set(this.activeMap, key, {
          ...msg,
          status: "ON",
          startedAtMs,
        });

        // queue it if not already queued and not currently shown
        if (!this.queue.includes(key) && this.currentKey() !== key) {
          this.queue.push(key);
        }

        // if nothing shown, show immediately
        if (!this.dialog || !this.current) {
          this.showNextAlarm();
        }

        // play sound (optional)
        if (!this.mute) this.tryBeep();
      }

      if (status === "OFF") {
        // remove alarm
        const wasCurrent = this.currentKey() === key;

        if (this.activeMap[key]) this.$delete(this.activeMap, key);

        // remove from queue if still there
        this.queue = this.queue.filter((k) => k !== key);

        // if the alarm currently shown turned OFF -> close and show next
        if (wasCurrent) {
          this.dialog = false;
          this.current = null;
          this.currentDuration = "00:00:00";

          // show next after a short delay (UI smoother)
          setTimeout(() => this.showNextAlarm(), 200);
        }
      }
    },

    // -------- POPUP / QUEUE --------
    currentKey() {
      if (!this.current) return null;
      return `${this.current.serialNumber}:${this.current.id}`;
    },

    showNextAlarm() {
      // pick next from queue that still exists in activeMap
      while (this.queue.length) {
        const key = this.queue.shift();
        if (this.activeMap[key]) {
          this.current = this.activeMap[key];
          this.dialog = true;
          this.updateCurrentDuration();
          return;
        }
      }

      // if queue empty but there are active alarms, show any
      const keys = Object.keys(this.activeMap);
      if (keys.length) {
        const key = keys[0];
        this.current = this.activeMap[key];
        this.dialog = true;
        this.updateCurrentDuration();
        return;
      }

      // no alarms
      this.dialog = false;
      this.current = null;
      this.currentDuration = "00:00:00";
    },

    closeDialogOnly() {
      // This only closes popup UI; it does NOT publish OFF.
      // You requested: close on OFF. So usually you can remove this button.
      this.dialog = false;
    },

    // -------- DURATION (HH:MM:SS) --------
    updateCurrentDuration() {
      if (!this.current || !this.current.startedAtMs) {
        this.currentDuration = "00:00:00";
        return;
      }
      const diffSec = Math.max(0, Math.floor((Date.now() - this.current.startedAtMs) / 1000));
      this.currentDuration = this.formatHHMMSS(diffSec);
    },

    formatHHMMSS(totalSeconds) {
      const hh = Math.floor(totalSeconds / 3600);
      const mm = Math.floor((totalSeconds % 3600) / 60);
      const ss = totalSeconds % 60;
      return `${String(hh).padStart(2, "0")}:${String(mm).padStart(2, "0")}:${String(ss).padStart(2, "0")}`;
    },

    tryBeep() {
      // If you attach a real sound file, call this.audio.play()
      // Keeping safe; browsers may block autoplay until user interaction.
      try {
        if (this.audio) this.audio.play().catch(() => { });
      } catch (e) { }
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
