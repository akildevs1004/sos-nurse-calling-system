<template>
  <div class="sos-app mt-2">


    <SosAlarmPopupMqtt v-if="companyDeviceSerials" :allowedSerials="companyDeviceSerials"
      @triggerUpdateDashboard="RefreshDashboard()" />
    <!-- FILTER BAR -->
    <v-card class="panel mb-8" outlined>
      <v-card-text class="d-flex align-center flex-wrap">
        <span class="font-weight-bold text-uppercase muted-text mr-4">
          Filters
        </span>

        <v-btn class="chip-btn mr-2">Last 7 Days</v-btn>
        <v-btn class="chip-btn mr-2">All Rooms</v-btn>
        <v-btn class="chip-btn mr-2">All Sources</v-btn>
        <v-btn class="chip-btn mr-2">All Status</v-btn>

        <v-spacer />

        <!-- <v-switch v-model="isDark" inset hide-details label="Dark Mode" /> -->
      </v-card-text>
    </v-card>

    <!-- KPI CARDS (FULL BORDER COLORS) -->
    <v-row dense class="mb-8">
      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-danger" outlined>
          <v-card-text>
            <div class="metric-title">Active SOS</div>
            <div class="metric-value">{{ totalSOSActive }}</div>
            <!-- <v-chip small outlined color="error">Immediate Action</v-chip> -->
          </v-card-text>
        </v-card>
      </v-col>


      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-success" outlined>
          <v-card-text>
            <div class="metric-title">Resolved</div>
            <div class="metric-value">{{ totalResolved }}</div>
            <v-progress-linear :value="97" height="6" rounded />
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-primary" outlined>
          <v-card-text>
            <div class="metric-title">Total Calls</div>
            <div class="metric-value">{{ totalSOSCount }}</div>
            <!-- <div class="metric-sub">+12% vs last week</div> -->
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-warning" outlined>
          <v-card-text>
            <div class="metric-title">Avg Response Time</div>
            <div class="metric-value">{{ averageMinutes }}</div>
            <!-- <div class="metric-sub">Target &lt; 1m 30s</div> -->
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-warning" outlined>
          <v-card-text>
            <div class="metric-title">Gateways Online</div>
            <div class="metric-value">{{ totalDeviceOnline }}</div>
            <!-- <div class="metric-sub">Target &lt; 1m 30s</div> -->
          </v-card-text>
        </v-card>
      </v-col><v-col cols="12" sm="6" lg="2">
        <v-card class="metric panel metric-border-warning" outlined>
          <v-card-text>
            <div class="metric-title">Gateways Offline</div>
            <div class="metric-value">{{ totalDeviceOffline }}</div>
            <!-- <div class="metric-sub">Target &lt; 1m 30s</div> -->
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
              <SosChart1 />
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

  </div>
</template>

<script>

import SosChart1 from "../../components/SOS/SosChart1.vue";
export default {
  components: { SosChart1 },
  name: "SosCallReportFull",
  data() {
    return {

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
</style>
