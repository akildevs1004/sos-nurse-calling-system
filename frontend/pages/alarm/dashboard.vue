<template>
  <div class="sos-app mt-2">


    <SosAlarmPopupMqtt @triggerUpdateDashboard="RefreshDashboard()" />
    <!-- FILTER BAR -->

    <v-card class="filters-bar mb-2" outlined :loading="loading">
      <div class="filters-inner">
        <div class="filters-left">
          <div class="filters-label">FILTERS:</div>

          <!-- Last 7 Days -->
          <v-menu offset-y bottom left>
            <template v-slot:activator="{ on, attrs }">
              <v-btn style="max-width:250px" class="filter-pill" v-bind="attrs" v-on="on" depressed>
                <v-icon small class="mr-2">mdi-calendar-month-outline</v-icon>
                <span class="pill-text">{{ filterRangeLabel }}</span>
                <v-icon small class="ml-2 pill-chevron">mdi-chevron-down</v-icon>
              </v-btn>
            </template>

            <v-list dense class="filters-menu">

              <v-list-item @click="setRange('')"><v-list-item-title>Today</v-list-item-title></v-list-item>

              <v-list-item @click="setRange('7')"><v-list-item-title>Last 7 Days</v-list-item-title></v-list-item>
              <v-list-item @click="setRange('30')"><v-list-item-title>Last 30 Days</v-list-item-title></v-list-item>
              <v-list-item @click="setRange('0')"><v-list-item-title>Custom</v-list-item-title></v-list-item>
            </v-list>
          </v-menu>
          <span v-if="range == '0'"
            style="background-color: rgba(255, 255, 255, 0.04) !important;border-radius: 20px;;">
            <CustomFilter class="customFilterdate" style="padding-top: " @filter-attr="filterAttr"
              :default_date_from="date_from" :default_date_to="date_to" :defaultFilterType="1" :height="30"
              :width="250" />
          </span>

          <!-- All Rooms -->
          <v-menu offset-y bottom left>
            <template v-slot:activator="{ on, attrs }">
              <v-btn style="max-width:250px" class="filter-pill" v-bind="attrs" v-on="on" depressed>
                <v-icon small class="mr-2">mdi-domain</v-icon>
                <span class="pill-text">{{ roomLabel }}</span>
                <v-icon small class="ml-2 pill-chevron">mdi-chevron-down</v-icon>
              </v-btn>
            </template>

            <v-list dense class="filters-menu">
              <v-list-item @click="setRoom(null)"><v-list-item-title>All Rooms </v-list-item-title></v-list-item>
              <v-list-item v-for="r in rooms" :key="r.value" @click="setRoom(r)">
                <v-list-item-title>{{ r.name }}</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>




          <!-- All Statuses -->
          <v-menu offset-y bottom left>
            <template v-slot:activator="{ on, attrs }">
              <v-btn style="max-width:250px" class="filter-pill" v-bind="attrs" v-on="on" depressed>
                <v-icon small class="mr-2">mdi-check-decagram-outline</v-icon>
                <span class="pill-text">{{ statusLabel }}</span>
                <v-icon small class="ml-2 pill-chevron">mdi-chevron-down</v-icon>
              </v-btn>
            </template>

            <v-list dense class="filters-menu">
              <v-list-item @click="setStatus(null)"><v-list-item-title>All SOS</v-list-item-title></v-list-item>
              <v-list-item @click="setStatus('ON')"><v-list-item-title>Active (ON)</v-list-item-title></v-list-item>

              <v-list-item @click="setStatus('OFF')"><v-list-item-title>Resolved (OFF)</v-list-item-title></v-list-item>
              <!-- <v-list-item
                @click="setStatus('PENDING')"><v-list-item-title>Acknowledged</v-list-item-title></v-list-item> -->

            </v-list>
          </v-menu>
        </div>

        <v-spacer />

        <v-btn small class="reset-link success" @click="resetFilters">
          Reset Filters
        </v-btn>
      </div>
    </v-card>

    <!-- KPI CARDS (FULL BORDER COLORS) -->
    <v-row dense class="mb-4">

      <!-- Active SOS -->
      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-danger" outlined>
          <v-card-text class="kpi-row">
            <div>
              <div class="metric-title">Active SOS</div>
              <div class="metric-value-xl danger-text">
                {{ totalSOSActive }}
              </div>
            </div>
            <v-avatar size="48" class="metric-icon error">
              <v-icon dark size="26">mdi-bell</v-icon>
            </v-avatar>

          </v-card-text>
        </v-card>
      </v-col>

      <!-- Resolved -->
      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-success" outlined>
          <v-card-text class="kpi-row">
            <div>
              <div class="metric-title">Resolved</div>
              <div class="metric-value-xl success-text">
                {{ totalResolved }}
              </div>
            </div>

            <v-avatar size="48" class="metric-icon success">
              <v-icon dark size="26">mdi-check-circle</v-icon>
            </v-avatar>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Total Calls -->
      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-primary" outlined>
          <v-card-text class="kpi-row">
            <div>
              <div class="metric-title">Total Calls</div>
              <div class="metric-value-xl primary-text">
                {{ totalSOSCount }}
              </div>
            </div>

            <v-avatar size="48" class="metric-icon primary">
              <v-icon dark size="26">mdi-phone-in-talk</v-icon>
            </v-avatar>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Avg Response -->
      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-warning" outlined>
          <v-card-text class="kpi-row">
            <div>
              <div class="metric-title">Avg Response</div>
              <div class="metric-value-xl warning-text">
                {{ averageMinutes }}
              </div>
            </div>

            <v-avatar size="48" class="metric-icon warning">
              <v-icon dark size="26">mdi-timer-outline</v-icon>
            </v-avatar>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Gateways Online -->
      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-info" outlined>
          <v-card-text class="kpi-row">
            <div>
              <div class="metric-title">Gateways Online</div>
              <div class="metric-value-xl info-text">
                {{ totalDeviceOnline }}
              </div>
            </div>

            <v-avatar size="48" class="metric-icon info">
              <v-icon dark size="26">mdi-wifi</v-icon>
            </v-avatar>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Gateways Offline -->
      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-danger" outlined>
          <v-card-text class="kpi-row">
            <div>
              <div class="metric-title">Gateways Offline</div>
              <div class="metric-value-xl danger-text">
                {{ totalDeviceOffline }}
              </div>
            </div>

            <v-avatar size="48" class="metric-icon error">
              <v-icon dark size="26">mdi-wifi-off</v-icon>
            </v-avatar>


          </v-card-text>
        </v-card>
      </v-col>

    </v-row>


    <!-- CHARTS -->
    <v-row dense>
      <!-- CALL FREQUENCY -->
      <v-col cols="12" lg="8">
        <v-card class="panel" outlined height="320">
          <v-card-text>
            <div class="font-weight-bold mb-2">
              Call Frequency (Hourly)
            </div>

            <div class="bar-wrap">
              <SosChart1 :key="key" style="height:250px!important" :date_from="date_from || null"
                :date_to="date_to || null" :sosStatus="status || null" :roomType="room?.value || null" />
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- CALL SOURCES -->
      <v-col cols="12" lg="4">
        <v-card class="panel" outlined height="320">
          <v-card-text>
            <div class="font-weight-bold mb-6">Call Sources</div>

            <div v-for="s in sources" :key="s.name" class="mb-5">
              <div class="d-flex justify-space-between align-center">
                <div class="font-weight-medium">
                  <v-icon small :class="s.color" class="mr-1">
                    {{ s.icon }}
                  </v-icon>
                  {{ s.name }}
                </div>
                <div class="muted-text">{{ s.pct }}%</div>
              </div>

              <v-progress-linear :value="s.pct" height="8" rounded :class="s.bar" />
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>


    <!-- <v-progress-linear v-if="loading" indeterminate height="3" class="mb-2" /> -->
    <v-row>
      <v-col>
        <SOSLogs :key="logskey" />
      </v-col>
    </v-row>
  </div>
</template>

<script>

import SosChart1 from "../../components/SOS/SosChart1.vue";
import SOSLogs from "../../components/SOS/SOSLogs.vue";

export default {
  components: { SosChart1, SOSLogs },
  name: "SosCallReportFull",
  data() {
    return {
      key: 0,
      logskey: 0,
      date_from: "",
      date_to: "",

      range: "",            // "7" | "30" | "all"
      room: null,            // {id,name} | null
      source: null,          // {value,text} | null
      status: null,          // "ON" | "OFF" | null

      // demo lists (replace with your API data)
      rooms: [{ value: "room", name: "Rooms" }, { value: "toilet", name: "Toilets" }, { value: "toilet-ph", name: "Toilets - Handicapped" }],



      loading: false,
      totalSOSCount: 0,
      totalResolved: 0,
      totalSOSActive: 0,
      averageMinutes: 0,
      totalDeviceOnline: 0,
      totalDeviceOffline: 0,


      metrics: {
        total: 142,
        avg: "1m 12s",
        resolved: 138,
        active: 4,
      },
      hourly: [15, 10, 12, 22, 38, 55, 70, 90, 60, 42, 30, 18],
      sources: [
        {
          name: "Bedside",
          pct: 62,
          icon: "mdi-bed-outline",
          color: "text-primary",
          bar: "bar-primary",
        },
        {
          name: "Toilet / Bath",
          pct: 28,
          icon: "mdi-toilet",
          color: "text-info",
          bar: "bar-info",
        },
        {
          name: "Code Blue",
          pct: 10,
          icon: "mdi-alert-octagram",
          color: "text-error",
          bar: "bar-danger",
        },
      ],
    };
  },

  mounted() {

    const today = new Date();


    this.date_from = today.toISOString().slice(0, 10); // YYYY-MM-DD
    this.date_to = today.toISOString().slice(0, 10);
    this.getDataFromApi();
  },
  computed: {
    filterRangeLabel() {
      if (this.range === "7") return "Last 7 Days";
      if (this.range === "30") return "Last 30 Days";
      if (this.range === "") return "Today";
      if (this.range === "0") return "Custom";


      return "Custom";
    },
    roomLabel() {
      console.log("Room- ", this.room);

      return this.room?.name || "All Rooms";
    },
    sourceLabel() {
      return this.source?.text || "All Sources";
    },
    statusLabel() {
      if (!this.status) return "All SOS";
      return this.status === "ON" ? "Active" : this.status === "PENDING" ? "Acknowledged" : "Resolved";
    },

  },
  methods: {
    async loadData() {

      // console.log("this.room", this.room);

      await this.getDataFromApi();
      // await this.getStatsApi();
    },
    filterAttr(data) {
      if (data?.from)
        this.date_from = data?.from;
      if (data?.to)
        this.date_to = data?.to;
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


      const today = new Date();


      this.date_from = today.toISOString().slice(0, 10); // YYYY-MM-DD
      this.date_to = today.toISOString().slice(0, 10);



      this.roomType = "";
      this.sosStatus = "";
      this.loadData();
    },
    RefreshDashboard() {
      this.getDataFromApi();
    },
    async getDataFromApi() {
      this.loading = true;
      try {




        const { data } = await this.$axios.get("sos_monitor_statistics", {
          params: {
            company_id: this.$auth.user.company_id,

            date_from: this.date_from || null,
            date_to: this.date_to || null,
            sosStatus: this.status || null,
            roomType: this.room?.value || null,

          },
        });

        if (data) {
          this.totalSOSCount = data.totalSOSCount || 0;
          this.totalResolved = data.totalResolved || 0;
          this.totalSOSActive = data.totalSOSActive || 0;
          this.averageMinutes = data.averageMinutes || 0;
          this.totalDeviceOnline = data.totalDeviceOnline || 0;
          this.totalDeviceOffline = data.totalDeviceOffline || 0;


        }

        setTimeout(() => {
          this.key++;
        }, 1000);

        setTimeout(() => {
          this.logskey++;
        }, 3000);

      } catch (e) {
        console.error("SOS reports fetch failed:", e);

      } finally {
        this.loading = false;
      }
    },


  }
};
</script>

<style scoped>
/* ===== THEME ===== */
.sos-app {
  --bg: #f6f7f8;
  --panel: #ffffff;
  --border: rgba(0, 0, 0, .08);
}

.theme--dark .sos-app {
  --bg: #101922;
  --panel: #1c2632;
  --border: #233648;
}

.v-main__wrap {
  background: var(--bg);
}

/* ===== PANELS ===== */
.panel {
  background: var(--panel);
  border-radius: 16px;
  border: 1px solid var(--border);
}

/* ===== KPI CARDS (FULL BORDER COLORS) ===== */
.metric {
  min-height: 130px;
  border-width: 2px !important;
  border-style: solid !important;
}

.metric-border-primary {
  border-color: var(--v-primary-base) !important;
}

.metric-border-warning {
  border-color: var(--v-warning-base) !important;
}

.metric-border-success {
  border-color: var(--v-success-base) !important;
}

.metric-border-danger {
  border-color: var(--v-error-base) !important;
}

.metric-title {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  opacity: .7;
}

.metric-value {
  font-size: 30px;
  font-weight: 900;
  margin: 6px 0;
}

.metric-sub {
  font-size: 12px;
  opacity: .6;
}

/* ===== BAR CHART ===== */


.bar-col {
  flex: 1;
}

.bar {
  background: rgba(25, 118, 210, .35);
  width: 100%;
  border-radius: 4px 4px 0 0;
}

/* ===== CALL SOURCE COLORS ===== */
.bar-primary :deep(.v-progress-linear__determinate) {
  background: #1976d2
}

.bar-info :deep(.v-progress-linear__determinate) {
  background: #0288d1
}

.bar-danger :deep(.v-progress-linear__determinate) {
  background: #e53935
}

/* ===== UTILS ===== */
.muted-text {
  opacity: .6
}

.btn-soft {
  text-transform: none;
  font-weight: 700
}

.btn-primary {
  text-transform: none;
  font-weight: 700
}

.chip-btn {
  text-transform: none
}

/* ===== KPI ICONS ===== */
.metric-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

.metric-icon.primary {
  background: var(--c-primary);
}

.metric-icon.info {
  background: var(--c-info);
}

.metric-icon.success {
  background: var(--c-success);
}

.metric-icon.warning {
  background: var(--c-warning);
}

.metric-icon.danger {
  background: var(--c-error);
}

/* ===== KPI COUNT COLORS ===== */
.primary-text {
  color: var(--c-primary);
}

.info-text {
  color: var(--c-info);
}

.success-text {
  color: var(--c-success);
}

.warning-text {
  color: var(--c-warning);
}

.danger-text {
  color: var(--c-error);
}

/* tighten layout slightly */
.metric-value {
  line-height: 1.1;
}

/* KPI row layout */
.kpi-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Extra large numbers */
.metric-value-xl {
  font-size: 38px;
  font-weight: 900;
  line-height: 1.05;
  margin-top: 4px;
}

/* Icons */
.metric-icon {
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* icon backgrounds */
.metric-icon.primary {
  background: var(--c-primary);
}

.metric-icon.info {
  background: var(--c-info);
}

.metric-icon.success {
  background: #4caf5042;
}

.metric-icon.warning {
  background: var(--c-warning);
}

.metric-icon.danger {
  background: var(--c-error);
}

/* number colors */
.primary-text {
  color: var(--c-primary);
}

.info-text {
  color: var(--c-info);
}

.success-text {
  color: var(--c-success);
}

.warning-text {
  color: var(--c-warning);
}

.danger-text {
  color: var(--c-error);
}
</style>


<style scoped>
/* Container */
.filters-bar {
  border-radius: 14px;
  border: 1px solid var(--c-border) !important;
  background: var(--c-panel);
}

/* Layout */
.filters-inner {
  display: flex;
  align-items: center;
  padding: 10px 14px;
  gap: 10px;
}

.filters-left {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.filters-label {
  font-weight: 800;
  letter-spacing: .06em;
  font-size: 12px;
  opacity: .7;
  margin-right: 6px;
}

/* Pills */
.filter-pill {
  height: 34px !important;
  border-radius: 999px !important;
  padding: 0 12px !important;
  border: 1px solid var(--c-border) !important;

  /* subtle pill background like screenshot */
  background: rgba(255, 255, 255, 0.04) !important;
}

.theme--light .filter-pill {
  background: rgba(0, 0, 0, 0.03) !important;
}

.pill-text {
  font-weight: 700;
  font-size: 13px;
  opacity: .9;
}

.pill-chevron {
  opacity: .7;
}

/* Dropdown menu surface */
.filters-menu {
  border-radius: 12px;
  overflow: hidden;
}

/* Reset link */
.reset-link {
  text-transform: none;
  font-weight: 800;
  letter-spacing: .01em;
}
</style>

<style>
.customFilterdate .mx-input {
  border: 0px solid #9e9e9e !important;
  border-radius: 20px !important;
  ;
}

.customFilterdate .backgroundcolordate {
  background-color: red !important;
  padding: 10px;
}
</style>
