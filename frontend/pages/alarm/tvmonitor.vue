<template>
  <div class="tv-page">
    <div class="text-center ma-2">
      <v-snackbar v-model="snackbar" top="top" elevation="24">
        {{ snackbarResponse }}
      </v-snackbar>
    </div>

    <SosAlarmPopupMqtt @triggerUpdateDashboard="RefreshDashboard()" />

    <!-- HEADER AREA (stats + filters) -->
    <div ref="header" class="tv-header">
      <!-- ================= TOP STATISTICS ================= -->
      <v-row dense>
        <v-col cols="12" sm="6" md="2">
          <v-card outlined class="pa-4 roomCard stat-card1 stat-critical">
            <div class="d-flex align-center">
              <div>
                <div class="text-caption text-uppercase font-weight-bold stat-title critical-text">
                  Active SOS
                </div>
                <div class="d-flex align-baseline mt-2">
                  <div class="text-h4 font-weight-bold">{{ stats.activeSos }}</div>
                  <div class="ml-2 stat-sub critical-text">Calls</div>
                </div>
              </div>
              <v-spacer />
              <v-icon class="stat-icon critical-text">mdi-bell-alert</v-icon>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="2">
          <v-card outlined class="pa-4 roomCard stat-card1 stat-amber">
            <div class="d-flex align-center">
              <div>
                <div class="text-caption text-uppercase font-weight-bold stat-title amber-text">
                  Disabled
                </div>
                <div class="d-flex align-baseline mt-2">
                  <div class="text-h4 font-weight-bold">{{ activeDisabledSos }}</div>
                  <div class="ml-2 stat-sub amber-text">Calls</div>
                </div>
              </div>
              <v-spacer />
              <v-icon class="stat-icon amber-text">mdi-wheelchair</v-icon>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="2">
          <v-card outlined class="pa-4 roomCard stat-card1 stat-info">
            <div class="d-flex align-center">
              <div>
                <div class="text-caption text-uppercase font-weight-bold stat-title info-text">
                  Repeated
                </div>
                <div class="d-flex align-baseline mt-2">
                  <div class="text-h4 font-weight-bold">{{ stats.repeated }}</div>
                  <div class="ml-2 stat-sub info-text">Calls</div>
                </div>
              </div>
              <v-spacer />
              <v-icon class="stat-icon info-text">mdi-alarm-light</v-icon>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="2">
          <v-card outlined class="pa-4 roomCard stat-card1 stat-success">
            <div class="d-flex align-center">
              <div>
                <div class="text-caption text-uppercase font-weight-bold stat-title success-text">
                  Acknowledged
                </div>
                <div class="d-flex align-baseline mt-2">
                  <div class="text-h4 font-weight-bold">{{ stats.ackCount }}</div>
                  <div class="ml-2 stat-sub success-text">Calls</div>
                </div>
              </div>
              <v-spacer />
              <v-icon class="stat-icon success-text">mdi-check-circle</v-icon>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="2">
          <v-card outlined class="pa-4 roomCard stat-card1 stat-purple">
            <div class="d-flex align-center">
              <div>
                <div class="text-caption text-uppercase font-weight-bold stat-title purple-text">
                  Total
                </div>
                <div class="d-flex align-baseline mt-2">
                  <div class="text-h4 font-weight-bold">{{ stats.totalSOSCount }}</div>
                  <div class="ml-2 stat-sub purple-text">Calls</div>
                </div>
              </div>
              <v-spacer />
              <v-icon class="stat-icon purple-text">mdi-counter</v-icon>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="2">
          <v-card outlined class="pa-4 roomCard stat-card1 stat-teal">
            <div class="d-flex align-center">
              <div style="width:100%">
                <div class="text-caption text-uppercase font-weight-bold stat-title teal-text">
                  Avg Response
                </div>
                <div class="d-flex align-baseline mt-0">
                  <div class="text-h4 font-weight-bold">{{ avgResponseText }}</div>
                  <div class="ml-2 stat-sub teal-text">min</div>
                </div>
                <v-progress-linear class="mt-1" height="4" :value="avgResponsePct" color="teal" rounded />
              </div>
              <v-spacer />
              <v-icon class="stat-icon teal-text">mdi-timer</v-icon>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- ================= FILTER ROW ================= -->
      <div class="d-flex flex-wrap align-center mt-2 mb-3">
        <v-btn-toggle v-model="filterMode" mandatory class="mr-4">
          <v-btn small value="all">All</v-btn>
          <v-btn small value="on">SOS ON</v-btn>
          <v-btn small value="off">SOS OFF</v-btn>
        </v-btn-toggle>

        <div class="d-flex align-center">
          <v-btn-toggle v-model="perRow" mandatory>
            <v-btn small readonly :value="4">Split <v-icon>mdi-view-grid</v-icon></v-btn>
            <!-- <v-btn small :value="2">2</v-btn> -->
            <v-btn small :value="4">4</v-btn>
            <v-btn small :value="6">6</v-btn>
          </v-btn-toggle>
        </div>

        <v-spacer />

        <div class="d-flex align-center mt-2 mt-md-0">
          <div class="d-flex align-center mr-4">
            <span class="dot dot-red mr-2"></span><span class="text-caption grey--text">SOS ON</span>
          </div>
          <div class="d-flex align-center">
            <span class="dot dot-grey mr-2"></span><span class="text-caption grey--text">SOS OFF</span>
          </div>
        </div>
      </div>
    </div>

    <!-- GRID AREA -->
    <div class="tv-grid" :style="{ height: gridHeight + 'px' }">
      <v-card outlined class="pa-3 roomCard gridCard">
        <div class="gridWrap" :style="gridStyle">
          <div v-for="d in filteredDevices" :key="d.id" class="gridItem">
            <v-card outlined class="pa-3 roomCard roomCardIndividual roomCardSized" :class="cardClass(d)"
              :style="{ height: roomCardHeight + 'px' }">
              <div class="d-flex align-start">
                <div class="min-w-0">
                  <div class="text-h6 font-weight-black text-truncate">{{ d.name }}</div>

                  <div class="mt-3">
                    <v-icon v-if="d.room_type === 'toilet' || d.room_type === 'toilet-ph'" size="40" color="yellow">
                      mdi-toilet
                    </v-icon>

                    <v-icon v-else-if="d.room_type === 'room' || d.room_type === 'room-ph'" size="40" color="blue">
                      mdi-bed
                    </v-icon>

                    <v-icon v-else-if="d.room_type === 'room-ph' || d.room_type === 'toilet-ph'" size="40" color="red">
                      mdi-wheelchair
                    </v-icon>

                    <v-icon v-else size="40" color="blue">mdi-bed</v-icon>
                  </div>
                </div>

                <v-spacer />

                <div>
                  <v-icon v-if="d.alarm_status === true" color="red">mdi-bell</v-icon>
                  <v-icon v-if="d.device?.status_id == 1" color="green">mdi-wifi</v-icon>
                  <v-icon v-else-if="d.device?.status_id == 2" color="red">mdi-wifi-off</v-icon>
                </div>
              </div>

              <div class="text-right tv-chip-wrap">
                <v-chip small class="mt-0"
                  :color="d.alarm_status === true ? (d.alarm?.responded_datetime ? '#f97316' : 'red') : 'grey'">
                  {{ d.alarm_status === true ? (d.alarm?.responded_datetime ? 'ACKNOWLEDGED' : 'PENDING') : 'RESOLVED'
                  }}
                </v-chip>

                <div v-if="!d.alarm?.responded_datetime">
                  <v-btn class="mt-0 blink-btn" small v-if="d.alarm_status === true"
                    @click="udpateResponse(d.alarm.id)">
                    <v-icon>mdi-cursor-default-click</v-icon> Acknowledge
                  </v-btn>
                </div>
              </div>

              <div class="mt-0 text-center" v-if="d.alarm_status === true">
                <div class="text-h4 font-weight-bold mono">
                  {{ d.duration || "00:00:00" }}
                </div>
                <div class="text-h4 red--text font-weight-bold text-uppercase timeText">
                  {{ d.alarm?.alarm_start_datetime }}
                </div>
              </div>

              <div class="mt-0 text-center grey--text" v-else>
                <v-icon large color="green">mdi-check-circle</v-icon>
                <div class="text-body-2 font-weight-medium mt-1">No Active Call</div>
              </div>
            </v-card>
          </div>
        </div>
      </v-card>

      <v-progress-linear v-if="loading" indeterminate height="3" class="mb-2" />
    </div>
  </div>
</template>

<script>
import SosAlarmPopupMqtt from "@/components/SOS/SosAlarmPopupMqtt.vue";

export default {
  layout: "tvmonitorlayout",
  auth: false,
  middleware: [],

  name: "TvSosFloor",

  components: { SosAlarmPopupMqtt },

  data() {
    return {
      filterMode: "all",
      perRow: 4,

      devices: [],
      loading: false,
      apiError: "",

      timer: null,
      TIMER_MS: 1000,

      refreshTimer: null,
      REFRESH_API_MS: 5000,

      apiFailCount: 0,
      MAX_FAILS_BEFORE_RELOAD: 12,

      avgResponseText: "00:00",
      avgResponsePct: 100,
      repeated: 0,
      ackCount: 0,
      totalSOSCount: 0,
      activeDisabledSos: 0,

      room: null,
      status: null,
      date_from: "",
      date_to: "",

      snackbar: false,
      snackbarResponse: "",

      // dynamic sizing
      gridHeight: 400,
      roomCardHeight: 200,
      gapPx: 12,
      innerCardPadding: 12
    };
  },

  computed: {
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
    gridStyle() {
      return {
        gridTemplateColumns: `repeat(${this.perRow}, minmax(0, 1fr))`,
        gap: this.gapPx + "px"
      };
    }
  },

  watch: {
    perRow() {
      this.$nextTick(() => this.recalcLayout());
    },
    filterMode() {
      this.$nextTick(() => this.recalcLayout());
    }
  },

  async created() {
    await this.RefreshDashboard();
  },

  mounted() {
    this.timer = setInterval(() => this.updateDurationAll(), this.TIMER_MS);
    this.refreshTimer = setInterval(() => this.RefreshDashboard(), this.REFRESH_API_MS);

    document.body.style.overflow = "hidden";

    this.$nextTick(() => this.recalcLayout());
    if (window)
      window.addEventListener("resize", this.recalcLayout, { passive: true });
  },

  beforeDestroy() {
    if (this.timer) clearInterval(this.timer);
    if (this.refreshTimer) clearInterval(this.refreshTimer);
    window.removeEventListener("resize", this.recalcLayout);
    document.body.style.overflow = "";
  },

  methods: {
    // ======= TV NO-OVERFLOW LAYOUT CALC =======
    recalcLayout() {
      // viewport height
      const vh = window.innerHeight || 1080;

      // actual measured header height
      const headerEl = this.$refs.header;
      const headerH = headerEl ? Math.ceil(headerEl.getBoundingClientRect().height) : 260;

      // outer padding (tv-page) top+bottom roughly 2 * 1.2vh
      const outerPad = Math.ceil(vh * 0.024);

      // grid height must fit into remaining space
      const gridH = Math.max(120, vh - headerH - outerPad);
      this.gridHeight = gridH;

      // set gap relative to screen (stable on 1080p & 4K)
      this.gapPx = Math.max(10, Math.round(vh * 0.012)); // ~1.2% of screen height

      // compute rows
      const cols = Number(this.perRow || 4);
      const count = Math.max(1, (this.filteredDevices || []).length);
      const rows = Math.max(1, Math.ceil(count / cols));

      // Inside v-card: subtract its padding & border (pa-3 = 12px padding)
      const cardInner = Math.max(1, gridH - (this.innerCardPadding * 2) - 2);

      // total gaps between rows
      const totalRowGaps = this.gapPx * (rows - 1);

      // card height per row
      const h = Math.floor((cardInner - totalRowGaps) / rows);

      // clamp to keep it readable but never overflow
      const minH = Math.max(150, Math.round(vh * 0.14));
      const maxH = Math.max(220, Math.round(vh * 0.30));
      this.roomCardHeight = Math.max(minH, Math.min(maxH, h));
    },

    // ======= existing logic =======
    cardClass(d) {
      return d?.alarm_status === true
        ? (d.alarm?.responded_datetime != null ? "cardAck" : "cardOn")
        : "cardOff";
    },

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
          typeof r.alarm_status === "boolean"
            ? r.alarm_status
            : this.toBool(r.alarm_status ?? alarm?.alarm_status ?? r.status);

        const startMs = alarm?.alarm_start_datetime
          ? this.parseStartMs(alarm.alarm_start_datetime)
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

    async RefreshDashboard() {
      try {
        await Promise.all([this.getDataFromApi(), this.getStatsApi()]);
        this.apiFailCount = 0;
        this.$nextTick(() => this.recalcLayout());
      } catch (e) {
        this.apiFailCount++;
        if (this.apiFailCount >= this.MAX_FAILS_BEFORE_RELOAD) window.location.reload();
      }
    },

    async udpateResponse(alarmId) {
      this.loading = true;
      this.apiError = "";

      try {
        const { data } = await this.$axios.post("dashboard_alarm_response", {
          company_id: process.env.TV_COMPANY_ID,
          alarmId
        });

        this.snackbar = true;
        this.snackbarResponse = data.message || "Updated";
        await this.RefreshDashboard();
      } catch (err) {
        this.apiError = err?.response?.data?.message || err?.message || "Failed to update response";
      } finally {
        this.loading = false;
      }
    },

    async getStatsApi() {
      const { data } = await this.$axios.get("dashboard_stats", {
        params: {
          company_id: process.env.TV_COMPANY_ID,
          date_from: this.date_from || null,
          date_to: this.date_to || null,
          roomType: this.room?.value || null,
          sosStatus: this.status || null
        }
      });

      this.repeated = data.repeated || 0;
      this.ackCount = data.ackCount || 0;
      this.totalSOSCount = data.totalSOSCount || 0;
      this.activeDisabledSos = data.activeDisabledSos || 0;

      if (this.$dateFormat?.minutesToHHMM) {
        this.avgResponseText = this.$dateFormat.minutesToHHMM(data.averageMinutes || 0);
      } else {
        const m = Number(data.averageMinutes || 0);
        const hh = String(Math.floor(m / 60)).padStart(2, "0");
        const mm = String(m % 60).padStart(2, "0");
        this.avgResponseText = `${hh}:${mm}`;
      }

      this.avgResponsePct = data.sla_percentage ?? 100;
    },

    async getDataFromApi() {
      this.loading = true;

      try {
        const { data } = await this.$axios.get("dashboard_rooms", {
          params: {
            company_id: process.env.TV_COMPANY_ID,
            date_from: this.date_from || null,
            date_to: this.date_to || null,
            roomType: this.room?.value || null,
            sosStatus: this.status || null
          }
        });

        const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
        this.devices = this.normalizeRooms(list);
        this.updateDurationAll();
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
/* No overflow, no scroll */
.tv-page {
  height: 100vh;
  overflow: hidden;
  padding: 1.2vh;
  box-sizing: border-box;
}

/* Grid takes exact computed height */
.tv-grid {
  overflow: hidden;
}

.gridCard {
  height: 100%;
}

/* Grid */
.gridWrap {
  display: grid;
  width: 100%;
  height: 100%;
  align-items: stretch;
}

.gridItem {
  min-width: 0;
}

/* Room card fits allocated height */
.roomCardSized {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
}

/* Remove fixed mins that cause overflow */
.roomCard {
  border-radius: 12px;
  min-height: unset;
}

/* Typography scaling for TV */
.text-h4 {
  font-size: clamp(20px, 2.4vh, 44px) !important;
}

.text-h6 {
  font-size: clamp(14px, 1.7vh, 28px) !important;
}

.text-caption {
  font-size: clamp(11px, 1.2vh, 18px) !important;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

/* Stat cards are fixed relative to TV, not content pushing grid down */
.stat-card1 {
  min-height: unset !important;
  height: clamp(80px, 12vh, 140px);
}

/* Status borders */
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

/* Dots */
.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  display: inline-block;
}

.dot-red {
  background: #ef4444;
  box-shadow: 0 0 10px rgba(239, 68, 68, .25);
}

.dot-grey {
  background: #64748b;
}

/* Blink */
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

/* Stat border colors */
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

/* Text colors */
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

.stat-icon {
  font-size: 34px;
  opacity: 0.95;
}
</style>
