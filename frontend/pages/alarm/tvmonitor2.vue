<template>
  <div class="pageShell">

    <SosAlarmPopupMqtt @triggerUpdateDashboard="requestDashboardSnapshot()" />
    <!-- Header ONLY when NOT TV -->
    <!-- <HeaderBar :visible="!isTv" :companyName="companyName" subtitle="AKIL SECURITY AND ALARM SYSTEM" :logoSrc="logoSrc"
      :isConnected="isConnected" :timeText="headerTime" :dateText="headerDate" :muted="muted"
      :pageText="`${pageIndex + 1} / ${totalPages}`" :disablePaging="totalPages <= 1" :showLogout="!!$auth?.user"
      @toggleMute="toggleMute" @prevPage="prevPage" @nextPage="nextPage" @logout="logout" /> -->

    <!-- Common body for ALL -->
    <SosMonitorBody :logoSrc="logoSrc" :isTv="isTv" :devices="devices" :pagedDevices="pagedDevices"
      :splitMode="splitMode" :totalPages="totalPages" :muted="muted" :showLogout="!!$auth?.user" @setSplit="setSplit"
      @toggleMute="toggleMute" @prevPage="prevPage" @nextPage="nextPage" @logout="logout" @ackRoom="udpateResponse" />

    <!-- Sound: ONLY for non-TV (web/desktop) -->
    <AudioSoundPlay v-if="!isTv && stats.activeSos > 0 && !muted" :key="totalSOSCount"
      :notificationsMenuItemsCount="stats.activeSos" />
  </div>
</template>

<script>
import mqtt from "mqtt";
import HeaderBar from "@/components/SOS/HeaderBar.vue";
import SosMonitorBody from "@/components/SOS/SosMonitorBody.vue";
import AudioSoundPlay from "@/components/Alarm/AudioSoundPlay.vue";

const SPLIT_MODE_KEY = "dash_split_mode";

export default {
  layout: "tvmonitorlayout",
  name: "TvSosFloor",
  components: { HeaderBar, SosMonitorBody, AudioSoundPlay },

  data() {
    return {
      // TV detect
      screenW: 0,
      screenH: 0,

      // UI
      splitMode: 16,
      pageIndex: 0,
      muted: false,

      // Data
      devices: [],
      loading: false,

      // Header clock
      headerTime: "",
      headerDate: "",
      _clock: null,

      // MQTT
      mqttUrl: "",
      client: null,
      isConnected: false,
      topics: { req: "", rooms: "", stats: "", reload: "", reloadconfig: "" },

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
      // Your rule: TV if width between 500 and 1000, OR width 0
      if (w === 0) return true;
      return w >= 500 && w <= 1000;
    },

    companyName() {
      return this.$auth?.user?.company?.name || "Company";
    },

    logoSrc() {
      return this.$auth?.user?.company?.logo || "/logo.png";
    },

    filteredDevices() {
      // Keep your filterMode logic if required; using "all" here by default
      return this.devices || [];
    },

    pageSize() {
      const s = Number(this.splitMode);
      return [4, 8, 16, 32, 64].includes(s) ? s : 16;
    },

    totalPages() {
      const n = this.filteredDevices.length || 0;
      return Math.max(1, Math.ceil(n / this.pageSize));
    },

    pagedDevices() {
      const start = this.pageIndex * this.pageSize;
      return this.filteredDevices.slice(start, start + this.pageSize);
    },

    activeSosCount() {
      return (this.devices || []).filter(d => d?.alarm_status === true).length;
    },

    stats() {
      return {
        totalPoints: this.devices.length,
        activeSos: this.activeSosCount,
        repeated: this.repeated,
        ackCount: this.ackCount,
        totalSOSCount: this.totalSOSCount,
        activeDisabledSos: this.activeDisabledSos
      };
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

    this.updateHeaderClock();
    this._clock = setInterval(() => this.updateHeaderClock(), 1000);

    this.mqttUrl = process.env.MQTT_SOCKET_HOST;
    this.connectMqtt();
  },

  beforeDestroy() {
    window.removeEventListener("resize", this.readScreen);
    if (this._clock) clearInterval(this._clock);
    this.disconnectMqtt();
  },

  methods: {
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

    toggleMute() {
      this.muted = !this.muted;

      // On TV: also stop/start AndroidBridge alarm immediately
      if (this.isTv) {
        try {
          if (this.muted) AndroidBridge.stopAlarm();
          else if (this.activeSosCount > 0) AndroidBridge.startAlarm();
        } catch (e) { }
      }
    },

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

    safeLsGet(key) {
      try { return window?.localStorage?.getItem(key) ?? null; } catch (e) { return null; }
    },
    safeLsSet(key, value) {
      try { window?.localStorage?.setItem(key, value); } catch (e) { }
    },

    // ===== MQTT (keep your existing topics/payloads; below is skeleton) =====
    connectMqtt() {
      if (this.client) return;
      if (!this.mqttUrl) return;

      const companyId = this.$auth?.user ? this.$auth?.user?.company_id : Number(process.env.TV_COMPANY_ID || 0);
      if (!companyId) return;

      this.topics.req = `tv/${companyId}/dashboard/request`;
      this.topics.rooms = `tv/${companyId}/dashboard/rooms`;
      this.topics.stats = `tv/${companyId}/dashboard/stats`;
      this.topics.reload = `tv/reload`;
      this.topics.reloadconfig = `${process.env.MQTT_DEVICE_CLIENTID}/${companyId}/message`;

      this.client = mqtt.connect(this.mqttUrl, { reconnectPeriod: 3000, keepalive: 30, clean: true });

      this.client.on("connect", () => {
        this.isConnected = true;
        this.client.subscribe([this.topics.rooms, this.topics.stats, this.topics.reload, this.topics.reloadconfig], { qos: 0 }, () => {
          this.requestDashboardSnapshot();
        });
      });

      this.client.on("message", this.onMqttMessage);
      this.client.on("close", () => (this.isConnected = false));
      this.client.on("offline", () => (this.isConnected = false));
      this.client.on("error", () => (this.isConnected = false));
    },

    disconnectMqtt() {
      try {
        if (!this.client) return;
        this.client.removeListener("message", this.onMqttMessage);
        this.client.end(true);
        this.client = null;
        this.isConnected = false;
      } catch (e) { }
    },

    requestDashboardSnapshot() {
      if (!this.client || !this.isConnected) return;

      const companyId = this.$auth?.user ? this.$auth?.user?.company_id : Number(process.env.TV_COMPANY_ID || 0);
      const securityId = this.$auth?.user?.security?.id || 0;

      const payload = {
        reqId: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
        params: { company_id: companyId, securityId }
      };

      this.client.publish(this.topics.req, JSON.stringify(payload), { qos: 0, retain: false });
    },

    onMqttMessage(topic, payload) {
      let msg;
      try { msg = JSON.parse(payload.toString()); } catch (e) { return; }

      if (topic === this.topics.rooms) {
        const data = msg?.data;
        const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
        this.devices = this.normalizeRooms(list);
        this.updateDurationAll();

        // TV sound control only (web uses AudioSoundPlay)
        if (this.isTv) {
          try {
            if (this.muted) {
              AndroidBridge.stopAlarm();
            } else if (this.activeSosCount > 0) {
              AndroidBridge.startAlarm();
            } else {
              AndroidBridge.stopAlarm();
            }
          } catch (e) { }
        }
      }

      if (topic === this.topics.stats) {
        const s = msg?.data || {};
        this.repeated = s.repeated || 0;
        this.ackCount = s.ackCount || 0;
        this.totalSOSCount = s.totalSOSCount || 0;
        this.activeDisabledSos = s.activeDisabledSos || 0;
      }
    },

    // ===== room normalization + duration (use your existing) =====
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

    normalizeRooms(list = []) {
      return list.map((r) => {
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

    formatHHMMSS(totalSeconds) {
      const hh = Math.floor(totalSeconds / 3600);
      const mm = Math.floor((totalSeconds % 3600) / 60);
      const ss = totalSeconds % 60;
      return `${String(hh).padStart(2, "0")}:${String(mm).padStart(2, "0")}:${String(ss).padStart(2, "0")}`;
    },

    updateDurationAll() {
      const nowMs = Date.now();
      this.devices.forEach((d, idx) => {
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

    // Ack publish (use your existing topic/payload)
    udpateResponse(alarmId) {
      if (!alarmId) return;
      const companyId = this.$auth?.user ? this.$auth?.user?.company_id : Number(process.env.TV_COMPANY_ID || 0);
      const payload = {
        reqId: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
        params: { company_id: companyId, alarmId }
      };

      try {
        if (this.client && this.isConnected) {
          this.client.publish(`tv/${companyId}/dashboard_alarm_response`, JSON.stringify(payload), { qos: 0, retain: false });
        }
      } catch (e) { }

      this.requestDashboardSnapshot();
    }
  }
};
</script>

<style scoped>
.pageShell {
  width: 100%;
  height: 100vh;
  overflow: hidden;
}
</style>
