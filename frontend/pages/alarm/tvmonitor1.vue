<!-- TvDashboard.vue -->
<template>
  <div class="tv-page" :class="deviceClass">
    <v-snackbar v-model="snackbar" :timeout="3000" elevation="24" class="center-snackbar">
      {{ snackbarResponse }}
    </v-snackbar>

    <SosAlarmPopupMqtt @triggerUpdateDashboard="requestDashboardSnapshot()" />

    <!-- TV LAYOUT (NO SCROLL) -->
    <div class="tvLayout">
      <!-- HEADER -->
      <div class="tvHeader">
        <div class="d-flex justify-end align-center" v-if="$auth?.user">
          <span class="text-caption mr-3">
            Welcome,
            <strong>
              {{ $auth.user?.security?.first_name || "" }}
              {{ $auth.user?.security?.last_name || "" }}
            </strong>
          </span>

          <!-- Screen size indicator -->
          <div class="tvResolution mr-3">
            {{ screenWidth }} × {{ screenHeight }} px
          </div>

          <v-btn x-small outlined color="error" @click="logout" style="margin-right:10px">
            Logout
          </v-btn>
        </div>
      </div>

      <!-- STATS: ONE ROW -->
      <v-row dense class="tvStatsRow">
        <v-col v-for="s in statCards" :key="s.key" cols="2" class="tvStatCol">
          <v-card outlined class="pa-3 roomCard stat-card1 tvStatCard" :class="s.cardClass">
            <div class="d-flex align-center">
              <div class="min-w-0">
                <div class="text-caption text-uppercase font-weight-bold stat-title" :class="s.textClass">
                  {{ s.title }}
                </div>

                <div class="d-flex align-baseline mt-1">
                  <div class="text-h4 font-weight-bold">{{ s.value }}</div>
                  <div class="ml-2 stat-sub" :class="s.textClass">
                    {{ s.sub }}
                  </div>
                </div>
              </div>

              <v-spacer />

              <v-icon class="stat-icon" :class="s.textClass">{{ s.icon }}</v-icon>
            </div>

            <v-progress-linear v-if="s.key === 'avg'" class="mt-1" height="4" :value="avgResponsePct" rounded
              style="margin-top:-10px!important" />
          </v-card>
        </v-col>
      </v-row>

      <!-- TOOLBAR -->
      <div class="tvToolbar d-flex align-center">
        <v-btn-toggle v-model="filterMode" mandatory class="mr-4">
          <v-btn small value="all">All</v-btn>
          <v-btn small value="on">SOS ON</v-btn>
          <v-btn small value="off">SOS OFF</v-btn>
        </v-btn-toggle>

        <div class="text-caption grey--text mr-2">Cards Per Row</div>
        <v-btn-toggle v-model="roomsPerRow" mandatory dense>
          <v-btn small :value="2" @click="roomsPerRow = 2">2</v-btn>
          <v-btn small :value="4" @click="roomsPerRow = 4">4</v-btn>
          <v-btn small :value="6" @click="roomsPerRow = 6">6</v-btn>
        </v-btn-toggle>

        <v-spacer />

        <!-- Pagination -->
        <div class="d-flex align-center">
          <v-btn small outlined class="mr-2" @click="prevPage" :disabled="pageIndex === 0">Prev</v-btn>
          <div class="text-caption grey--text mr-2">
            Page {{ pageIndex + 1 }} / {{ totalPages }}
          </div>
          <v-btn small outlined @click="nextPage" :disabled="pageIndex >= totalPages - 1">Next</v-btn>
        </div>
      </div>

      <!-- ROOMS AREA (FILL REST OF SCREEN, NO SCROLL) -->
      <div class="tvRoomsArea">
        <div class="roomsGrid tvRoomsGrid" :style="roomsGridStyle">
          <div v-for="d in pagedDevices" :key="d.id || d.room_id" class="roomCell">
            <v-card outlined class="pa-2 roomCard roomCardIndividual tvRoomCard"
              :class="[cardClass(d), 'room-cols-' + roomsPerRow]">
              <!-- SINGLE LANE: Name | Signal | Alarm Icon | Status | Ack -->
              <div class="roomLane">
                <div class="laneName text-truncate">{{ d.name }}</div>

                <!-- Signal -->
                <v-icon class="laneIcon"
                  :color="d.device?.status_id == 1 ? 'green' : (d.device?.status_id == 2 ? 'red' : '')">
                  {{ d.device?.status_id == 1 ? "mdi-wifi" : (d.device?.status_id == 2 ? "mdi-wifi-off" : "mdi-wifi") }}
                </v-icon>

                <!-- Alarm Icon -->
                <v-icon v-if="d.alarm_status === true" class="laneIcon" color="red">mdi-bell</v-icon>
                <v-icon v-else class="laneIcon" color="grey">mdi-bell-outline</v-icon>

                <!-- Alarm Status -->
                <v-chip x-small class="laneChip"
                  :color="d.alarm_status === true ? (d.alarm?.responded_datetime ? '#f97316' : 'red') : 'grey'">
                  {{
                    d.alarm_status === true
                      ? (d.alarm?.responded_datetime ? "ACK" : "PENDING")
                      : "OK"
                  }}
                </v-chip>

                <!-- Ack button always present (disabled when not needed) -->
                <v-btn x-small class="laneAckBtn blink-btn"
                  :disabled="!(d.alarm_status === true && !d.alarm?.responded_datetime)"
                  @click="udpateResponse(d.alarm?.id)">
                  ACK
                </v-btn>
              </div>

              <!-- OPTIONAL: Duration line -->
              <div class="laneDurationWrap" v-if="d.alarm_status === true">
                <div class="laneDuration mono">{{ d.duration || "00:00:00" }}</div>
              </div>

              <div class="laneOkWrap" v-else>
                <v-icon color="green" class="okIcon">mdi-check-circle</v-icon>
                <span class="okText">No Active Call</span>
              </div>
            </v-card>
          </div>
        </div>

        <v-progress-linear v-if="loading" indeterminate height="3" class="my-2" />
      </div>
    </div>
  </div>
</template>

<script>
import mqtt from "mqtt";
import SosAlarmPopupMqtt from "@/components/SOS/SosAlarmPopupMqtt.vue";

const ROOMS_PER_ROW_KEY = "tv_rooms_per_row";

export default {
  layout: "tvmonitorlayout",
  name: "TvSosFloor",
  components: { SosAlarmPopupMqtt },

  data() {
    return {
      // UI
      filterMode: "all",
      devices: [],
      loading: false,

      snackbar: false,
      snackbarResponse: "",

      roomsPerRow: 4, // 2 | 4 | 6

      // TV paging (NO SCROLL)
      pageIndex: 0,
      tvRowsPerScreen: 3,

      // Screen size indicator
      screenWidth: 0,
      screenHeight: 0,

      // Stats
      avgResponseText: "00:00",
      avgResponsePct: 100,
      repeated: 0,
      ackCount: 0,
      totalSOSCount: 0,
      activeDisabledSos: 0,

      // params
      room: null,
      status: null,
      date_from: "",
      date_to: "",

      // duration timer
      timer: null,
      TIMER_MS: 1000,

      // MQTT
      mqttUrl: "",
      client: null,
      isConnected: false,
      reqId: "",
      topics: { req: "", rooms: "", stats: "", reload: "" },

      message: "",
      filterRoomTableIds: [],
    };
  },

  computed: {
    deviceType() {
      if (this.$vuetify.breakpoint.smAndDown) return "mobile";
      if (this.$vuetify.breakpoint.mdOnly) return "tablet";
      return "tv";
    },
    deviceClass() {
      return `is-${this.deviceType}`;
    },

    // IMPORTANT: This now applies for ALL sizes so 2/4/6 always works
    roomsGridStyle() {
      const cols = [2, 4, 6].includes(Number(this.roomsPerRow)) ? Number(this.roomsPerRow) : 4;
      return {
        "--rooms-cols": cols,
        "--card-max-w": "450px",
        "--card-max-h": "200px",
      };
    },

    activeSosCount() {
      return this.devices.filter((d) => d.alarm_status === true).length;
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
    },

    filteredDevices() {
      if (this.filterMode === "on") return this.devices.filter((d) => d.alarm_status === true);
      if (this.filterMode === "off") return this.devices.filter((d) => d.alarm_status === false);
      return this.devices;
    },

    // ===== NO-SCROLL PAGINATION =====
    pageSize() {
      const cols = [2, 4, 6].includes(Number(this.roomsPerRow)) ? Number(this.roomsPerRow) : 4;
      return cols * this.tvRowsPerScreen;
    },
    totalPages() {
      const n = this.filteredDevices?.length || 0;
      return Math.max(1, Math.ceil(n / this.pageSize));
    },
    pagedDevices() {
      const start = this.pageIndex * this.pageSize;
      return (this.filteredDevices || []).slice(start, start + this.pageSize);
    },

    statCards() {
      return [
        { key: "active", title: "Active SOS", value: this.stats.activeSos, sub: "Calls", icon: "mdi-bell-alert", cardClass: "stat-critical", textClass: "critical-text" },
        { key: "disabled", title: "Disabled", value: this.activeDisabledSos, sub: "Calls", icon: "mdi-wheelchair", cardClass: "stat-amber", textClass: "amber-text" },
        { key: "repeated", title: "Repeated", value: this.stats.repeated, sub: "Calls", icon: "mdi-alarm-light", cardClass: "stat-info", textClass: "info-text" },
        { key: "ack", title: "Acknowledged", value: this.stats.ackCount, sub: "Calls", icon: "mdi-check-circle", cardClass: "stat-success", textClass: "success-text" },
        { key: "total", title: "Total", value: this.stats.totalSOSCount, sub: "Calls", icon: "mdi-counter", cardClass: "stat-purple", textClass: "purple-text" },
        { key: "avg", title: "Avg Response", value: this.avgResponseText, sub: "min", icon: "mdi-timer", cardClass: "stat-teal", textClass: "teal-text" }
      ];
    }
  },

  watch: {
    roomsPerRow(v) {
      const n = Number(v);
      if ([2, 4, 6].includes(n)) localStorage.setItem(ROOMS_PER_ROW_KEY, String(n));
      this.pageIndex = 0;
    },
    filterMode() {
      this.pageIndex = 0;
    },
    filteredDevices() {
      if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;
    }
  },

  created() {
    this.$vuetify.theme.dark = true;

    const sosRooms = this.$auth?.user?.security?.sos_rooms ?? [];
    this.filterRoomTableIds = Array.isArray(sosRooms) ? sosRooms.map(r => r.id) : [];
  },

  mounted() {
    this.updateScreenSize();
    window.addEventListener("resize", this.updateScreenSize);

    const saved = Number(localStorage.getItem(ROOMS_PER_ROW_KEY));
    if ([2, 4, 6].includes(saved)) this.roomsPerRow = saved;

    this.timer = setInterval(() => this.updateDurationAll(), this.TIMER_MS);

    this.mqttUrl = process.env.MQTT_SOCKET_HOST;
    this.connectMqtt();
  },

  beforeDestroy() {
    window.removeEventListener("resize", this.updateScreenSize);
    try {
      if (this.timer) clearInterval(this.timer);
      this.disconnectMqtt();
    } catch (e) { }
  },

  methods: {
    updateScreenSize() {
      this.screenWidth = window.innerWidth;
      this.screenHeight = window.innerHeight;
    },

    // ===== PAGING =====
    nextPage() {
      if (this.pageIndex < this.totalPages - 1) this.pageIndex++;
    },
    prevPage() {
      if (this.pageIndex > 0) this.pageIndex--;
    },

    logout() {
      this.$router.push("/logout");
    },

    // ---------- MQTT ----------
    connectMqtt() {
      if (this.client) return;

      if (!this.mqttUrl) {
        this.snackbar = true;
        this.snackbarResponse = "MQTT_SOCKET_HOST missing in env";
        return;
      }

      const companyId = this.$auth?.user ? this.$auth?.user?.company_id : Number(process.env.TV_COMPANY_ID || 0);
      if (!companyId) {
        this.snackbar = true;
        this.snackbarResponse = "TV_COMPANY_ID missing in env";
        return;
      }

      this.topics.req = `tv/${companyId}/dashboard/request`;
      this.topics.rooms = `tv/${companyId}/dashboard/rooms`;
      this.topics.stats = `tv/${companyId}/dashboard/stats`;
      this.topics.reload = `tv/reload`;

      this.client = mqtt.connect(this.mqttUrl, {
        reconnectPeriod: 3000,
        keepalive: 30,
        clean: true
      });

      this.client.on("connect", () => {
        this.isConnected = true;

        this.client.subscribe([this.topics.rooms, this.topics.stats, this.topics.reload], { qos: 0 }, (err) => {
          if (err) {
            this.snackbar = true;
            this.snackbarResponse = "Server subscribe failed";
            return;
          }
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
        if (this.topics.rooms) this.client.unsubscribe(this.topics.rooms);
        if (this.topics.stats) this.client.unsubscribe(this.topics.stats);
        if (this.topics.reload) this.client.unsubscribe(this.topics.reload);

        this.client.end(true);
        this.client = null;
        this.isConnected = false;
      } catch (e) { }
    },

    requestDashboardSnapshot() {
      if (!this.client || !this.isConnected) return;

      this.reqId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
      const companyId = this.$auth?.user ? this.$auth?.user?.company_id : Number(process.env.TV_COMPANY_ID || 0);
      const securityId = this.$auth?.user?.security?.id || 0;

      const payload = {
        reqId: this.reqId,
        params: {
          company_id: companyId,
          date_from: this.date_from || null,
          date_to: this.date_to || null,
          roomType: this.room?.value || null,
          sosStatus: this.status || null,
          securityId: securityId || null
        }
      };

      this.client.publish(this.topics.req, JSON.stringify(payload), { qos: 0, retain: false });
    },

    onMqttMessage(topic, payload) {
      if (topic !== this.topics.rooms && topic !== this.topics.stats && topic !== this.topics.reload) return;

      let msg;
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
        return;
      }

      if (topic === this.topics.reload) {
        try { window.location.reload(); } catch (e) { }
        return;
      }

      if (topic === this.topics.stats) {
        const s = msg?.data || {};
        this.repeated = s.repeated || 0;
        this.ackCount = s.ackCount || 0;
        this.totalSOSCount = s.totalSOSCount || 0;
        this.activeDisabledSos = s.activeDisabledSos || 0;

        const m = Number(s.averageMinutes || 0);
        if (this.$dateFormat?.minutesToHHMM) {
          this.avgResponseText = this.$dateFormat.minutesToHHMM(m);
        } else {
          const hh = String(Math.floor(m / 60)).padStart(2, "0");
          const mm = String(m % 60).padStart(2, "0");
          this.avgResponseText = `${hh}:${mm}`;
        }

        const pct = Number(s.sla_percentage ?? 100);
        this.avgResponsePct = Math.max(0, Math.min(100, Number.isFinite(pct) ? pct : 100));
      }
    },

    cardClass(d) {
      return d?.alarm_status === true ? (d.alarm?.responded_datetime != null ? "cardAck" : "cardOn") : "cardOff";
    },

    // ---------- NORMALIZATION + DURATION ----------
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
      return list.map((r) => {
        const alarm = r.alarm || null;
        const alarm_status =
          typeof r.alarm_status === "boolean" ? r.alarm_status : this.toBool(r.alarm_status ?? alarm?.alarm_status ?? r.status);

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

    // ---------- ACK ----------
    async udpateResponse(alarmId) {
      if (!alarmId) return;

      this.loading = true;
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

      this.loading = false;
      this.requestDashboardSnapshot();
      alert("Acknowledgement is Received..");
    }
  }
};
</script>

<style scoped>
/* ===== PAGE ===== */
.tv-page {
  height: 100vh;
  overflow: hidden;
  padding: 10px 12px;
  box-sizing: border-box;
}

.tvLayout {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.tvHeader {
  flex: 0 0 auto;
  margin-bottom: 6px;
}

/* stats */
.tvStatsRow {
  flex: 0 0 auto;
  margin: 0;
}

.tvStatCol {
  padding-top: 0 !important;
  padding-bottom: 0 !important;
}

.tvStatCard {
  height: 86px;
  display: flex;
  align-items: center;
  overflow: hidden;
}

/* toolbar */
.tvToolbar {
  flex: 0 0 auto;
  margin-top: 6px;
  margin-bottom: 8px;
}

/* rooms */
.tvRoomsArea {
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}

/* IMPORTANT: roomsPerRow works on ALL screen sizes */
.roomsGrid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(var(--rooms-cols, 4), minmax(0, 1fr));
  justify-content: center;
  align-content: start;
}

.tvRoomsGrid {
  height: 100%;
  overflow: hidden;
  grid-auto-rows: minmax(0, 1fr);
}

.roomCell {
  display: flex;
  justify-content: center;
  align-items: stretch;
  min-height: 0;
}

/* cards */
.roomCard {
  border-radius: 12px;
  overflow: hidden;
}

.roomCardIndividual {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

/* HARD LIMIT CARD SIZE */
.tvRoomCard {
  width: 100%;
  max-width: var(--card-max-w, 450px);
  height: 100%;
  max-height: var(--card-max-h, 200px);
  overflow: hidden;
}

/* ===== SINGLE LANE ===== */
.roomLane {
  display: grid;
  grid-template-columns: 1fr auto auto auto auto;
  gap: 10px;
  align-items: center;
  width: 100%;
  min-height: 40px;
}

.laneName {
  font-weight: 900;
  font-size: clamp(13px, 1.4vh, 18px);
}

.laneIcon {
  font-size: clamp(14px, 1.4vw, 22px) !important;
  line-height: 1;
}

.laneChip {
  height: 20px;
  font-weight: 900;
  letter-spacing: 0.03em;
  padding: 0 8px;
}

.laneAckBtn {
  min-width: 52px !important;
  height: 22px !important;
  padding: 0 10px !important;
  font-weight: 900;
  letter-spacing: 0.06em;
}

/* optional duration */
.laneDurationWrap {
  margin-top: 8px;
  display: flex;
  justify-content: center;
}

.laneDuration {
  font-size: clamp(14px, 1.6vh, 22px);
  font-weight: 900;
  line-height: 1.1;
}

/* OK */
.laneOkWrap {
  margin-top: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
}

.okIcon {
  font-size: clamp(14px, 1.4vw, 22px) !important;
  line-height: 1;
}

.okText {
  font-weight: 800;
  font-size: clamp(11px, 1.2vh, 16px);
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.min-w-0 {
  min-width: 0;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

/* borders */
.cardOn {
  border: 2px solid rgba(239, 68, 68, 0.95);
  box-shadow: 0 0 18px rgba(239, 68, 68, 0.18);
}

.cardOff {
  border: 1px solid rgba(148, 163, 184, 0.35);
}

.cardAck {
  border: 2px solid #f97316;
}

/* blink */
@keyframes blink {
  0% {
    opacity: 1;
  }

  50% {
    opacity: 0.3;
  }

  100% {
    opacity: 1;
  }
}

.blink-btn {
  animation: blink 1s infinite;
}

/* stats icon */
.stat-icon {
  font-size: clamp(18px, 1.8vw, 34px);
  line-height: 1;
  opacity: 0.95;
}

/* stat colors */
.stat-critical {
  border-color: #ef4444 !important;
  background: rgba(239, 68, 68, 0.05);
}

.stat-amber {
  border-color: #f59e0b !important;
  background: rgba(245, 158, 11, 0.05);
}

.stat-success {
  border-color: #22c55e !important;
  background: rgba(34, 197, 94, 0.05);
}

.stat-info {
  border-color: #3b82f6 !important;
  background: rgba(59, 130, 246, 0.05);
}

.stat-purple {
  border-color: #a855f7 !important;
  background: rgba(168, 85, 247, 0.05);
}

.stat-teal {
  border-color: #14b8a6 !important;
  background: rgba(20, 184, 166, 0.05);
}

/* text colors */
.critical-text {
  color: #ef4444 !important;
}

.amber-text {
  color: #f59e0b !important;
}

.success-text {
  color: #22c55e !important;
}

.info-text {
  color: #3b82f6 !important;
}

.purple-text {
  color: #a855f7 !important;
}

.teal-text {
  color: #14b8a6 !important;
}

.stat-title {
  letter-spacing: 0.06em;
}

.stat-sub {
  font-weight: 600;
  opacity: 0.9;
}

/* resolution badge */
.tvResolution {
  font-size: 12px;
  padding: 4px 8px;
  border-radius: 6px;
  background: #1a2632;
  color: #e5e7eb;
  font-weight: 700;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

/* snackbar center */
.center-snackbar {
  position: fixed !important;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  z-index: 9999;
}
</style>
