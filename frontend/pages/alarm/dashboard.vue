<template>
  <v-container fluid class="pa-4">

    <SosAlarmPopupMqtt v-if="companyDeviceSerials" :allowedSerials="companyDeviceSerials"
      @triggerUpdateDashboard="RefreshDashboard()" />

    <!-- ================= TOP STATISTICS ================= -->
    <v-row dense>
      <v-col cols="12" sm="6" md="3">

        <v-card outlined class="pa-4">
          <div class="d-flex align-center">
            <div>
              <div class="text-caption grey--text text-uppercase font-weight-bold">Total Rooms</div>
              <div class="d-flex align-baseline mt-2">
                <div class="text-h4 font-weight-bold">{{ stats.totalPoints }}</div>
                <!-- <div class="ml-2 grey--text">Rooms & Toilets</div> -->
              </div>
            </div>
            <v-spacer />
            <v-icon color="grey">mdi-grid</v-icon>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card outlined class="pa-4" :class="activeSosCount ? 'sos-border-red' : ''">
          <div class="d-flex align-center">
            <div>
              <div class="text-caption text-uppercase font-weight-bold red--text">Active SOS</div>
              <div class="d-flex align-baseline mt-2">
                <div class="text-h4 font-weight-bold">{{ stats.activeSos }}</div>
                <div class="ml-2 red--text">Calls Active</div>
              </div>
            </div>
            <v-spacer />
            <v-icon color="red">mdi-bell-alert</v-icon>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card outlined class="pa-4 sos-border-orange">
          <div class="d-flex align-center">
            <div style="width:100%">
              <div class="text-caption text-uppercase font-weight-bold orange--text">Avg Response</div>
              <div class="d-flex align-baseline mt-0">
                <div class="text-h4 font-weight-bold">{{ avgResponseText }}</div>
                <div class="ml-2 orange--text">min</div>
              </div>
              <v-progress-linear class="mt-1" height="4" :value="avgResponsePct" color="orange" rounded />
            </div>
            <v-spacer />
            <v-icon color="orange">mdi-timer</v-icon>
          </div>
        </v-card>
      </v-col>

      <v-col cols="12" sm="6" md="3">
        <v-card outlined class="pa-4">
          <div class="d-flex align-center">
            <div>
              <div class="text-caption grey--text text-uppercase font-weight-bold">Staff on Floor</div>
              <div class="d-flex align-center mt-2">
                <div class="text-h4 font-weight-bold">{{ stats.staffOnFloor }}</div>
              </div>
            </div>
            <v-spacer />
            <v-icon color="grey">mdi-badge-account</v-icon>
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
        <div class="text-caption grey--text mr-2 font-weight-bold">Grid / row:</div>
        <v-btn-toggle v-model="perRow" mandatory>
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

    <v-alert v-if="apiError" type="error" dense text class="mb-2">
      {{ apiError }}
    </v-alert>
    <v-progress-linear v-if="loading" indeterminate height="3" class="mb-2" />

    <!-- ================= CARDS GRID ================= -->
    <v-card outlined class="pa-4 roomCard">
      <div class="gridWrap" :style="gridStyle">
        <div v-for="d in filteredDevices" :key="d.id" class="gridItem">
          <v-card outlined class="pa-4 roomCard roomCardIndividual" :class="cardClass(d)">
            <div class="d-flex align-start">
              <div class="min-w-0">
                <div class="text-h6 font-weight-black text-truncate">{{ d.name }}</div>

                <div class="mt-3">
                  <v-icon v-if="d.room_type == 'toilet'" color="yellow"> mdi-toilet </v-icon> <v-icon
                    v-if="d.room_type == 'room'" color="blue"> mdi-bed </v-icon> <v-icon v-if="d.room_type == 'room-pd'"
                    color="red"> mdi-bed </v-icon> <v-icon v-if="d.room_type == 'toilet-pd'" color="yellow"> mdi-toilet
                  </v-icon> <v-icon color="red" v-if="d.room_type == 'toilet-pd'"> mdi-wheelchair </v-icon> <v-icon
                    v-if="!d.room_type" color="blue"> mdi-bed </v-icon>
                </div>
              </div>

              <v-spacer />

              <div>
                <v-icon v-if="d.alarm_status === true" color="red">mdi-bell</v-icon>
                <v-icon v-if="d.device?.status_id == 1" color="green">mdi-wifi</v-icon>
                <v-icon v-else-if="d.device?.status_id == 2" color="red">mdi-wifi-off</v-icon>
              </div>
            </div>

            <div style="width:100%; text-align:right;">
              <v-chip x-small class="mt-2" :color="d.alarm_status === true ? 'red' : 'grey'" dark>
                {{ d.alarm_status === true ? 'PENDING' : 'RESOLVED' }}
              </v-chip>
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

  </v-container>
</template>

<script>

import SosAlarmPopupMqtt from '../../components/SOS/SosAlarmPopupMqtt.vue';

export default {
  name: "SosFloorView",
  components: { SosAlarmPopupMqtt },

  data() {
    return {
      companyDeviceSerials: null,

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
    await this.getDataFromApi();
    await this.getStatsApi();
    await this.getDevicesFromApi();
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
    async RefreshDashboard() {
      await this.getDataFromApi();
      await this.getStatsApi();

    },
    cardClass(d) {
      return d?.alarm_status === true ? "cardOn" : "cardOff";
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
    async getStatsApi() {
      this.loading = true;
      this.apiError = "";

      try {
        const { data } = await this.$axios.get("dashboard_stats", {
          params: {
            company_id: this.$auth.user.company_id,

          },
        });

        // supports array or paginator {data:[...]}
        const list = data;


        this.avgResponseText = this.$dateFormat.minutesToHHMM(list.sla_percentage);
        this.avgResponsePct = list.sla_minutes;


      } catch (err) {
        this.apiError =
          err?.response?.data?.message ||
          err?.message ||
          "Failed to load dashboard rooms";
      } finally {
        this.loading = false;
      }

    },
    async getDevicesFromApi() {
      this.loading = true;
      this.apiError = "";

      try {
        const { data } = await this.$axios.get("device-list", {
          params: {
            company_id: this.$auth.user.company_id,

          },
        });

        // supports array or paginator {data:[...]}
        this.companyDeviceSerials = data.map(d => d.serial_number);
        this.devices
      } catch (err) {
        this.apiError =
          err?.response?.data?.message ||
          err?.message ||
          "Failed to load dashboard rooms";
      } finally {
        this.loading = false;
      }
    },
    async getDataFromApi() {
      this.loading = true;
      this.apiError = "";

      try {
        const { data } = await this.$axios.get("dashboard_rooms", {
          params: {
            company_id: this.$auth.user.company_id,
            per_page: 200,
          },
        });

        // supports array or paginator {data:[...]}
        const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
        this.devices = this.normalizeRooms(list);

        // compute immediately (no waiting for 1s)
        this.updateDurationAll();
      } catch (err) {
        this.apiError =
          err?.response?.data?.message ||
          err?.message ||
          "Failed to load dashboard rooms";
      } finally {
        this.loading = false;
      }
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

.cardOn {
  border: 2px solid rgba(239, 68, 68, 0.95);
  box-shadow: 0 0 18px rgba(239, 68, 68, 0.18);
}

.cardOff {
  border: 1px solid rgba(148, 163, 184, 0.35);
}

.sos-border-red {
  border-color: rgba(239, 68, 68, 0.35) !important;
}

.sos-border-orange {
  border-color: rgba(249, 115, 22, 0.35) !important;
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
</style>
