<template>
  <div class="tv-page" :class="deviceClass">
    <div class="text-center ma-2">
      <v-snackbar v-model="snackbar" top="top" elevation="24">
        {{ snackbarResponse }}
      </v-snackbar>
    </div>


    <SosAlarmPopupMqtt @triggerUpdateDashboard="requestDashboardSnapshot()" />

    <!-- ONE SCROLL CONTAINER FOR EVERYTHING -->
    <div class="pageScroll">
      <!-- ================= TOP STATISTICS ================= -->
      <v-row dense class="mb-2">
        <v-col v-for="s in statCards" :key="s.key" cols="12" sm="6" :md="statsColsMd" :lg="statsColsLg">
          <v-card outlined class="pa-4 roomCard stat-card1" :class="s.cardClass">
            <div class="d-flex align-center">
              <div>
                <div class="text-caption text-uppercase font-weight-bold stat-title" :class="s.textClass">
                  {{ s.title }}
                </div>
                <div class="d-flex align-baseline mt-2">
                  <div class="text-h4 font-weight-bold">{{ s.value }}</div>
                  <div class="ml-2 stat-sub" :class="s.textClass">{{ s.sub }}</div>
                </div>
              </div>
              <v-spacer />
              <v-icon class="stat-icon" :class="s.textClass">{{ s.icon }}</v-icon>
            </div>

            <v-progress-linear style="margin-top:-10px!important" v-if="s.key === 'avg'" class="mt-1" height="4"
              :value="avgResponsePct" rounded />
          </v-card>
        </v-col>
      </v-row>

      <!-- ================= FILTER ROW ================= -->
      <div class="d-flex flex-wrap align-center mt-1 mb-3">
        <v-btn-toggle v-model="filterMode" mandatory class="mr-4">
          <v-btn small value="all">All</v-btn>
          <v-btn small value="on">SOS ON</v-btn>
          <v-btn small value="off">SOS OFF</v-btn>
        </v-btn-toggle>

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

      <!-- ================= ROOMS GRID (RESPONSIVE + SCROLL) ================= -->
      <div class="roomsGrid">
        <div v-for="d in filteredDevices" :key="d.id || d.room_id" class="roomCell">
          <v-card outlined class="pa-3 roomCard roomCardIndividual" :class="cardClass(d)">
            <div class="d-flex align-start">
              <div class="min-w-0">
                <div class="text-h6 font-weight-black text-truncate">{{ d.name }}</div>

                <div class="mt-3">
                  <v-icon v-if="d.room_type === 'room-ph' || d.room_type === 'toilet-ph'" size="40"
                    class="roomIcon roomIconPh">
                    mdi-wheelchair
                  </v-icon>

                  <v-icon v-else-if="d.room_type === 'toilet'" size="40" class="roomIcon roomIconToilet">
                    mdi-toilet
                  </v-icon>

                  <v-icon v-else-if="d.room_type === 'room'" size="40" class="roomIcon roomIconRoom">
                    mdi-bed
                  </v-icon>

                  <v-icon v-else size="40" class="roomIcon roomIconDefault">mdi-bed</v-icon>
                </div>
              </div>

              <v-spacer />

              <div>
                <v-icon v-if="d.alarm_status === true" color="red">mdi-bell</v-icon>
                <v-icon v-if="d.device?.status_id == 1" color="green">mdi-wifi</v-icon>
                <v-icon v-else-if="d.device?.status_id == 2" color="red">mdi-wifi-off</v-icon>
              </div>
            </div>

            <div class="text-right tv-chip-wrap mt-2">
              <v-chip small v-if="d.alarm?.responded_datetime == null"
                :style="d.alarm?.alarm_end_datetime == null && d.alarm?.responded_datetime == null ? 'margin-top:-30px!important;' : ''"
                :color="d.alarm_status === true ? (d.alarm?.responded_datetime ? '#f97316' : 'red') : 'grey'">
                {{
                  d.alarm_status === true
                    ? (d.alarm?.responded_datetime ? "ACKNOWLEDGED" : "PENDING")
                    : "RESOLVED"
                }}
              </v-chip>

              <v-chip small v-else-if="d.alarm?.responded_datetime != null"
                :color="d.alarm_status === true ? (d.alarm?.responded_datetime ? '#f97316' : 'red') : 'grey'">
                {{
                  d.alarm_status === true
                    ? (d.alarm?.responded_datetime ? "ACKNOWLEDGED1" : "PENDING")
                    : "RESOLVED"
                }}
              </v-chip>

              <div v-if="!d.alarm?.responded_datetime">

                <v-btn class="mt-2 blink-btn" small v-if="d.alarm_status === true" @click="udpateResponse(d.alarm?.id)">
                  <v-icon>mdi-cursor-default-click</v-icon> Acknowledge
                </v-btn>
              </div>
            </div>

            <div class="mt-2 text-center" v-if="d.alarm_status === true"
              :style="d.alarm?.alarm_end_datetime == null && d.alarm?.responded_datetime == null ? 'margin-top:-30px!important;' : ''">
              <div class="text-h4 font-weight-bold mono">
                {{ d.duration || "00:00:00" }}
              </div>
              <div class="text-body-1 red--text font-weight-bold text-uppercase timeText">
                {{ d.alarm?.alarm_start_datetime || d.alarm_start_datetime }}
              </div>
            </div>

            <div class="mt-2 text-center grey--text" v-else>
              <v-icon large color="green">mdi-check-circle</v-icon>
              <div class="text-body-2 font-weight-medium mt-1">No Active Call</div>
            </div>
          </v-card>
        </div>
      </div>

      <v-progress-linear v-if="loading" indeterminate height="3" class="my-2" />
    </div>
  </div>
</template>

<script>
import mqtt from "mqtt";
import SosAlarmPopupMqtt from "@/components/SOS/SosAlarmPopupMqtt.vue";

export default {
  layout: "tvmonitorlayout",
  auth: false,
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

      // Stats
      avgResponseText: "00:00",
      avgResponsePct: 100,
      repeated: 0,
      ackCount: 0,
      totalSOSCount: 0,
      activeDisabledSos: 0,

      // params (same as your API)
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
      topics: {
        req: "",
        rooms: "",
        stats: "",
        reload: ""

      },

      // optional debug
      message: ""
    };
  },

  computed: {
    // --- Breakpoint driven device mode ---
    deviceType() {
      if (this.$vuetify.breakpoint.smAndDown) return "mobile"; // xs/sm
      if (this.$vuetify.breakpoint.mdOnly) return "tablet"; // md
      return "tv"; // lg/xl
    },
    deviceClass() {
      return `is-${this.deviceType}`;
    },

    // Stats: 1/row on mobile, 2/row on tablet, 6/row on TV
    statsColsMd() {
      // md column span when viewport >= md
      // tablet is md only: 6 => 2 cards per row
      // tv often lg+: but keep fallback
      return this.deviceType === "tv" ? 4 : 6;
    },
    statsColsLg() {
      // lg+: on TV show 6 cards per row => 2 columns each
      return this.deviceType === "tv" ? 2 : 3;
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

    statCards() {
      return [
        {
          key: "active",
          title: "Active SOS",
          value: this.stats.activeSos,
          sub: "Calls",
          icon: "mdi-bell-alert",
          cardClass: "stat-critical",
          textClass: "critical-text"
        },
        {
          key: "disabled",
          title: "Disabled",
          value: this.activeDisabledSos,
          sub: "Calls",
          icon: "mdi-wheelchair",
          cardClass: "stat-amber",
          textClass: "amber-text"
        },
        {
          key: "repeated",
          title: "Repeated",
          value: this.stats.repeated,
          sub: "Calls",
          icon: "mdi-alarm-light",
          cardClass: "stat-info",
          textClass: "info-text"
        },
        {
          key: "ack",
          title: "Acknowledged",
          value: this.stats.ackCount,
          sub: "Calls",
          icon: "mdi-check-circle",
          cardClass: "stat-success",
          textClass: "success-text"
        },
        {
          key: "total",
          title: "Total",
          value: this.stats.totalSOSCount,
          sub: "Calls",
          icon: "mdi-counter",
          cardClass: "stat-purple",
          textClass: "purple-text"
        },
        {
          key: "avg",
          title: "Avg Response",
          value: this.avgResponseText,
          sub: "min",
          icon: "mdi-timer",
          cardClass: "stat-teal",
          textClass: "teal-text"
        }
      ];
    }
  },

  created() {
    this.message = "created";
  },

  mounted() {
    this.snackbarResponse = "Ok";

    this.snackbarResponse = "Mounted";

    // Duration ticking
    this.timer = setInterval(() => this.updateDurationAll(), this.TIMER_MS);

    // MQTT
    this.mqttUrl = process.env.MQTT_SOCKET_HOST; // ws://IP:9001
    this.connectMqtt();
  },

  beforeDestroy() {
    try {
      if (this.timer) clearInterval(this.timer);
      this.disconnectMqtt();
    } catch (e) { }
  },

  destroyed() {
    // extra safety (Nuxt keep-alive edge cases)
    try {
      this.disconnectMqtt();
      if (this.timer) clearInterval(this.timer);
    } catch (e) { }
  },

  methods: {
    // ---------- MQTT ----------
    connectMqtt() {


      if (this.client) return;



      if (!this.mqttUrl) {
        this.snackbar = true;
        this.snackbarResponse = "MQTT_SOCKET_HOST missing in env";
        return;
      }

      const companyId = Number(process.env.TV_COMPANY_ID || 0);
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
            this.snackbarResponse = "MQTT subscribe failed";

            console.log("Step1 MQTT subscribe failed");
            return;
          }
          this.requestDashboardSnapshot();

          this.snackbar = true;
          this.snackbarResponse = "Server Connected";
          console.log("Step1 MQTT subscribed");

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


      console.log("Step5- MQTT Request sending");

      if (!this.client || !this.isConnected) return;

      this.snackbar = true;
      this.snackbarResponse = "MQTT requestDashboardSnapshot";

      this.reqId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
      const companyId = Number(process.env.TV_COMPANY_ID || 0);

      const payload = {
        reqId: this.reqId,
        params: {
          company_id: companyId,
          date_from: this.date_from || null,
          date_to: this.date_to || null,
          roomType: this.room?.value || null,
          sosStatus: this.status || null
        }
      };

      console.log("Step6- MQTT Request sent", payload);


      this.client.publish(this.topics.req, JSON.stringify(payload), { qos: 0, retain: false });

      this.snackbar = true;
      this.snackbarResponse = "MQTT Request sent";

      console.log("Step7- MQTT Request sent success");


      this.message = "snapshot requested";
    },

    onMqttMessage(topic, payload) {

      console.log("Step8- Message received");


      // console.log("topic", topic);

      if (topic !== this.topics.rooms && topic !== this.topics.stats && topic !== this.topics.reload) return;

      let msg;
      try {
        msg = JSON.parse(payload.toString());
        console.log("Step9- Message received", topic, msg);


      } catch (e) {
        console.log("Step10- Error", e);

        return;
      }

      // Strict reqId matching if needed:
      // if (msg.reqId && this.reqId && msg.reqId !== this.reqId) return;

      if (topic === this.topics.rooms) {
        const data = msg?.data;
        const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
        this.devices = this.normalizeRooms(list);

        console.log(this.devices);

        this.updateDurationAll();
        return;
      }
      if (topic === this.topics.reload) {
        try {
          window.location.reload();
        } catch (e) { }
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

    // ---------- ACK (still HTTP) ----------
    async udpateResponse(alarmId) {

      if (!alarmId) return;
      this.reqId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;

      this.loading = true;
      const companyId = Number(process.env.TV_COMPANY_ID || 0);
      const payload = {
        reqId: this.reqId,
        params: {
          company_id: companyId,
          alarmId: alarmId,

        }
      };

      this.client.publish(`tv/${companyId}/dashboard_alarm_response`, JSON.stringify(payload), { qos: 0, retain: false });
      this.loading = false;
      this.requestDashboardSnapshot();
      alert("Acknowledgement is Received..")


      // try {
      //   const { data } = await this.$axios.post("dashboard_alarm_response", {
      //     company_id: Number(process.env.TV_COMPANY_ID || 0),
      //     alarmId
      //   });

      //   this.snackbar = true;
      //   this.snackbarResponse = data.message || "Updated";
      // } catch (e) {
      //   this.snackbar = true;
      //   this.snackbarResponse = e?.response?.data?.message || e?.message || "ACK failed";
      // } finally {
      //   this.loading = false;
      // }
    }
  }
};
</script>

<style scoped>
.tv-page {
  height: 100vh;
  padding: 12px;
  box-sizing: border-box;
}

/* Scroll entire content */
.pageScroll {
  height: calc(100vh - 24px);
  overflow-y: auto;
  overflow-x: hidden;
}

/* Rooms responsive grid */
.roomsGrid {
  display: grid;
  gap: 12px;
}

/* MOBILE */
.is-mobile .roomsGrid {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

/* TABLET */
.is-tablet .roomsGrid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

/* TV */
.is-tv .roomsGrid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

/* Cards */
.roomCard {
  border-radius: 12px;
}

.roomCardIndividual {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

/* Typography scaling */
.text-h4 {
  font-size: clamp(20px, 2.2vh, 44px) !important;
}

.text-h6 {
  font-size: clamp(14px, 1.6vh, 28px) !important;
}

.text-caption {
  font-size: clamp(11px, 1.1vh, 18px) !important;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
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
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.25);
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

/* Stat colors */
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

/* Room icons */
.roomIcon {
  opacity: 0.95;
}

.roomIconPh {
  color: #ef4444;
}

.roomIconToilet {
  color: #f59e0b;
}

.roomIconRoom {
  color: #3b82f6;
}

.roomIconDefault {
  color: #3b82f6;
}
</style>
