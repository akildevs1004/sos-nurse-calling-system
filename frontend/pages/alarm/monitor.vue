<template>
  <div fluid class=" ">
    <div class="text-center ma-2">
      <v-snackbar v-model="snackbar" top="top" elevation="24">
        {{ snackbarResponse }}
      </v-snackbar>
    </div>
    <SosAlarmPopupMqtt @triggerUpdateDashboard="RefreshDashboard()" />



    <!-- ================= TOP STATISTICS ================= -->
    <v-row dense>
      <!-- Active SOS (Red) -->
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

      <!-- Disabled (Amber) -->
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

      <!-- Repeated (Blue) -->
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

      <!-- Acknowledged (Green) -->
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

      <!-- Total (Purple) -->
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

      <!-- Avg Response (Teal) -->
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
          <v-btn small readonly :value="4">Split <v-icon>mdi-view-grid</v-icon> </v-btn>
          <v-btn small :value="2">2</v-btn>
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

    <!-- <v-alert v-if="apiError" type="error" dense text class="mb-2">
      {{ apiError }}
    </v-alert> -->

    <!-- ================= CARDS GRID ================= -->
    <v-card outlined class="pa-4 roomCard">
      <div class="gridWrap" :style="gridStyle">
        <div v-for="d in filteredDevices" :key="d.id" class="gridItem">
          <v-card outlined class="pa-4 roomCard roomCardIndividual" :class="cardClass(d)">
            <div class="d-flex align-start">
              <div class="min-w-0">
                <div class="text-h6 font-weight-black text-truncate">{{ d.name }} </div>

                <div class="mt-3" style="">


                  <v-icon size="40" v-if="d.room_type == 'toilet' || 'toilet-ph'" color="yellow"> mdi-toilet </v-icon>
                  <v-icon size="40" v-if="d.room_type == 'room' || 'room-ph'" color="blue"> mdi-bed </v-icon>
                  <v-icon size="40" v-if="d.room_type == 'room-ph' || 'toilet-ph'" color="red"> mdi-wheelchair </v-icon>


                  <v-icon v-if="!d.room_type || d.room_type == ''" size="40" color="blue"> mdi-bed </v-icon>
                </div>
              </div>

              <v-spacer />

              <div>
                <v-icon v-if="d.alarm_status === true" color="red">mdi-bell</v-icon>
                <v-icon v-if="d.device?.status_id == 1" color="green">mdi-wifi</v-icon>
                <v-icon v-else-if="d.device?.status_id == 2" color="red">mdi-wifi-off</v-icon>
              </div>
            </div>

            <div style="width:100%; text-align:right;margin-top:-50px;height:50px">
              <v-chip small class="mt-2"
                :color="d.alarm_status === true ? d.alarm?.responded_datetime ? '#f97316' : 'red' : 'grey'">
                {{ d.alarm_status === true ? d.alarm?.responded_datetime ? 'ACKNOWLEDGED' : 'PENDING' : 'RESOLVED' }}
              </v-chip>

              <div v-if="!d.alarm?.responded_datetime">
                <v-btn class="mt-2 blink-btn" small v-if="d.alarm_status === true" @click="udpateResponse(d.alarm.id)">
                  <v-icon>mdi-cursor-default-click</v-icon> Acknowledge
                </v-btn>
              </div>
            </div>

            <div class="mt-5 text-center" v-if="d.alarm_status === true">
              <div class="text-h4 font-weight-bold"
                style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
                {{ d.duration || "00:00:00" }}
              </div>
              <div class="text-caption red--text font-weight-bold text-uppercase" style="letter-spacing:.12em">
                {{ d.alarm?.alarm_start_datetime }}
              </div>
            </div>

            <div class="mt-5 text-center grey--text" v-else>
              <v-icon large color="green">mdi-check-circle</v-icon>
              <div class="text-body-2 font-weight-medium mt-1">No Active Call</div>
            </div>

          </v-card>
        </div>
      </div>
    </v-card>
    <v-progress-linear v-if="loading" indeterminate height="3" class="mb-2" />

  </div>
</template>

<script>
import CustomFilter from '../../components/CustomFilter.vue';

// import { VTabItem } from 'vuetify/lib';


// import SosAlarmPopupMqtt from '../../components/SOS/SosAlarmPopupMqtt.vue';



export default {
  name: "SosFloorView",
  components: { CustomFilter },

  data() {
    return {
      // filters
      range: "7",            // "7" | "30" | "all"
      room: null,            // {id,name} | null
      source: null,          // {value,text} | null
      status: null,          // "ON" | "OFF" | null

      // demo lists (replace with your API data)
      rooms: [{ value: "room", name: "Rooms" }, { value: "toilet", name: "Toilets" }, { value: "toilet-ph", name: "Toilets - Handicapped" }],
      sourcesList: [
        { value: "bedside", text: "Bedside" },
        { value: "toilet", text: "Toilet / Bath" },
        { value: "codeblue", text: "Code Blue" },
      ],
      // companyDeviceSerials: null,

      filterMode: "all",
      perRow: 4,

      devices: [],
      loading: false,
      apiError: "",

      timer: null,
      TIMER_MS: 1000, // 1 second refresh

      // demo values
      avgResponseText: "00:00",
      avgResponsePct: 100,
      repeated: 0,
      ackCount: 0,
      totalSOSCount: 0,
      activeDisabledSos: 0,

      snackbar: false,
      snackbarResponse: '',
      averageMinutes: 0,
      date_from: "",
      date_to: "",

    };
  },

  computed: {
    filterRangeLabel() {
      if (this.range === "7") return "Last 7 Days";
      if (this.range === "30") return "Last 30 Days";
      return "All Time";
    },
    roomLabel() {
      console.log("Room- ", this.room);

      return this.room?.name || "All Rooms";
    },
    sourceLabel() {
      return this.source?.text || "All Sources";
    },
    statusLabel() {
      if (!this.status) return "All Statuses";
      return this.status === "ON" ? "Active" : "Resolved";
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
        activeDisabledSos: this.activeDisabledSos,

        staffOnFloor: 6,
      };
    },
    filteredDevices() {
      if (this.filterMode === "on") return this.devices.filter((d) => d.alarm_status === true);
      if (this.filterMode === "off") return this.devices.filter((d) => d.alarm_status === false);
      return this.devices;
    },
    gridStyle() {
      return { gridTemplateColumns: `repeat(${this.perRow}, minmax(0, 1fr))` };
    },
  },

  async created() {
    await this.loadData();
    // await this.getDevicesFromApi();
  },



  mounted() {
    // Timer (no timezone logic)
    this.timer = setInterval(() => {
      this.updateDurationAll();
    }, this.TIMER_MS);
  },

  beforeDestroy() {
    if (this.timer) clearInterval(this.timer);
  },

  methods: {

    async loadData() {

      console.log("this.room", this.room);

      await this.getDataFromApi();
      await this.getStatsApi();
    },
    filterAttr(data) {
      this.date_from = data?.from || null;
      this.date_to = data?.to || null;
      this.loadData();
    },
    setRange(v) {
      this.range = v;


      if (this.range == '') {
        const today = new Date();


        this.date_from = today.toISOString().slice(0, 10); // YYYY-MM-DD
        this.date_to = today.toISOString().slice(0, 10);

      } else if (this.range == 7) {
        const today = new Date();

        const from = new Date();
        from.setDate(today.getDate() - 6); // last 7 days incl. today

        this.date_from = from.toISOString().slice(0, 10); // YYYY-MM-DD
        this.date_to = today.toISOString().slice(0, 10);

      } else if (this.range == 30) {
        const today = new Date();

        const from = new Date();
        from.setDate(today.getDate() - 30); // last 7 days incl. today

        this.date_from = from.toISOString().slice(0, 10); // YYYY-MM-DD
        this.date_to = today.toISOString().slice(0, 10);

      }






      this.loadData();
    },
    setRoom(r) { this.room = r; this.loadData(); },
    setSource(s) { this.source = s; this.loadData(); },
    setStatus(v) { this.status = v; this.loadData(); },

    resetFilters() {
      this.range = "";
      this.date_from = null;
      this.date_to = null;
      this.roomType = null;
      this.sosStatus = null;
      this.loadData();
    },

    applyFilters() {
      // Call API / refresh dashboard
      // this.getDataFromApi();
    },
    async RefreshDashboard() {
      await this.getDataFromApi();
      await this.getStatsApi();

    },
    cardClass(d) {
      return d?.alarm_status === true ? d.alarm?.responded_datetime != null ? "cardAck" : "cardOn" : "cardOff";

    },

    // normalize alarm_status to boolean (handles true/false, 1/0, "ON"/"OFF")
    toBool(v) {
      if (v === true) return true;
      if (v === false) return false;
      const s = String(v ?? "").trim().toUpperCase();
      return s === "1" || s === "TRUE" || s === "ON" || s === "YES";
    },

    // Parse datetime as-is (NO timezone handling)
    // Supports:
    //  - "YYYY-MM-DD HH:mm:ss"
    //  - "YYYY-MM-DD HH:mm:ss.000000"
    //  - ISO strings
    parseStartMs(dateStr) {
      if (!dateStr) return null;

      const raw = String(dateStr).trim();

      // ISO strings
      if (raw.includes("T")) {
        const ms = Date.parse(raw);
        return Number.isFinite(ms) ? ms : null;
      }

      // Laravel "YYYY-MM-DD HH:mm:ss[.micro]"
      const clean = raw.replace(/\.\d+$/, "");
      // Convert to ISO-like local time; JS parses as local time
      const isoLocal = clean.replace(" ", "T");
      const ms = Date.parse(isoLocal);

      return Number.isFinite(ms) ? ms : null;
    },

    // HH:MM:SS formatter (hours can exceed 24)
    formatHHMMSS(totalSeconds) {
      const hh = Math.floor(totalSeconds / 3600);
      const mm = Math.floor((totalSeconds % 3600) / 60);
      const ss = totalSeconds % 60;
      return `${String(hh).padStart(2, "0")}:${String(mm).padStart(2, "0")}:${String(ss).padStart(2, "0")}`;
    },

    normalizeRooms(list = []) {
      return list.map((r) => {
        const alarm = r.alarm || null;

        // prefer backend boolean if it is boolean, otherwise normalize
        const alarm_status =
          typeof r.alarm_status === "boolean"
            ? r.alarm_status
            : this.toBool(r.alarm_status ?? alarm?.alarm_status ?? r.status);

        const startMs = alarm?.alarm_start_datetime
          ? this.parseStartMs(alarm.alarm_start_datetime)
          : null;

        return {
          ...r,
          alarm,
          alarm_status,
          startMs,
          duration: "", // HH:MM:SS
        };
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

    async udpateResponse(alarmId) {
      this.loading = true;
      this.apiError = "";

      try {
        const { data } = await this.$axios.post("dashboard_alarm_response", {

          company_id: this.$auth.user.company_id,
          alarmId: alarmId,
        });

        //if (data.status) {
        this.snackbar = true;
        this.snackbarResponse = data.message;
        //  }

        await this.getDataFromApi();
        await this.getStatsApi();
      } catch (err) {
        this.apiError =
          err?.response?.data?.message ||
          err?.message ||
          "Failed to load dashboard rooms";
      } finally {
        this.loading = false;
      }

    },
    // async getSosRoomTypes() {
    //   // this.loading = true;
    //   this.apiError = "";

    //   try {
    //     const { data } = await this.$axios.get("sos_room_types", {
    //       params: {
    //         company_id: this.$auth.user.company_id,

    //       },
    //     });

    //     // supports array or paginator {data:[...]}
    //     this.roomTypes = data ? data : [];



    //   } catch (err) {


    //   } finally {

    //   }

    // },
    async getStatsApi() {
      // this.loading = true;
      this.apiError = "";

      try {
        const { data } = await this.$axios.get("dashboard_stats", {
          params: {
            company_id: this.$auth.user.company_id,

            date_from: this.date_from || null,
            date_to: this.date_to || null,

            roomType: this.room?.value || null,
            sosStatus: this.status || null,


          },
        });

        // supports array or paginator {data:[...]}
        const list = data;
        this.repeated = data.repeated;
        this.ackCount = data.ackCount;
        this.totalSOSCount = data.totalSOSCount;
        this.avgResponseText = this.$dateFormat.minutesToHHMM(list.averageMinutes);
        this.avgResponsePct = list.sla_percentage;
        this.activeDisabledSos = data.activeDisabledSos;


      } catch (err) {
        this.apiError =
          err?.response?.data?.message ||
          err?.message ||
          "Failed to load dashboard rooms";
      } finally {
        this.loading = false;
      }

    },
    // async getDevicesFromApi() {
    //   // this.loading = true;
    //   this.apiError = "";

    //   try {
    //     const { data } = await this.$axios.get("device-list", {
    //       params: {
    //         company_id: this.$auth.user.company_id,

    //       },
    //     });

    //     // supports array or paginator {data:[...]}
    //     this.companyDeviceSerials = data.map(d => d.serial_number);
    //     this.devices
    //   } catch (err) {
    //     this.apiError =
    //       err?.response?.data?.message ||
    //       err?.message ||
    //       "Failed to load dashboard rooms";
    //   } finally {
    //     this.loading = false;
    //   }
    // },
    async getDataFromApi() {
      this.loading = true;
      this.apiError = "";

      // try {
      const { data } = await this.$axios.get("dashboard_rooms", {
        params: {
          company_id: this.$auth.user.company_id,

          date_from: this.date_from || null,
          date_to: this.date_to || null,

          roomType: this.room?.value || null,
          sosStatus: this.status || null,



        },
      });

      // supports array or paginator {data:[...]}
      const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
      this.devices = this.normalizeRooms(list);

      // compute immediately (no waiting for 1s)
      this.updateDurationAll();
      // } catch (err) {
      //   this.apiError =
      //     err?.response?.data?.message ||
      //     err?.message ||
      //     "Failed to load dashboard rooms";
      // } finally {
      //   this.loading = false;
      // }
    },
  },
};
</script>

<style scoped>
.gridWrap {
  display: grid;
  gap: 14px;
}

@media (max-width: 1400px) {
  .gridWrap {
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
  }
}

@media (max-width: 960px) {
  .gridWrap {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }
}

@media (max-width: 600px) {
  .gridWrap {
    grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
  }
}

.roomCard {
  border-radius: 12px;
  min-height: 170px;
}

.stat-card1 {

  min-height: 80px !important;
}

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

.sos-border-red {
  border-color: rgba(239, 68, 68, 0.35) !important;
}

.sos-border-orange {
  border-color: #f97316 !important;
}

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

.stat-card {
  border-radius: 14px;
  border: 1px solid transparent !important;
}

/* ===== Full Border Colors ===== */
.stat-critical {
  border-color: #ef4444 !important;
  background: rgba(239, 68, 68, 0.05);
}

.stat-warning {
  border-color: #f97316 !important;
  background: rgba(249, 115, 22, 0.05);
}

.stat-success {
  border-color: #22c55e !important;
  background: rgba(34, 197, 94, 0.05);
}

.stat-info {
  border-color: #3b82f6 !important;
  background: rgba(59, 130, 246, 0.05);
}

/* ===== Text & Icon Colors ===== */
.critical-text {
  color: #ef4444 !important;
}

.warning-text {
  color: #f97316 !important;
}

.success-text {
  color: #22c55e !important;
}

.info-text {
  color: #3b82f6 !important;
}

/* Typography */
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
