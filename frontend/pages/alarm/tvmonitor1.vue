<!-- TvDashboard.vue -->
<template>
  <div class="tv-page" :class="deviceClass">
    <v-snackbar v-model="snackbar" :timeout="3000" elevation="24" class="center-snackbar">
      {{ snackbarResponse }}
    </v-snackbar>

    <SosAlarmPopupMqtt @triggerUpdateDashboard="requestDashboardSnapshot()" />

    <!-- DETAILS POPUP (for 6-cards mode) -->
    <v-dialog v-model="detailsDialog" content-class="tvDetailsDialog" persistent>
      <v-card class="tvDetailsCard">
        <v-card-title class="d-flex align-center">
          <div class="font-weight-black">{{ selectedRoom?.name || "Room" }}</div>
          <v-spacer />
          <v-btn icon @click="detailsDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>

        <v-card-text v-if="selectedRoom">
          <div class="d-flex flex-wrap" style="gap:10px">
            <v-chip small :color="selectedRoom.alarm_status ? 'red' : 'grey'">
              {{ selectedRoom.alarm_status ? "SOS ON" : "SOS OFF" }}
            </v-chip>

            <v-chip small
              :color="selectedRoom.alarm_status ? (selectedRoom.alarm?.responded_datetime ? '#f97316' : 'red') : 'grey'">
              {{
                selectedRoom.alarm_status
                  ? (selectedRoom.alarm?.responded_datetime ? "ACKNOWLEDGED" : "PENDING")
                  : "RESOLVED"
              }}
            </v-chip>

            <v-chip small :color="selectedRoom.device?.status_id == 1 ? 'green' : 'red'">
              {{ selectedRoom.device?.status_id == 1 ? "SIGNAL OK" : "SIGNAL OFF" }}
            </v-chip>
          </div>

          <div class="mt-4">
            <div class="text-caption grey--text">Alarm Start</div>
            <div class="font-weight-bold">
              {{ selectedRoom.alarm?.alarm_start_datetime || selectedRoom.alarm_start_datetime || "-" }}
            </div>
          </div>

          <div class="mt-3" v-if="selectedRoom.alarm_status">
            <div class="text-caption grey--text">Duration</div>
            <div class="font-weight-black" style="font-size:28px">
              {{ selectedRoom.duration || "00:00:00" }}
            </div>
          </div>

          <div class="mt-4 d-flex justify-end"
            v-if="selectedRoom.alarm_status && !selectedRoom.alarm?.responded_datetime">
            <v-btn color="warning" @click="udpateResponse(selectedRoom.alarm?.id)">
              <v-icon left>mdi-cursor-default-click</v-icon>
              Acknowledge
            </v-btn>
          </div>

          <div class="mt-3" v-if="selectedRoom.alarm_status && selectedRoom.alarm?.responded_datetime">
            <div class="text-caption grey--text">Acknowledged At</div>
            <div class="font-weight-bold">{{ selectedRoom.alarm?.responded_datetime }}</div>
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>

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
          <!-- <div class="tvResolution mr-3">
            {{ screenWidth }} × {{ screenHeight }} px
          </div> -->
          <v-icon style="text-align: center;padding-right:5px;" @click="reload">mdi-refresh-circle </v-icon>


          <v-btn x-small outlined color="error" @click="logout" style="margin-right:10px">
            Logout
          </v-btn>
        </div>
      </div>

      <!-- STATS: ONE ROW -->
      <v-row dense class="tvStatsRow mx-0">
        <v-col v-for="s in statCards" :key="s.key" cols="2" class="tvStatCol">
          <v-card outlined style="width:100%" class="pa-3 roomCard stat-card1 tvStatCard" :class="s.cardClass">
            <div class="d-flex align-center" style="width:100%">
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

              <v-icon style="text-align: right;" class="stat-icon" :class="s.textClass">{{ s.icon }}</v-icon>
            </div>

            <!-- <v-progress-linear v-if="s.key === 'avg'" class="mt-1" height="4" :value="avgResponsePct" rounded
              style="margin-top:-10px!important" /> -->
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

      <!-- ROOMS AREA -->
      <div class="tvRoomsArea">
        <div class="roomsGrid tvRoomsGrid" :style="roomsGridStyle">
          <div v-for="d in pagedDevices" :key="d.id || d.room_id" class="roomCell">
            <v-card outlined class="pa-2 roomCard roomCardIndividual tvRoomCard"
              :class="[cardClass(d), 'room-cols-' + roomsPerRow, roomsPerRow === 6 ? 'clickableCard' : '']"
              @click="onRoomClick(d)">
              <!-- MODE 2: FULL DETAILS -->
              <template v-if="roomsPerRow === 2">
                <div class="roomLane roomLane2">
                  <div class="laneName text-truncate">{{ d.name }}</div>

                  <v-icon class="laneIcon"
                    :color="d.device?.status_id == 1 ? 'green' : (d.device?.status_id == 2 ? 'red' : '')">
                    {{ d.device?.status_id == 1 ? "mdi-wifi" : (d.device?.status_id == 2 ? "mdi-wifi-off" : "mdi-wifi")
                    }}
                  </v-icon>

                  <v-icon v-if="d.alarm_status === true" class="laneIcon" color="red">mdi-bell</v-icon>
                  <v-icon v-else class="laneIcon" color="grey">mdi-bell-outline</v-icon>

                  <v-chip x-small class="laneChip" v-if="d.alarm_status === true"
                    :color="d.alarm?.responded_datetime ? '#f97316' : 'red'">
                    {{ d.alarm?.responded_datetime ? "ACK" : "PENDING" }}
                  </v-chip>

                  <v-btn x-small class="laneAckBtn blink-btn"
                    v-if="(d.alarm_status === true && !d.alarm?.responded_datetime)"
                    @click.stop="udpateResponse(d.alarm?.id)">
                    ACK
                  </v-btn>
                </div>
                <!-- ROOM ICONS -->
                <div class="roomIconsRow">
                  <!-- MAIN ICON -->
                  <v-icon class="roomMainIcon" :class="isToilet(d) ? 'is-toilet' : 'is-bed'">
                    {{ isToilet(d) ? 'mdi-toilet' : 'mdi-bed' }}
                  </v-icon>

                  <!-- PH / DISABLED OVERLAY -->
                  <v-icon v-if="isPh(d)" class="roomPhIcon">
                    mdi-wheelchair
                  </v-icon>
                </div>
                <div class="laneDurationWrap" v-if="d.alarm_status === true">
                  <div class="laneDuration mono">{{ d.duration || "00:00:00" }}</div>
                </div>

                <div class="laneStartWrap" v-if="d.alarm_status === true" style="padding-bottom:10px">
                  <div class="laneStartText text-truncate">
                    Start: {{ d.alarm?.alarm_start_datetime || d.alarm_start_datetime }}
                  </div>
                </div>

                <div class="laneOkWrap" v-else>
                  <v-icon color="green" class="okIcon">mdi-check-circle</v-icon>
                  <span class="okText">No Active Call</span>
                </div>
              </template>

              <!-- MODE 6: ICONS ONLY -->
              <template v-else>
                <div class="miniCard">
                  <div class="miniTop">
                    <div class="miniName text-truncate">{{ d.name }}</div>
                    <v-chip x-small class="miniChip"
                      :color="d.alarm_status === true ? (d.alarm?.responded_datetime ? '#f97316' : 'red') : 'grey'">
                      {{
                        d.alarm_status === true
                          ? (d.alarm?.responded_datetime ? "ACK" : "ON")
                          : "OK"
                      }}
                    </v-chip>
                  </div>

                  <div class="miniIcons">
                    <!-- ROOM ICON -->
                    <v-icon class="miniIcon" :class="isToilet(d) ? 'is-toilet' : 'is-bed'">
                      {{ isToilet(d) ? 'mdi-toilet' : 'mdi-bed' }}
                    </v-icon>

                    <!-- PH -->
                    <v-icon v-if="isPh(d)" class="miniIcon is-ph">
                      mdi-wheelchair
                    </v-icon>

                    <!-- SIGNAL -->
                    <v-icon class="miniIcon" :color="d.device?.status_id == 1 ? 'green' : 'red'">
                      {{ d.device?.status_id == 1 ? 'mdi-wifi' : 'mdi-wifi-off' }}
                    </v-icon>

                    <!-- ALARM -->
                    <v-icon class="miniIcon" :color="d.alarm_status ? 'red' : 'grey'">
                      {{ d.alarm_status ? 'mdi-bell' : 'mdi-bell-outline' }}
                    </v-icon>
                  </div>

                  <div class="miniHint text-caption grey--text">Click for details</div>
                </div>
              </template>
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
      autoPageTimer: null,
      AUTO_PAGE_MS: 5000, // change interval (5 seconds)
      autoPagingEnabled: true, // you can toggle if needed
      // UI
      filterMode: "all",
      devices: [],
      loading: false,

      snackbar: false,
      snackbarResponse: "",

      // ONLY 2 or 6
      roomsPerRow: 2,

      // TV paging (NO SCROLL)
      pageIndex: 0,
      tvRowsPerScreen: 3,

      // Screen size indicator
      screenWidth: 0,
      screenHeight: 0,

      // Popup
      detailsDialog: false,
      selectedRoom: null,

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

    roomsGridStyle() {
      const cols = [2, 6].includes(Number(this.roomsPerRow)) ? Number(this.roomsPerRow) : 2;
      const maxW = cols === 2 ? "520px" : "240px";
      const maxH = cols === 2 ? "210px" : "140px";
      return { "--rooms-cols": cols, "--card-max-w": maxW, "--card-max-h": maxH };
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

    // NO-SCROLL PAGINATION
    pageSize() {
      const cols = [2, 6].includes(Number(this.roomsPerRow)) ? Number(this.roomsPerRow) : 2;
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
        { key: "avg", title: "Avg Response", value: this.avgResponseText, sub: " ", icon: "mdi-timer", cardClass: "stat-teal", textClass: "teal-text" }
      ];
    }
  },
  watch: {
    totalPages() {
      // keep pageIndex valid
      if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;
      // restart timer based on new totalPages
      this.startAutoPaging();
    },

    roomsPerRow() {
      this.pageIndex = 0;
      this.startAutoPaging();
    },

    filterMode() {
      this.pageIndex = 0;
      this.startAutoPaging();
    },

    filteredDevices() {
      if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;
      this.startAutoPaging();
    },

    roomsPerRow(v) {
      const n = Number(v);
      this.safeLsSet(ROOMS_PER_ROW_KEY, String(n));
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

    const saved = Number(this.safeLsGet(ROOMS_PER_ROW_KEY));
    if ([2, 6].includes(saved)) this.roomsPerRow = saved;
    else this.roomsPerRow = 2;

    const sosRooms = this.$auth?.user?.security?.sos_rooms ?? [];
    this.filterRoomTableIds = Array.isArray(sosRooms) ? sosRooms.map(r => r.id) : [];
  },

  mounted() {
    this.updateScreenSize();
    window.addEventListener("resize", this.updateScreenSize);

    this.timer = setInterval(() => this.updateDurationAll(), this.TIMER_MS);

    this.mqttUrl = process.env.MQTT_SOCKET_HOST;
    this.connectMqtt();

    this.startAutoPaging();

  },

  beforeDestroy() {
    window.removeEventListener("resize", this.updateScreenSize);
    try {
      if (this.timer) clearInterval(this.timer);
      this.disconnectMqtt();
    } catch (e) { }

    this.stopAutoPaging();

  },

  methods: {
    startAutoPaging() {
      // stop old timer first
      this.stopAutoPaging();

      if (!this.autoPagingEnabled) return;
      if (!Number.isFinite(this.totalPages) || this.totalPages <= 1) return;

      this.autoPageTimer = setInterval(() => {
        // only rotate if still multiple pages
        if (this.totalPages <= 1) return;

        // go next, or loop to first
        if (this.pageIndex < this.totalPages - 1) {
          this.pageIndex++;
        } else {
          this.pageIndex = 0;
        }
      }, this.AUTO_PAGE_MS);
    },

    stopAutoPaging() {
      if (this.autoPageTimer) {
        clearInterval(this.autoPageTimer);
        this.autoPageTimer = null;
      }
    },

    // OPTIONAL: if user clicks, pause rotation briefly then resume
    pauseAutoPaging(ms = 12000) {
      this.stopAutoPaging();
      if (!this.autoPagingEnabled) return;
      setTimeout(() => this.startAutoPaging(), ms);
    },

    nextPage() {
      if (this.pageIndex < this.totalPages - 1) this.pageIndex++;
      else this.pageIndex = 0;

      // optional pause after manual action
      this.pauseAutoPaging();
    },

    prevPage() {
      if (this.pageIndex > 0) this.pageIndex--;
      else this.pageIndex = Math.max(0, this.totalPages - 1);

      // optional pause after manual action
      this.pauseAutoPaging();
    },
    isToilet(d) {
      return d.room_type === 'toilet' || d.room_type === 'toilet-ph';
    },
    isPh(d) {
      return d.room_type === 'room-ph' || d.room_type === 'toilet-ph';
    },
    // SAFE LOCALSTORAGE
    safeLsGet(key) {
      try {
        if (typeof window === "undefined") return null;
        if (!window.localStorage) return null;
        return window.localStorage.getItem(key);
      } catch (e) {
        return null;
      }
    },
    safeLsSet(key, value) {
      try {
        if (typeof window === "undefined") return;
        if (!window.localStorage) return;
        window.localStorage.setItem(key, value);
      } catch (e) { }
    },

    updateScreenSize() {
      this.screenWidth = window.innerWidth;
      this.screenHeight = window.innerHeight;
    },

    onRoomClick(room) {
      if (Number(this.roomsPerRow) !== 6) return;
      this.selectedRoom = room;
      this.detailsDialog = true;
    },

    // ✅ FIX: cardClass method (was missing)
    cardClass(d) {
      if (!d) return "";
      if (d.alarm_status === true && !d.alarm?.responded_datetime) return "cardOn";
      if (d.alarm_status === true && d.alarm?.responded_datetime) return "cardAck";
      return "cardOff";
    },

    nextPage() {
      if (this.pageIndex < this.totalPages - 1) this.pageIndex++;
    },
    prevPage() {
      if (this.pageIndex > 0) this.pageIndex--;
    },

    logout() {
      this.$router.push("/logout");
    },

    // MQTT
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
          if (err) return;
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
    reload() {

      try { window.location.reload(); } catch (e) { }
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
          this.client.publish(
            `tv/${companyId}/dashboard_alarm_response`,
            JSON.stringify(payload),
            { qos: 0, retain: false }
          );
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
/* ===== KILL HORIZONTAL SCROLL (CRITICAL) ===== */
:global(html),
:global(body),
:global(#__nuxt),
:global(#__layout) {
  overflow-x: hidden !important;
  max-width: 100% !important;
}

.tv-page,
.tvLayout {
  overflow-x: hidden !important;
  max-width: 100% !important;
}

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

/* GRID: prevent overflow */
.roomsGrid {
  width: 100%;
  max-width: 100%;
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(var(--rooms-cols, 2), minmax(0, 1fr));
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
  min-width: 0;
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

.tvRoomCard {
  width: 100%;
  max-width: var(--card-max-w, 520px);
  height: 100%;
  max-height: var(--card-max-h, 210px);
  overflow: hidden;
  min-width: 0;
}

.clickableCard {
  cursor: pointer;
}

/* ===== MODE 2 ===== */
.roomLane {
  display: grid;
  gap: 10px;
  align-items: center;
  width: 100%;
  min-height: 40px;
  min-width: 0;
}

.roomLane2 {
  grid-template-columns: 1fr auto auto auto auto;
}

.laneName {
  font-weight: 900;
  font-size: clamp(13px, 1.4vh, 18px);
  min-width: 0;
}

.laneIcon {
  font-size: clamp(14px, 1.4vw, 22px) !important;
  line-height: 1;
}

.laneChip {
  height: 20px;
  font-weight: 900;
  padding: 0 8px;
  max-width: 70px;
}

.laneAckBtn {
  min-width: 52px !important;
  height: 22px !important;
  padding: 0 10px !important;
  font-weight: 900;
}

.laneDurationWrap {
  margin-top: 8px;
  display: flex;
  justify-content: center;
}

.laneDuration {
  font-size: clamp(14px, 1.9vh, 26px);
  font-weight: 900;
  line-height: 1.1;
}

.laneStartWrap {
  margin-top: 4px;
  display: flex;
  justify-content: center;
  min-width: 0;
}

.laneStartText {
  font-size: 12px;
  color: #fca5a5;
  max-width: 100%;
  min-width: 0;
}

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

/* ===== MODE 6 ===== */
.miniCard {
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 8px;
  min-width: 0;
}

.miniTop {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.miniName {
  font-weight: 900;
  font-size: 12px;
  flex: 1;
  min-width: 0;
}

.miniChip {
  height: 18px;
  font-weight: 900;
}

.miniIcons {
  display: flex;
  justify-content: space-evenly;
  align-items: center;
  gap: 6px;
}

.miniIcon {
  font-size: clamp(18px, 2.2vw, 34px) !important;
}

.miniHint {
  text-align: center;
}

/* utilities */
.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
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

/* ===== POPUP BOX < 50% SCREEN ===== */
:global(.tvDetailsDialog) {
  width: clamp(320px, 45vw, 720px) !important;
  /* < 50% width */
  max-width: 45vw !important;
  height: clamp(260px, 45vh, 520px) !important;
  /* < 50% height */
  max-height: 45vh !important;
  margin: 0 auto !important;
}

.tvDetailsCard {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.tvDetailsCard .v-card__text {
  overflow-y: auto;
  overflow-x: hidden;
  max-height: 100%;
}

/* ===== MODE 2 ROOM ICONS ===== */
.roomIconsRow {
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
}

/* main icon */
.roomMainIcon {
  font-size: clamp(22px, 3vw, 36px) !important;
  line-height: 1;
}

.roomMainIcon.is-bed {
  color: #3b82f6;
}

/* blue */
.roomMainIcon.is-toilet {
  color: #facc15;
}

/* yellow */

/* PH overlay */
.roomPhIcon {
  font-size: clamp(14px, 2vw, 24px) !important;
  color: #ef4444;
  line-height: 1;
}

/* ===== MODE 6 MINI ICONS ===== */
.miniIcons {
  display: flex;
  justify-content: space-evenly;
  align-items: center;
  gap: 6px;
}

.miniIcon {
  font-size: clamp(18px, 2.6vw, 34px) !important;
  line-height: 1;
}

.miniIcon.is-bed {
  color: #3b82f6;
}

.miniIcon.is-toilet {
  color: #facc15;
}

.miniIcon.is-ph {
  color: #ef4444;
}
</style>
