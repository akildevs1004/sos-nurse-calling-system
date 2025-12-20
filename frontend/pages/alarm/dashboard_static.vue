<template>
  <v-container fluid class="pa-4">
    <!-- ================= TOP BAR (optional) ================= -->
    <div class="d-flex align-center mb-4">
      <div class="text-subtitle-2 grey--text">Dashboard</div>
      <div class="mx-2 grey--text">/</div>
      <div class="text-subtitle-2 font-weight-bold">SOS Floor View</div>
      <v-spacer />
      <div class="d-flex align-center grey--text text-body-2">
        <v-icon small class="mr-1">mdi-clock-outline</v-icon>
        Oct 24, 2023 • 09:42 AM
      </div>
    </div>

    <!-- ================= TOP STATISTICS ================= -->
    <v-row dense>
      <v-col cols="12" sm="6" md="3">
        <v-card outlined class="pa-4">
          <div class="d-flex align-center">
            <div>
              <div class="text-caption grey--text text-uppercase font-weight-bold">Total Points</div>
              <div class="d-flex align-baseline mt-2">
                <div class="text-h4 font-weight-bold">{{ stats.totalPoints }}</div>
                <div class="ml-2 grey--text">Rooms & Toilets</div>
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
        <v-card outlined class="pa-4" :class="'sos-border-orange'">
          <div class="d-flex align-center">
            <div style="width:100%">
              <div class="text-caption text-uppercase font-weight-bold orange--text">Avg Response</div>
              <div class="d-flex align-baseline mt-2">
                <div class="text-h4 font-weight-bold">1:45</div>
                <div class="ml-2 orange--text">min</div>
              </div>
              <v-progress-linear class="mt-3" height="4" value="45" color="orange" rounded />
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
                <div class="ml-3 d-flex">
                  <v-avatar size="24" class="mr-n2" color="grey darken-2"></v-avatar>
                  <v-avatar size="24" class="mr-n2" color="grey darken-3"></v-avatar>
                  <v-avatar size="24" color="grey darken-4">
                    <span class="white--text text-caption font-weight-bold">+4</span>
                  </v-avatar>
                </div>
              </div>
            </div>
            <v-spacer />
            <v-icon color="grey">mdi-badge-account</v-icon>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- ================= FILTER ROW ================= -->
    <div class="d-flex flex-wrap align-center mt-4">
      <!-- Filter buttons -->
      <v-btn-toggle v-model="filterMode" mandatory class="mr-4">
        <v-btn small value="all">All</v-btn>
        <v-btn small value="on">SOS ON</v-btn>
        <v-btn small value="off">SOS OFF</v-btn>
      </v-btn-toggle>

      <!-- Grid per row -->
      <div class="d-flex align-center">
        <div class="text-caption grey--text mr-2 font-weight-bold">Grid / row:</div>
        <v-btn-toggle v-model="perRow" mandatory>
          <v-btn small :value="2">2</v-btn>

          <v-btn small :value="4">4</v-btn>
          <v-btn small :value="6">6</v-btn>
          <!-- <v-btn small :value="8">8</v-btn> -->

        </v-btn-toggle>
      </div>

      <v-spacer />

      <!-- Legend -->
      <div class="d-flex align-center mt-2 mt-md-0">
        <div class="d-flex align-center mr-4">
          <span class="dot dot-red mr-2"></span><span class="text-caption grey--text">SOS ON</span>
        </div>
        <div class="d-flex align-center">
          <span class="dot dot-grey mr-2"></span><span class="text-caption grey--text">SOS OFF</span>
        </div>
      </div>
    </div>

    <!-- ================= CARDS GRID ================= -->
    <div class="mt-4 gridWrap" :style="gridStyle">
      <div v-for="d in filteredDevices" :key="d.id" class="gridItem">
        <v-card outlined class="pa-4 roomCard" :class="cardClass(d)">
          <div class="d-flex align-start">
            <div class="min-w-0">
              <div class="text-h6 font-weight-black text-truncate">
                {{ d.name }}
              </div>

              <v-chip x-small class="mt-2" :color="d.status === 'ON' ? 'red' : 'grey'" dark>
                {{ d.status === 'ON' ? 'PENDING' : 'RESOLVED' }}
              </v-chip>

              <div class="mt-3">
                {{ d.type }}
                <v-icon :color="d.type === 'toilet' ? 'orange' : 'grey'">
                  {{ d.type === 'toilet' ? 'mdi-wheelchair-accessibility' : 'mdi-bed' }}
                </v-icon>
              </div>
            </div>

            <v-spacer />

            <v-btn icon small :color="d.status === 'ON' ? 'red' : 'grey'">
              <v-icon>
                {{ d.status === 'ON' ? 'mdi-bell-alert' : 'mdi-check-circle' }}
              </v-icon>
            </v-btn>
          </div>

          <div class="mt-5 text-center" v-if="d.status === 'ON'">
            <div class="text-h4 font-weight-bold" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
              {{ d.elapsed }}
            </div>
            <div class="text-caption red--text font-weight-bold text-uppercase" style="letter-spacing:.12em">
              Elapsed Time
            </div>
          </div>

          <div class="mt-5 text-center grey--text" v-else>
            <v-icon large color="grey">mdi-check-circle</v-icon>
            <div class="text-body-2 font-weight-medium mt-1">No Active Call</div>
          </div>
        </v-card>
      </div>
    </div>
  </v-container>
</template>

<script>
export default {
  name: "SosFloorView",

  data() {
    return {
      // filters
      filterMode: "all", // all | on | off
      perRow: 4,         // 4 | 6 | 8 | 10

      // STATIC DATA (replace later with API/MQTT)
      devices: [
        { id: 1, name: "Room 101", type: "room", status: "ON", elapsed: "02:14" },
        { id: 2, name: "Room 102", type: "room", status: "OFF", elapsed: "" },
        { id: 3, name: "DT-01 (North)", type: "toilet", status: "ON", elapsed: "00:45" },
        { id: 4, name: "Room 103", type: "room", status: "OFF", elapsed: "" },
        { id: 5, name: "Room 104", type: "room", status: "OFF", elapsed: "" },
        { id: 6, name: "DT-02 (South)", type: "toilet", status: "OFF", elapsed: "" },
        { id: 7, name: "Room 105", type: "room", status: "ON", elapsed: "00:12" },
        { id: 8, name: "Room 106", type: "room", status: "OFF", elapsed: "" },
        { id: 10, name: "Room 102", type: "room", status: "OFF", elapsed: "" },
        { id: 11, name: "DT-01 (North)", type: "toilet", status: "ON", elapsed: "00:45" },
        { id: 12, name: "Room 103", type: "room", status: "OFF", elapsed: "" },
        { id: 13, name: "Room 104", type: "room", status: "OFF", elapsed: "" },
        { id: 14, name: "DT-02 (South)", type: "toilet", status: "OFF", elapsed: "" },
        { id: 15, name: "Room 105", type: "room", status: "ON", elapsed: "00:12" },
        { id: 16, name: "Room 106", type: "room", status: "OFF", elapsed: "" },
        { id: 17, name: "Room 106", type: "room", status: "OFF", elapsed: "" },
        { id: 18, name: "Room 101", type: "room", status: "ON", elapsed: "02:14" },
        { id: 19, name: "Room 105", type: "room", status: "ON", elapsed: "00:12" },
        { id: 20, name: "Room 105", type: "room", status: "ON", elapsed: "00:12" },

      ],
    };
  },

  computed: {
    activeSosCount() {
      return this.devices.filter(d => d.status === "ON").length;
    },

    stats() {
      return {
        totalPoints: this.devices.length,
        activeSos: this.activeSosCount,
        staffOnFloor: 6,
      };
    },

    filteredDevices() {
      if (this.filterMode === "on") return this.devices.filter(d => d.status === "ON");
      if (this.filterMode === "off") return this.devices.filter(d => d.status === "OFF");
      return this.devices;
    },

    // CSS grid for exact per-row counts (4/6/8/10)
    gridStyle() {
      return {
        gridTemplateColumns: `repeat(${this.perRow}, minmax(0, 1fr))`,
      };
    },
  },

  methods: {
    cardClass(d) {
      if (d.status === "ON") return "cardOn";
      return "cardOff";
    },
  },
};
</script>

<style scoped>
/* Grid supports 10-per-row cleanly */
.gridWrap {
  display: grid;
  gap: 14px;
}

/* Make grid responsive: fall back to fewer columns on small screens */
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
