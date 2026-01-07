<template>
  <div class="pageShell">
    <v-snackbar v-model="snackbar" :timeout="3000" elevation="24">
      {{ snackbarText }}
    </v-snackbar>
    <SosAlarmPopupMqtt @triggerUpdateDashboard="requestDashboardSnapshot()" />
    <!-- Header only when NOT TV -->
    <!-- <HeaderBar v-if="!isTv" :logo="logo" :companyName="companyName" :headerTime="headerTime" :headerDate="headerDate"
      :isConnected="isConnected" :muted="muted" @toggleMute="toggleMute" /> -->

    <!-- Body is common for all devices -->
    <SosMonitorBody :isTv="isTv" :splitMode="splitMode" :pageIndex="pageIndex" :totalPages="totalPages"
      :devices="devices" :pagedDevices="pagedDevices" :activeAlarmRooms="activeAlarmRooms" :muted="muted"
      :bellBlink="bellBlink" @setSplit="setSplit" @toggleMute="toggleMute" @nextPage="nextPage" @prevPage="prevPage"
      @logout="logout" @ack="submitAck" @bellSeen="markBellSeen" />

    <!-- Desktop sound only (and only for UNACK alarms) -->
    <AudioSoundPlay v-if="!isTv && activeUnackedCount > 0 && !muted && !soundOverride" :key="totalSOSCount"
      :notificationsMenuItemsCount="activeUnackedCount" />
  </div>
</template>

<script>
import mqtt from "mqtt";
import AudioSoundPlay from "@/components/Alarm/AudioSoundPlay.vue";
import HeaderBar from "@/components/SOS/HeaderBar.vue";
import SosMonitorBody from "@/components/SOS/SosMonitorBody.vue";

const SPLIT_MODE_KEY = "dash_split_mode";

export default {
  layout: "tvmonitorlayout",
  name: "TvMonitor2",
  components: { AudioSoundPlay, HeaderBar, SosMonitorBody },

  data() {
    return {
      // screen
      screenW: 0,
      screenH: 0,

      // ui
      splitMode: 16,
      pageIndex: 0,
      muted: false,
      bellSeen: false,

      // data
      devices: [],
      loading: false,

      // snackbar
      snackbar: false,
      snackbarText: "",

      // timers
      _clock: null,
      _durationTimer: null,
      _autoPager: null,
      _soundOverrideTimer: null,
      soundOverride: false,

      headerTime: "",
      headerDate: "",

      // mqtt
      mqttUrl: "",
      client: null,
      isConnected: false,
      topics: { req: "", rooms: "", stats: "", reload: "", reloadconfig: "" },
      _onMessageBound: null,

      // stats
      repeated: 0,
      ackCount: 0,
      totalSOSCount: 0,
      activeDisabledSos: 0
    };
  },

  computed: {
    isTv() {
      const w = Number(this.screenW || 0);
      if (w === 0) return true;
      return w >= 500 && w <= 1000;
    },

    logo() {
      return this.$auth?.user?.company?.logo || "/logo.png";
    },
    companyName() {
      return this.$auth?.user?.company?.name || "Company";
    },

    pageSize() {
      const s = Number(this.splitMode);
      return [4, 8, 16, 32, 64].includes(s) ? s : 16;
    },

    totalPages() {
      const n = (this.devices || []).length;
      return Math.max(1, Math.ceil(n / this.pageSize));
    },

    pagedDevices() {
      const start = this.pageIndex * this.pageSize;
      return (this.devices || []).slice(start, start + this.pageSize);
    },

    activeAlarmRooms() {
      // show in drawer: ALL alarms that are ON (both ack and unacked)
      return (this.devices || []).filter((d) => d && d.alarm_status === true);
    },

    activeUnackedCount() {
      // sound should be for NEW/unack only
      return (this.devices || []).filter(
        (d) => d && d.alarm_status === true && !d?.alarm?.responded_datetime
      ).length;
    },

    bellBlink() {
      return this.activeAlarmRooms.length > 0 && !this.bellSeen;
    }
  },

  watch: {
    totalPages() {
      if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;
    }
  },

  created() {
    this.$vuetify.theme.dark = true;

    const savedSplit = Number(this.safeLsGet(SPLIT_MODE_KEY));
    if ([4, 8, 16, 32, 64].includes(savedSplit)) this.splitMode = savedSplit;
  },

  mounted() {
    this.readScreen();
    window.addEventListener("resize", this.readScreen);

    // clock
    this.updateHeaderClock();
    this._clock = setInterval(this.updateHeaderClock, 1000);

    // duration update (per second)
    this._durationTimer = setInterval(this.updateDurationAll, 1000);

    // mqtt: set url FIRST, then connect ONCE
    this.mqttUrl = process.env.MQTT_SOCKET_HOST || "";
    this.connectMqtt();

    // auto pagination every 5s
    this.startAutoPagination();
  },

  beforeDestroy() {
    window.removeEventListener("resize", this.readScreen);

    try {
      if (this._clock) clearInterval(this._clock);
      if (this._durationTimer) clearInterval(this._durationTimer);
      this.stopAutoPagination();
      this.disconnectMqtt();
      this.clearSoundOverride();
    } catch (e) { }
  },

  methods: {
    // -----------------------------
    // Auto pagination (every 5s)
    // -----------------------------
    startAutoPagination() {
      this.stopAutoPagination();
      this._autoPager = setInterval(() => {
        if (this.totalPages <= 1) return;
        this.pageIndex = (this.pageIndex + 1) % this.totalPages;
      }, 5000);
    },
    stopAutoPagination() {
      if (this._autoPager) {
        clearInterval(this._autoPager);
        this._autoPager = null;
      }
    },

    // -----------------------------
    // UI actions
    // -----------------------------
    setSplit(n) {
      this.splitMode = n;
      this.pageIndex = 0;
      this.safeLsSet(SPLIT_MODE_KEY, String(n));
    },

    nextPage() {
      if (this.totalPages <= 1) return;
      this.pageIndex = (this.pageIndex + 1) % this.totalPages;
    },

    prevPage() {
      if (this.totalPages <= 1) return;
      this.pageIndex = (this.pageIndex - 1 + this.totalPages) % this.totalPages;
    },

    logout() {
      this.$router.push("/logout");
    },

    markBellSeen() {
      this.bellSeen = true;
    },

    toggleMute() {
      this.muted = !this.muted;
      this.applySoundPolicy();
    },

    // -----------------------------
    // Stop sound immediately (TV + Desktop)
    // called after submit acknowledgement
    // -----------------------------
    stopSoundNow() {
      // TV
      if (this.isTv) {
        try {
          AndroidBridge.stopAlarm();
        } catch (e) { }
      }

      // Desktop: force AudioSoundPlay off briefly
      this.soundOverride = true;
      if (this._soundOverrideTimer) clearTimeout(this._soundOverrideTimer);
      this._soundOverrideTimer = setTimeout(() => {
        this.soundOverride = false;
      }, 10000); // 10s safety window
    },

    clearSoundOverride() {
      if (this._soundOverrideTimer) clearTimeout(this._soundOverrideTimer);
      this._soundOverrideTimer = null;
      this.soundOverride = false;
    },

    // Apply current sound policy based on alarms/mute/device type
    applySoundPolicy() {
      // TV: use AndroidBridge only
      if (this.isTv) {
        try {
          if (this.muted) return AndroidBridge.stopAlarm();
          if (this.activeUnackedCount > 0) return AndroidBridge.startAlarm();
          return AndroidBridge.stopAlarm();
        } catch (e) { }
      }
      // Desktop uses AudioSoundPlay condition; nothing else needed here
    },

    // -----------------------------
    // ACK submit
    // -----------------------------
    submitAck(alarmId) {
      if (!alarmId) return;

      const companyId = this.$auth?.user
        ? this.$auth?.user?.company_id
        : Number(process.env.TV_COMPANY_ID || 0);

      const payload = {
        reqId: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
        params: { company_id: companyId, alarmId }
      };

      try {
        if (this.client && this.isConnected) {
          this.client.publish(
            `tv/${companyId}/dashboard_alarm_response`,
            JSON.stringify(payload),
            { qos: 0, retain: false }
          );
        }
      } catch (e) { }

      // stop sound immediately after submit (your requirement)
      this.stopSoundNow();

      // refresh snapshot quickly
      this.requestDashboardSnapshot();
    },

    // -----------------------------
    // Screen + header clock
    // -----------------------------
    readScreen() {
      try {
        this.screenW = window.innerWidth || 0;
        this.screenH = window.innerHeight || 0;
      } catch (e) {
        this.screenW = 0;
        this.screenH = 0;
      }
    },

    updateHeaderClock() {
      const d = new Date();
      this.headerTime = d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
      this.headerDate = d.toISOString().slice(0, 10);
    },

    // -----------------------------
    // MQTT (IMPORTANT FIXES)
    // 1) set mqttUrl before connect
    // 2) bind onMessage handler so `this` is Vue instance
    // 3) set unique clientId
    // -----------------------------
    connectMqtt() {
      if (this.client) return;

      if (!this.mqttUrl) {
        this.snackbar = true;
        this.snackbarText = "MQTT_SOCKET_HOST missing in env";
        return;
      }

      const companyId = this.$auth?.user
        ? this.$auth?.user?.company_id
        : Number(process.env.TV_COMPANY_ID || 0);

      if (!companyId) {
        this.snackbar = true;
        this.snackbarText = "TV_COMPANY_ID missing in env";
        return;
      }

      this.topics.req = `tv/${companyId}/dashboard/request`;
      this.topics.rooms = `tv/${companyId}/dashboard/rooms`;
      this.topics.stats = `tv/${companyId}/dashboard/stats`;
      this.topics.reload = `tv/reload`;
      this.topics.reloadconfig = `${process.env.MQTT_DEVICE_CLIENTID}/${companyId}/message`;

      const clientId = `tvmonitor2-${companyId}-${Date.now()}-${Math.random().toString(16).slice(2)}`;

      this.client = mqtt.connect(this.mqttUrl, {
        reconnectPeriod: 3000,
        keepalive: 30,
        clean: true,
        clientId
      });

      // bind message handler (critical)
      this._onMessageBound = (t, p) => this.onMqttMessage(t, p);

      this.client.on("connect", () => {
        this.isConnected = true;

        this.client.subscribe(
          [this.topics.rooms, this.topics.stats, this.topics.reload, this.topics.reloadconfig],
          { qos: 0 },
          (err) => {
            if (err) return;
            this.requestDashboardSnapshot();
          }
        );
      });

      this.client.on("message", this._onMessageBound);
      this.client.on("close", () => (this.isConnected = false));
      this.client.on("offline", () => (this.isConnected = false));
      this.client.on("error", () => (this.isConnected = false));
    },

    disconnectMqtt() {
      try {
        if (!this.client) return;
        if (this._onMessageBound) this.client.removeListener("message", this._onMessageBound);
        this.client.end(true);
        this.client = null;
        this.isConnected = false;
        this._onMessageBound = null;
      } catch (e) { }
    },

    requestDashboardSnapshot() {
      if (!this.client || !this.isConnected) return;

      const companyId = this.$auth?.user
        ? this.$auth?.user?.company_id
        : Number(process.env.TV_COMPANY_ID || 0);

      const securityId = this.$auth?.user?.security?.id || 0;

      const payload = {
        reqId: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
        params: {
          company_id: companyId,
          securityId: securityId || null
        }
      };

      this.client.publish(this.topics.req, JSON.stringify(payload), { qos: 0, retain: false });
    },

    onMqttMessage(topic, payload) {
      // strict filter
      if (
        topic !== this.topics.rooms &&
        topic !== this.topics.stats &&
        topic !== this.topics.reload &&
        topic !== this.topics.reloadconfig
      ) return;

      let msg = null;
      try {
        msg = JSON.parse(payload.toString());
      } catch (e) {
        return;
      }

      if (topic === this.topics.rooms) {
        const data = msg?.data;
        const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
        this.devices = this.normalizeRooms(list);
        this.updateDurationAll();

        if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;

        // apply sound policy whenever rooms update
        this.applySoundPolicy();
        return;
      }

      if (topic === this.topics.reload) {
        try { window.location.reload(); } catch (e) { }
        return;
      }

      if (topic === this.topics.reloadconfig) {
        this.requestDashboardSnapshot();
        return;
      }

      if (topic === this.topics.stats) {
        const s = msg?.data || {};
        this.repeated = s.repeated || 0;
        this.ackCount = s.ackCount || 0;
        this.totalSOSCount = s.totalSOSCount || 0;
        this.activeDisabledSos = s.activeDisabledSos || 0;

        // apply sound policy whenever stats update
        this.applySoundPolicy();
      }
    },

    // -----------------------------
    // Normalize + duration
    // -----------------------------
    toBool(v) {
      if (v === true) return true;
      if (v === false) return false;
      const s = String(v ?? "").trim().toUpperCase();
      return s === "1" || s === "TRUE" || s === "ON" || s === "YES";
    },

    parseStartMs(dateStr) {
      if (!dateStr) return null;
      const raw = String(dateStr).trim();
      if (raw.includes("T")) {
        const ms = Date.parse(raw);
        return Number.isFinite(ms) ? ms : null;
      }
      const clean = raw.replace(/\.\d+$/, "");
      const isoLocal = clean.replace(" ", "T");
      const ms = Date.parse(isoLocal);
      return Number.isFinite(ms) ? ms : null;
    },

    formatHHMMSS(totalSeconds) {
      const hh = Math.floor(totalSeconds / 3600);
      const mm = Math.floor((totalSeconds % 3600) / 60);
      const ss = totalSeconds % 60;
      return `${String(hh).padStart(2, "0")}:${String(mm).padStart(2, "0")}:${String(ss).padStart(2, "0")}`;
    },

    normalizeRooms(list = []) {
      return (list || []).map((r) => {
        const alarm = r.alarm || null;

        const alarm_status =
          typeof r.alarm_status === "boolean"
            ? r.alarm_status
            : this.toBool(r.alarm_status ?? alarm?.alarm_status ?? r.status);

        const startMs = alarm?.alarm_start_datetime
          ? this.parseStartMs(alarm.alarm_start_datetime)
          : r.alarm_start_datetime
            ? this.parseStartMs(r.alarm_start_datetime)
            : null;

        return { ...r, alarm, alarm_status, startMs, duration: "" };
      });
    },

    updateDurationAll() {
      const nowMs = Date.now();
      (this.devices || []).forEach((d, idx) => {
        if (!d) return;

        if (d.alarm_status !== true || !d.startMs) {
          if (d.duration) this.$set(this.devices, idx, { ...d, duration: "" });
          return;
        }

        const diffSec = Math.max(0, Math.floor((nowMs - d.startMs) / 1000));
        const hhmmss = this.formatHHMMSS(diffSec);

        if (d.duration !== hhmmss) {
          this.$set(this.devices, idx, { ...d, duration: hhmmss });
        }
      });
    },

    // -----------------------------
    // localstorage safe
    // -----------------------------
    safeLsGet(key) {
      try { return window?.localStorage?.getItem(key) ?? null; } catch (e) { return null; }
    },
    safeLsSet(key, value) {
      try { window?.localStorage?.setItem(key, value); } catch (e) { }
    }
  }
};
</script>

<style scoped>
.pageShell {
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: linear-gradient(180deg, #0a0e16, #0e1420);
}
</style>
