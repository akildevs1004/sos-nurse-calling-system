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
          <v-btn icon @click="detailsDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text v-if="selectedRoom">
          <div class="popupGrid">
            <!-- LEFT SIDE: ALARM INFO -->
            <div class="popupInfo">
              <div class="d-flex flex-wrap" style="gap:10px">
                <v-chip small :color="selectedRoom.alarm_status ? 'red' : 'grey'">
                  {{ selectedRoom.alarm_status ? "SOS ON" : "SOS OFF" }}
                </v-chip>

                <v-chip small :color="selectedRoom.alarm_status
                  ? (selectedRoom.alarm?.responded_datetime ? '#f97316' : 'red')
                  : 'grey'">
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
                <div class="font-weight-black popupDuration">
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
            </div>

            <!-- RIGHT SIDE: ROOM ICONS -->
            <div class="popupIconsSide">
              <v-icon class="popupMainIcon" :class="isToilet(selectedRoom) ? 'is-toilet' : 'is-bed'">
                {{ isToilet(selectedRoom) ? 'mdi-toilet' : 'mdi-bed' }}
              </v-icon>

              <v-icon v-if="isPh(selectedRoom)" class="popupPhIcon">
                mdi-wheelchair
              </v-icon>
            </div>
          </div>
        </v-card-text>

      </v-card>
    </v-dialog>

    <!-- ===== TOP BAR ===== -->
    <div class="tvTopBar" ref="topBar">
      <div class="tvTopLeft">
        <div class="tvAppIcon">
          <v-icon small>mdi-hospital-box-outline</v-icon>
        </div>
        <div class="tvBrand">
          <div class="tvBrandTitle">Emergency Response System</div>
          <div class="tvBrandSub">DASHBOARD VIEW</div>
        </div>
      </div>

      <div class="tvTopCenter">
        <div class="tvStatusChip">
          <span class="tvStatusDot"></span>
          <span class="tvStatusText">System Online</span>
        </div>
      </div>

      <div class="tvTopRight" v-if="$auth?.user">
        <div class="tvWelcome">
          Welcome,
          <strong>
            {{ $auth.user?.security?.first_name || "" }}
            {{ $auth.user?.security?.last_name || "" }}
          </strong>
        </div>

        <!-- <v-btn icon class="tvIconBtn" @click="reload" aria-label="Reload">
          <v-icon>mdi-refresh</v-icon>
        </v-btn> -->

        <v-btn small outlined color="error" class="tvLogoutBtn" @click="logout">
          <v-icon left small>mdi-logout</v-icon>
          Logout
        </v-btn>
      </div>
    </div>

    <!-- ===== STATS STRIP ===== -->
    <div class="tvStatsStrip" ref="statsStrip">
      <div class="tvStatsGrid">
        <div v-for="s in statCards" :key="s.key" class="tvStatWrap">
          <v-card outlined class="tvStatCard" :class="s.cardClass">
            <div class="tvStatInner">
              <div class="tvStatText">
                <div class="tvStatTitle" :class="s.textClass">{{ s.title }}</div>
                <div class="tvStatValueRow">
                  <div class="tvStatValue">{{ s.value }}</div>
                  <div class="tvStatSub" :class="s.textClass">{{ s.sub }}</div>
                </div>
              </div>
              <v-icon class="tvStatIcon" :class="s.textClass">{{ s.icon }}</v-icon>
            </div>
          </v-card>
        </div>

      </div>
    </div>

    <!-- ===== TOOLBAR ===== -->
    <div class="tvToolbarBar" ref="toolbarBar">
      <div class="tvToolbarLeft">
        <v-btn-toggle v-model="filterMode" mandatory class="tvToggle">
          <v-btn small value="all">ALL</v-btn>
          <v-btn small value="on">SOS ON</v-btn>
          <v-btn small value="off">SOS OFF</v-btn>
        </v-btn-toggle>

        <div class="tvToolbarDivider"></div>

        <div class="tvToolbarLabel">CARDS PER ROW</div>
        <v-btn-toggle v-model="roomsPerRow" mandatory class="tvToggle">
          <v-btn small :value="2">2</v-btn>
          <v-btn small :value="6">6</v-btn>
        </v-btn-toggle>
      </div>

      <div class="tvToolbarRight">
        <v-btn small outlined class="tvNavBtn" @click="prevPage" :disabled="pageIndex === 0">
          <v-icon left small>mdi-chevron-left</v-icon>
          Prev
        </v-btn>

        <div class="tvPageText">
          Page {{ pageIndex + 1 }} / {{ totalPages }}
        </div>

        <v-btn small outlined class="tvNavBtn" @click="nextPage" :disabled="pageIndex >= totalPages - 1">
          Next
          <v-icon right small>mdi-chevron-right</v-icon>
        </v-btn>
      </div>
    </div>


    <!-- <AudioSoundPlay :key="totalSOSCount" v-if="stats?.activeSos > 0" :notificationsMenuItemsCount="stats?.activeSos" /> -->

    <!-- ===== ROOMS AREA ===== -->
    <div class="tvRoomsArea" ref="roomsArea">
      <div class="roomsGrid tvRoomsGrid" ref="roomsGrid" :style="roomsGridStyle">
        <div v-for="d in pagedDevices" :key="d.id || d.room_id" class="roomCell">
          <!-- <v-card outlined class="roomCard tvRoomCard  " @click="onRoomClick(d)" :class="cardClass(d)"> -->

          <!-- <v-card outlined class="roomCard tvRoomCard" :class="[cardClass(d), roomsPerRow === 6 ? 'clickableCard' : '']"
            @click="onRoomClick(d)"> -->
          <!-- TOP ROW -->
          <v-card outlined class="roomCard tvRoomCard" :class="[cardClass(d), roomsPerRow === 6 ? 'clickableCard' : '']"
            @click="onRoomClick(d)">

            <div class="cardTop">
              <div class="dot" :class="d.alarm_status ? 'dotOn' : 'dotOff'"></div>
              <div class="roomName text-truncate">{{ d.name }}</div>

              <div class="icons">
                <v-icon small :color="d.device?.status_id == 1 ? 'green' : 'red'">
                  {{ d.device?.status_id == 1 ? "mdi-wifi" : "mdi-wifi-off" }}
                </v-icon>

                <v-icon small :color="d.alarm_status ? 'red' : 'grey'">
                  {{ d.alarm_status ? "mdi-bell" : "mdi-bell-outline" }}
                </v-icon>

                <v-chip x-small class="pill" v-if="d.alarm_status === true"
                  :color="d.alarm?.responded_datetime ? '#f97316' : 'red'">
                  {{ d.alarm?.responded_datetime ? "ACK" : "PENDING" }}
                </v-chip>

                <v-btn x-small class="ackBtn blink-btn" v-if="d.alarm_status === true && !d.alarm?.responded_datetime"
                  @click.stop="udpateResponse(d.alarm?.id)">
                  ACK
                </v-btn>
              </div>
            </div>
            <!-- ROOM ICONS (ONLY FOR 2-CARDS MODE) -->
            <div class="roomIconsRow" v-if="roomsPerRow === 2" style="margin-top:-10px">
              <!-- MAIN ICON -->
              <v-icon class="roomMainIcon" :class="isToilet(d) ? 'is-toilet' : 'is-bed'">
                {{ isToilet(d) ? 'mdi-toilet' : 'mdi-bed' }}
              </v-icon>

              <!-- PH / DISABLED OVERLAY -->
              <v-icon v-if="isPh(d)" class="roomPhIcon">
                mdi-wheelchair
              </v-icon>
            </div>
            <!-- CENTER -->
            <div class="cardMid">
              <div class="timer mono" v-if="d.alarm_status === true">{{ d.duration || "00:00:00" }}</div>

              <div class="okRow" v-else>
                <v-icon small color="green">mdi-check-circle</v-icon>
                <span class="okText">No Active Call</span>
              </div>

              <!-- alarm_start_datetime -->
              <div class="startPill text-truncate" v-if="d.alarm_status === true">
                Start: {{ d.alarm?.alarm_start_datetime || d.alarm_start_datetime || "-" }}
              </div>
            </div>
          </v-card>
        </div>
        <div v-if="loading">
          Please wait...
        </div>

        <div v-else-if="pagedDevices.length === 0">
          Data is not available
        </div>
      </div>

      <v-progress-linear v-if="loading" indeterminate height="3" class="my-2" />
    </div>
  </div>
</template>

<script>
import mqtt from "mqtt";
import SosAlarmPopupMqtt from "@/components/SOS/SosAlarmPopupMqtt.vue";
import AudioSoundPlay from "../../components/Alarm/AudioSoundPlay.vue";

const ROOMS_PER_ROW_KEY = "tv_rooms_per_row";

export default {
  layout: "tvmonitorlayout",
  name: "TvSosFloor",
  components: { SosAlarmPopupMqtt, AudioSoundPlay },

  data() {
    return {
      dashboardInterval: null,
      detailsDialog: false,
      selectedRoom: null,
      // UI
      filterMode: "all",
      devices: [],
      loading: false,

      snackbar: false,
      snackbarResponse: "",

      // Cards per row selector (2 or 6)
      roomsPerRow: 2,

      // Pagination
      pageIndex: 0,
      tvRowsPerScreen: 1, // ✅ computed from available height

      // Fixed card sizes per your requirement
      CARD_H: 130,
      CARD_W_2: 460,
      CARD_W_6: 140,

      // Gap (vertical + horizontal)
      GAP_2: 16,
      GAP_6: 10,

      // Resize / recalc debounce
      recalcTimer: null,

      // Duration timer
      timer: null,
      TIMER_MS: 1000,

      // MQTT
      mqttUrl: "",
      client: null,
      isConnected: false,
      reqId: "",
      topics: { req: "", rooms: "", stats: "", reload: "" },

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

      filterRoomTableIds: []
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

    // ✅ fixed sizes per mode
    cardW() {
      return Number(this.roomsPerRow) === 2 ? this.CARD_W_2 : this.CARD_W_6;
    },
    cardH() {
      return this.CARD_H;
    },
    gridGap() {
      return Number(this.roomsPerRow) === 2 ? this.GAP_2 : this.GAP_6;
    },

    roomsGridStyle() {
      const cols = Number(this.roomsPerRow) === 2 ? 2 : 6;

      return {
        "--rooms-cols": cols,
        "--card-w": `${this.cardW}px`,
        "--card-h": `${this.cardH}px`,
        "--grid-gap": `${this.gridGap}px`
      };
    },

    filteredDevices() {
      if (this.filterMode === "on") return this.devices.filter((d) => d.alarm_status === true);
      if (this.filterMode === "off") return this.devices.filter((d) => d.alarm_status === false);
      return this.devices;
    },

    // ✅ page size = cols * rows that fit (computed)
    pageSize() {
      const cols = Number(this.roomsPerRow) === 2 ? 2 : 6;
      return cols * Math.max(1, Number(this.tvRowsPerScreen) || 1);
    },
    totalPages() {
      const n = this.filteredDevices?.length || 0;
      return Math.max(1, Math.ceil(n / this.pageSize));
    },
    pagedDevices() {
      const start = this.pageIndex * this.pageSize;
      return (this.filteredDevices || []).slice(start, start + this.pageSize);
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
    // keep pageIndex valid
    totalPages() {
      if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;
    },

    // When mode changes, recalc rows that fit, reset page
    roomsPerRow() {
      this.pageIndex = 0;
      this.safeLsSet(ROOMS_PER_ROW_KEY, String(this.roomsPerRow));
      this.scheduleRecalcRows();
    },

    filterMode() {
      this.pageIndex = 0;
      this.scheduleRecalcRows();
    },

    filteredDevices() {
      if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;
      this.scheduleRecalcRows();
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
    window.addEventListener("resize", this.onResize);

    this.timer = setInterval(() => this.updateDurationAll(), this.TIMER_MS);

    this.mqttUrl = process.env.MQTT_SOCKET_HOST;
    this.connectMqtt();

    // initial compute rows that fit
    this.scheduleRecalcRows();

    this.startDashboardPolling();

    // AndroidBridge.startAlarm();

    // setTimeout(() => {
    //   AndroidBridge.stopAlarm();

    // }, 5000);





  },

  beforeDestroy() {
    window.removeEventListener("resize", this.onResize);
    try {
      if (this.timer) clearInterval(this.timer);
      this.disconnectMqtt();
    } catch (e) { }

    if (this.recalcTimer) clearTimeout(this.recalcTimer);


    this.stopDashboardPolling();

  },

  methods: {
    startDashboardPolling() {
      if (this.dashboardInterval) return; // prevent duplicates

      this.dashboardInterval = setInterval(() => {
        this.requestDashboardSnapshot();
      }, 60 * 1000); // 10 seconds
    },

    stopDashboardPolling() {
      if (this.dashboardInterval) {
        clearInterval(this.dashboardInterval);
        this.dashboardInterval = null;
      }
    },
    isToilet(d) {
      return d?.room_type === "toilet" || d?.room_type === "toilet-ph";
    },
    isPh(d) {
      return d?.room_type === "room-ph" || d?.room_type === "toilet-ph";
    },
    onRoomClick(room) {
      if (Number(this.roomsPerRow) !== 6) return; // ONLY in 6 mode
      this.selectedRoom = room;
      this.detailsDialog = true;
    },
    // ===== pagination =====
    nextPage() {
      if (this.pageIndex < this.totalPages - 1) this.pageIndex++;
    },
    prevPage() {
      if (this.pageIndex > 0) this.pageIndex--;
    },

    // ===== resize / compute rows =====
    onResize() {
      this.scheduleRecalcRows();
    },

    scheduleRecalcRows() {
      if (this.recalcTimer) clearTimeout(this.recalcTimer);
      this.recalcTimer = setTimeout(() => this.recalcRowsPerScreen(), 50);
    },

    // ✅ key logic: fill row-wise, next page only if next row can't fit
    recalcRowsPerScreen() {
      this.$nextTick(() => {
        const area = this.$refs.roomsArea;
        if (!area) return;

        const availableH = area.getBoundingClientRect().height || 0;
        if (availableH <= 0) return;

        const rowH = this.cardH; // fixed 150
        const gap = this.gridGap;

        // rows = floor((available + gap) / (rowH + gap))
        const rows = Math.max(1, Math.floor((availableH + gap) / (rowH + gap)));

        if (rows !== this.tvRowsPerScreen) {
          this.tvRowsPerScreen = rows;

          // keep page valid
          if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;
        }
      });
    },

    // ===== room card class =====
    cardClass(d) {
      if (!d) return "";
      if (d.alarm_status === true && !d.alarm?.responded_datetime) return "cardOn";
      if (d.alarm_status === true && d.alarm?.responded_datetime) return "cardAck";
      return "cardOff";
    },

    // ===== SAFE LOCALSTORAGE =====
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

    // ===== auth / nav =====
    logout() {
      this.$router.push("/logout");
    },
    reload() {
      try { window.location.reload(); } catch (e) { }
    },

    // ===== MQTT =====
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
      this.topics.reloadconfig = `${process.env.MQTT_DEVICE_CLIENTID}/${companyId}/message`;


      this.client = mqtt.connect(this.mqttUrl, {
        reconnectPeriod: 3000,
        keepalive: 30,
        clean: true
      });

      this.client.on("connect", () => {
        this.isConnected = true;
        this.client.subscribe([this.topics.rooms, this.topics.stats, this.topics.reload, this.topics.reloadconfig], { qos: 0 }, (err) => {
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
        if (this.topics.reloadconfig) this.client.unsubscribe(this.topics.reloadconfig);



        this.client.end(true);
        this.client = null;
        this.isConnected = false;
      } catch (e) { }
    },

    requestDashboardSnapshot() {
      if (!this.client || !this.isConnected) return;

      this.loading = true;


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

      this.loading = false;

    },

    onMqttMessage(topic, payload) {

      if (topic !== this.topics.rooms && topic !== this.topics.stats && topic !== this.topics.reload && topic !== this.topics.reloadconfig) return;

      let msg;
      try {
        msg = JSON.parse(payload.toString());
      } catch (e) {
        return;
      }

      if (topic === this.topics.rooms) {
        this.loading = true;
        const data = msg?.data;
        const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
        this.devices = this.normalizeRooms(list);
        this.updateDurationAll();

        // ✅ recalc rows/pages after DOM updates
        this.scheduleRecalcRows();

        this.loading = false;
        return;
      }

      if (topic === this.topics.reload) {
        try { window.location.reload(); } catch (e) { }
        return;
      }
      if (topic === this.topics.reloadconfig) {
        try { this.requestDashboardSnapshot() } catch (e) { }
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

        if (this.activeSosCount > 0) {
          // SOS ON
          try {
            AndroidBridge.startAlarm()
          } catch (error) {

          }

        } else {
          try {
            AndroidBridge.stopAlarm()
          } catch (error) {

          }
        }
      }
    },

    // ===== room normalization + duration =====
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
/* ===== PAGE BASE ===== */
.tv-page {
  height: 100vh;
  overflow: hidden;
  padding: 10px 12px;
  box-sizing: border-box;
  background:
    radial-gradient(900px 500px at 20% 8%, rgba(59, 130, 246, 0.10), transparent 55%),
    radial-gradient(900px 500px at 80% 10%, rgba(168, 85, 247, 0.10), transparent 55%),
    linear-gradient(180deg, #070a14 0%, #0b1220 40%, #0b1220 100%);
}

/* ===== TOP BAR ===== */
.tvTopBar {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: linear-gradient(180deg, rgba(17, 24, 39, 0.78), rgba(17, 24, 39, 0.55));
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.35);
}

.tvTopLeft {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.tvAppIcon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(0, 0, 0, 0.25);
}

.tvBrandTitle {
  font-weight: 900;
  color: #e5e7eb;
  font-size: 14px;
  line-height: 1.1;
}

.tvBrandSub {
  margin-top: 2px;
  font-size: 10px;
  letter-spacing: 1.2px;
  font-weight: 800;
  color: rgba(229, 231, 235, 0.6);
}

.tvTopCenter {
  display: flex;
  justify-content: center;
}

.tvStatusChip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 12px;
  border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(0, 0, 0, 0.18);
}

.tvStatusDot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: #22c55e;
  box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
}

.tvStatusText {
  font-size: 12px;
  font-weight: 800;
  color: rgba(229, 231, 235, 0.9);
}

.tvTopRight {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.tvWelcome {
  font-size: 12px;
  color: rgba(229, 231, 235, 0.7);
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 340px;
}

.tvWelcome strong {
  color: #e5e7eb;
  font-weight: 900;
}

.tvIconBtn {
  border-radius: 10px !important;
  border: 1px solid rgba(148, 163, 184, 0.18) !important;
  background: rgba(0, 0, 0, 0.20) !important;
}

.tvLogoutBtn {
  border-radius: 10px !important;
  font-weight: 900 !important;
}

/* ===== STATS ===== */
.tvStatsStrip {
  margin-top: 10px;
}

.tvStatsGrid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
}

.tvStatCard {
  height: 78px;
  border-radius: 14px !important;
  border: 1px solid rgba(148, 163, 184, 0.18) !important;
  background: linear-gradient(180deg, rgba(17, 24, 39, 0.78), rgba(17, 24, 39, 0.55));
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.28);
}

.tvStatInner {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 12px;
  gap: 10px;
}

.tvStatTitle {
  font-size: 11px;
  letter-spacing: 0.9px;
  font-weight: 900;
  text-transform: uppercase;
  color: rgba(229, 231, 235, 0.75);
  white-space: nowrap;
}

.tvStatValueRow {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-top: 6px;
}

.tvStatValue {
  font-size: 26px;
  font-weight: 900;
  color: #e5e7eb;
  line-height: 1;
}

.tvStatSub {
  font-size: 12px;
  font-weight: 800;
  color: rgba(229, 231, 235, 0.55);
}

.tvStatIcon {
  font-size: 30px !important;
  opacity: 0.95;
}

/* ===== TOOLBAR ===== */
.tvToolbarBar {
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;

  padding: 10px 12px;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: linear-gradient(180deg, rgba(17, 24, 39, 0.78), rgba(17, 24, 39, 0.55));
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.28);
}

.tvToolbarLeft {
  display: flex;
  align-items: center;
  gap: 10px;
}

.tvToolbarRight {
  display: flex;
  align-items: center;
  gap: 10px;
  white-space: nowrap;
}

.tvToolbarDivider {
  width: 1px;
  height: 26px;
  background: rgba(148, 163, 184, 0.22);
  border-radius: 999px;
}

.tvToolbarLabel {
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 1px;
  color: rgba(229, 231, 235, 0.65);
  text-transform: uppercase;
}

.tvToggle {
  border-radius: 12px !important;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.18);
}

.tvToggle .v-btn {
  min-width: 74px;
  font-weight: 900;
  letter-spacing: 0.3px;
}

.tvNavBtn {
  border-radius: 10px !important;
  font-weight: 900 !important;
}

.tvPageText {
  font-size: 12px;
  font-weight: 800;
  color: rgba(229, 231, 235, 0.65);
}

/* ===== ROOMS AREA ===== */
.tvRoomsArea {
  margin-top: 10px;
  height: calc(100vh - 10px - 10px - 58px - 88px - 62px - 30px);
  /* safe fallback */
  flex: 1 1 auto;
  min-height: 0;
  overflow: hidden;
}

/* Grid uses CSS variables from roomsGridStyle */
.roomsGrid {
  width: 100%;
  height: 100%;
  display: grid;
  gap: var(--grid-gap, 16px);
  grid-template-columns: repeat(var(--rooms-cols, 2), var(--card-w, 460px));
  grid-auto-rows: var(--card-h, 150px);
  justify-content: center;
  align-content: start;
  overflow: hidden;
}

/* Cards fixed size */
.roomCell {
  width: var(--card-w, 460px);
  height: var(--card-h, 150px);
}

.roomCard {
  width: var(--card-w, 460px);
  height: var(--card-h, 150px);
  border-radius: 14px !important;
  overflow: hidden;
  position: relative;
  border: 1px solid rgba(148, 163, 184, 0.18) !important;
  background: rgba(17, 24, 39, 0.55) !important;
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.25);
}

/* Top row */
.cardTop {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px 6px 12px;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
}

.dotOn {
  background: #ef4444;
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);
}

.dotOff {
  background: rgba(148, 163, 184, 0.6);
}

.roomName {
  flex: 1;
  min-width: 0;
  font-weight: 900;
  font-size: 13px;
  color: #e5e7eb;
}

.icons {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pill {
  height: 18px;
  border-radius: 999px;
  font-weight: 900;
}

.ackBtn {
  min-width: 46px !important;
  height: 20px !important;
  padding: 0 10px !important;
  border-radius: 999px !important;
  font-weight: 900;
}

/* Center */
.cardMid {
  height: calc(var(--card-h, 150px) - 44px);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 6px;
  padding: 0 12px 10px 12px;
}

.timer {
  font-size: 22px;
  font-weight: 900;
  color: #e5e7eb;
}

.startPill {
  max-width: 100%;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  color: rgba(229, 231, 235, 0.85);
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(0, 0, 0, 0.25);
}

/* OK row */
.okRow {
  display: flex;
  align-items: center;
  gap: 8px;
}

.okText {
  font-weight: 800;
  font-size: 12px;
  color: rgba(229, 231, 235, 0.85);
}

/* ===== Alarm Border ONLY top + left ===== */
.cardOn::before,
.cardAck::before {
  content: "";
  position: absolute !important;
  inset: 0 !important;
  pointer-events: none !important;
  border-top: 2px solid transparent !important;
  border-left: 2px solid transparent !important;
  border-top-left-radius: 14px !important;
}

.cardOn::before {
  border-top-color: rgba(239, 68, 68, 0.95) !important;
  border-left-color: rgba(239, 68, 68, 0.95);
  box-shadow: -8px -8px 22px rgba(239, 68, 68, 0.14) !important;
}

.cardAck::before {
  border-top-color: rgba(249, 115, 22, 0.95);
  border-left-color: rgba(249, 115, 22, 0.95);
  box-shadow: -8px -8px 22px rgba(249, 115, 22, 0.12);
}

.cardOff {}

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

/* Utilities */
.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

/* Snackbar center */
.center-snackbar {
  position: fixed !important;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  z-index: 9999;
}

.clickableCard {
  cursor: pointer;
}

/* Popup size < 50% screen */
:global(.tvDetailsDialog) {
  width: clamp(320px, 45vw, 720px) !important;
  max-width: 45vw !important;
  height: clamp(260px, 45vh, 520px) !important;
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
  margin-top: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* main icon */
.roomMainIcon {
  font-size: 30px !important;
  line-height: 1;
}

.roomMainIcon.is-bed {
  color: #3b82f6;
}

.roomMainIcon.is-toilet {
  color: #facc15;
}

/* PH overlay */
.roomPhIcon {
  font-size: 20px !important;
  color: #ef4444;
  line-height: 1;
}

/* ===== POPUP ICONS ===== */
.popupIconsRow {
  margin-top: 14px;
  margin-bottom: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
}

.popupMainIcon {
  font-size: 54px !important;
  line-height: 1;
}

.popupMainIcon.is-bed {
  color: #3b82f6;
}

.popupMainIcon.is-toilet {
  color: #facc15;
}

.popupPhIcon {
  font-size: 34px !important;
  color: #ef4444;
  line-height: 1;
}

/* Popup two-column layout */
.popupGrid {
  display: grid;
  grid-template-columns: 1fr 140px;
  gap: 16px;
  align-items: center;
}

.popupInfo {
  min-width: 0;
}

/* Right side icons */
.popupIconsSide {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 10px;
}

/* icon styles */
.popupMainIcon {
  font-size: 64px !important;
  line-height: 1;
}

.popupMainIcon.is-bed {
  color: #3b82f6;
}

.popupMainIcon.is-toilet {
  color: #facc15;
}

.popupPhIcon {
  font-size: 34px !important;
  color: #ef4444;
  line-height: 1;
}

.popupDuration {
  font-size: 28px;
}

.clickableCard {
  cursor: pointer;
  user-select: none;
}

.clickableCard:hover {
  transform: translateY(-1px);
}

.roomCard {
  position: relative;
  /* required for ::before */
  overflow: hidden;
}

/* alarm border top+left */
.cardOn::before,
.cardAck::before {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 2;
  /* ensures it stays visible */
  border-top: 2px solid transparent;
  border-left: 2px solid transparent;
  border-top-left-radius: 14px;
}

.cardOn::before {
  border-top-color: rgba(239, 68, 68, 0.95);
  border-left-color: rgba(239, 68, 68, 0.95);
}

.cardAck::before {
  border-top-color: rgba(249, 115, 22, 0.95);
  border-left-color: rgba(249, 115, 22, 0.95);
}

/* ===== FIX VUETIFY v-card--link BORDER ISSUE ===== */
.roomCard.v-card--link {
  cursor: pointer;
  text-decoration: none;
  box-shadow: none !important;
  outline: none !important;
}

/* prevent hover overlay from hiding alarm border */
.roomCard.v-card--link::after {
  display: none !important;
}

.roomCard {
  position: relative;
  overflow: hidden;
}

/* alarm border top + left */
.cardOn::before,
.cardAck::before {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 5;
  /* ABOVE v-card--link overlay */
  border-top: 2px solid transparent;
  border-left: 2px solid transparent;
  border-top-left-radius: 14px;
}

.cardOn::before {
  border-top-color: rgba(239, 68, 68, 0.95);
  border-left-color: rgba(239, 68, 68, 0.95);
}

.cardAck::before {
  border-top-color: rgba(249, 115, 22, 0.95);
  border-left-color: rgba(249, 115, 22, 0.95);
}

/* ---------- IMPORTANT: Make sure roomCard is a stacking context ---------- */
.roomCard {
  position: relative !important;
  overflow: hidden !important;
}

/* ---------- Vuetify clickable overlay/ripple can hide your border ---------- */
/* Use :deep() so it actually applies under <style scoped> */
:deep(.roomCard.v-card--link) {
  box-shadow: none !important;
  outline: none !important;
  text-decoration: none !important;
}

/* Remove/neutralize Vuetify overlay + underlay + ripple layers */
:deep(.roomCard .v-card__overlay),
:deep(.roomCard .v-card__underlay),
:deep(.roomCard .v-ripple__container) {
  display: none !important;
  /* simplest, strongest */
}

/* ---------- Your alarm border (TOP + LEFT only) ALWAYS visible ---------- */
.roomCard.cardOn::before,
.roomCard.cardAck::before {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 9999;
  /* above anything */
  border-top: 3px solid transparent;
  border-left: 3px solid transparent;
  border-top-left-radius: 14px;
  /* match your card radius */
}

.roomCard.cardOn::before {
  border-top-color: rgba(239, 68, 68, 0.95);
  border-left-color: rgba(239, 68, 68, 0.95);
}

.roomCard.cardAck::before {
  border-top-color: rgba(249, 115, 22, 0.95);
  border-left-color: rgba(249, 115, 22, 0.95);
}

/* Optional: click cursor + hover without touching borders */
.clickableCard,
:deep(.roomCard.v-card--link) {
  cursor: pointer;
}

:deep(.roomCard.v-card--link:hover) {
  transform: translateY(-1px);
}

:deep(.roomCard.v-card--link) {
  cursor: pointer;
  text-decoration: none !important;
}

/* Remove overlay/ripple layers that can sit above visuals */
:deep(.roomCard .v-card__overlay),
:deep(.roomCard .v-card__underlay),
:deep(.roomCard .v-ripple__container) {
  display: none !important;
}

/* Base card */
.roomCard {
  position: relative !important;
  overflow: hidden !important;
  border-radius: 14px !important;
  /* match your design */
}

/* ===== TOP + LEFT BORDER USING BACKGROUND (bulletproof) ===== */
.roomCard.cardOn,
.roomCard.cardAck {
  /* Keep existing outline border from Vuetify if you want */
  /* background border lines */
  background-repeat: no-repeat;
  background-position: left top, left top;
  background-size: 100% 3px, 3px 100%;
  /* top thickness, left thickness */
}

/* RED (SOS ON - pending) */
.roomCard.cardOn {
  background-image:
    linear-gradient(rgba(239, 68, 68, 0.95), rgba(239, 68, 68, 0.95)),
    linear-gradient(rgba(239, 68, 68, 0.95), rgba(239, 68, 68, 0.95));
}

/* ORANGE (ACK) */
.roomCard.cardAck {
  background-image:
    linear-gradient(rgba(249, 115, 22, 0.95), rgba(249, 115, 22, 0.95)),
    linear-gradient(rgba(249, 115, 22, 0.95), rgba(249, 115, 22, 0.95));
}


/* Ensure card is the visual container */
.roomCard {
  position: relative !important;
  overflow: hidden !important;
  border-radius: 14px !important;
}

/* TOP + LEFT "BORDER" using inset shadows (works with v-card--link) */
.roomCard.cardOn {
  box-shadow:
    inset 0 3px 0 rgba(239, 68, 68, 0.95),
    inset 2px 0 0 rgba(239, 68, 68, 0.95) !important;
}

.roomCard.cardAck {
  box-shadow:
    inset 0 3px 0 rgba(249, 115, 22, 0.95),
    inset 2px 0 0 rgba(249, 115, 22, 0.95) !important;
}

/* Keep clickable behavior, but don’t let Vuetify add decoration */
:deep(.roomCard.v-card--link) {
  cursor: pointer;
  text-decoration: none !important;
}
</style>
