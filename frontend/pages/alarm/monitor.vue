<template>
  <div class="sos-app mt-2">


    <SosAlarmPopupMqtt @triggerUpdateDashboard="RefreshDashboard()" />
    <!-- FILTER BAR -->



    <!-- KPI CARDS (FULL BORDER COLORS) -->
    <v-row dense class="mb-8">

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
            <div class="font-weight-bold mb-4">
              Call Frequency (Hourly)
            </div>

            <div class="bar-wrap">
              <SosChart1 :key="loading" style="height:250px!important" />
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
    <v-progress-linear v-if="loading" indeterminate height="3" class="mb-2" />

  </div>
</template>

<script>

import SosChart1 from "../../components/SOS/SosChart1.vue";
export default {
  components: { SosChart1 },
  name: "SosCallReportFull",
  data() {
    return {
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
    this.getDataFromApi();
  },

  methods: {
    RefreshDashboard() {
      this.getDataFromApi();
    },
    async getDataFromApi() {
      this.loading = true;
      try {




        const { data } = await this.$axios.get("sos_monitor_statistics", {
          params: {
            company_id: this.$auth.user.company_id,

            // date_from: this.date_from || null,
            // date_to: this.date_to || null,
            // alarm_status: this.filterSOSStatus || null,
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
