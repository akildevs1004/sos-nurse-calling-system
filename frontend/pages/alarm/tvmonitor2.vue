<template>
  <div class="dashShell">
    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" elevation="24" class="center-snackbar">
      {{ snackbarResponse }}
    </v-snackbar>

    <SosAlarmPopupMqtt @triggerUpdateDashboard="requestDashboardSnapshot()" />

    <!-- WEB sound only (NOT TV). TV uses AndroidBridge -->
    <AudioSoundPlay v-if="useWebSound && !isMuted && stats?.activeSos > 0" :key="totalSOSCount"
      :notificationsMenuItemsCount="stats?.activeSos" />

    <!-- DETAILS POPUP (optional) -->
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

    <!-- LEFT FIXED NAV (icon-only, expands on hover) -->
    <aside class="leftNav" :class="{ expanded: navExpanded }" @mouseenter="navExpanded = true"
      @mouseleave="navExpanded = false">
      <div class="navTop">
        <div class="navLogo">
          <img src="/logo.png" alt="XtremeGuard" />
        </div>
      </div>

      <div class="navList">
        <!-- Split -->
        <div class="navItem" :class="{ active: splitMode === 4 }" @click="setSplit(4)">
          <v-icon>mdi-view-grid</v-icon>
          <span class="navLabel">4-way</span>
        </div>
        <div class="navItem" :class="{ active: splitMode === 8 }" @click="setSplit(8)">
          <v-icon>mdi-view-grid-plus</v-icon>
          <span class="navLabel">8-way</span>
        </div>
        <div class="navItem" :class="{ active: splitMode === 16 }" @click="setSplit(16)">
          <v-icon>mdi-view-module</v-icon>
          <span class="navLabel">16-way</span>
        </div>
        <div class="navItem" :class="{ active: splitMode === 32 }" @click="setSplit(32)">
          <v-icon>mdi-view-comfy</v-icon>
          <span class="navLabel">32-way</span>
        </div>
        <div class="navItem" :class="{ active: splitMode === 64 }" @click="setSplit(64)">
          <v-icon>mdi-view-dashboard</v-icon>
          <span class="navLabel">64-way</span>
        </div>

        <div class="navDivider"></div>

        <!-- Filter -->
        <div class="navItem" :class="{ active: filterMode === 'all' }" @click="setFilter('all')">
          <v-icon>mdi-format-list-bulleted</v-icon>
          <span class="navLabel">All</span>
        </div>
        <div class="navItem" :class="{ active: filterMode === 'on' }" @click="setFilter('on')">
          <v-icon>mdi-bell</v-icon>
          <span class="navLabel">SOS ON</span>
        </div>
        <div class="navItem" :class="{ active: filterMode === 'off' }" @click="setFilter('off')">
          <v-icon>mdi-bell-off</v-icon>
          <span class="navLabel">SOS OFF</span>
        </div>

        <div class="navDivider"></div>

        <!-- TV-only controls -->
        <template v-if="isTV">
          <div class="navItem" :class="{ disabled: totalPages <= 1 }" @click="totalPages > 1 && prevPage()">
            <v-icon>mdi-chevron-left</v-icon>
            <span class="navLabel">Prev Page</span>
          </div>

          <div class="navItem" :class="{ disabled: totalPages <= 1 }" @click="totalPages > 1 && nextPage()">
            <v-icon>mdi-chevron-right</v-icon>
            <span class="navLabel">Next Page</span>
          </div>

          <div class="navDivider"></div>

          <div class="navItem danger" @click="logout" v-if="$auth?.user">
            <v-icon>mdi-logout</v-icon>
            <span class="navLabel">Logout</span>
          </div>
        </template>
      </div>
    </aside>

    <!-- Notifications Drawer: fixed on right ONLY when alarms > 0 -->
    <aside class="notifDrawer" v-if="drawerVisible" @click.stop>
      <div class="notifHeader">
        <div class="notifHeaderTitle">
          NOTIFICATION QUEUE FOR ALERTS ({{ activeAlarmCount }})
        </div>

        <div class="notifHeaderActions">
          <v-btn x-small outlined class="notifMuteBtn" @click="toggleMute">
            <v-icon left small>
              {{ isMuted ? 'mdi-volume-off' : 'mdi-volume-high' }}
            </v-icon>
            {{ isMuted ? 'Muted' : 'Sound On' }}
          </v-btn>
        </div>
      </div>

      <div class="notifBody">
        <div v-if="activeAlarmRooms.length === 0" class="notifEmpty">
          No active alarms
        </div>

        <div v-for="d in activeAlarmRooms" :key="d.id || d.room_id" class="notifCard" :class="notifKind(d)">
          <div class="notifTopRow">
            <div class="notifTitle">
              {{ d.name }} - {{ d.alarm?.responded_datetime ? 'ACKNOWLEDGED' : 'PENDING' }}
            </div>

            <div class="notifPill" :class="d.alarm?.responded_datetime ? 'pillAck' : 'pillNew'">
              {{ d.alarm?.responded_datetime ? 'ACK' : 'ALARM' }}
            </div>
          </div>

          <div class="notifSub2">
            Active Time: {{ getRoomActiveTime(d) }}
          </div>

          <div class="notifSub2">
            Start: {{ d.alarm?.alarm_start_datetime || d.alarm_start_datetime || '-' }}
          </div>

          <div class="notifSub2" v-if="d.alarm?.responded_datetime">
            Ack At: {{ d.alarm?.responded_datetime }}
          </div>

          <div class="notifActions" v-if="!d.alarm?.responded_datetime">
            <v-btn x-small class="notifBtn warn" @click="udpateResponse(d.alarm?.id)">
              Submit Acknowledgement
            </v-btn>
          </div>
        </div>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="dashMain">
      <!-- HEADER (hide if width < 1000) -->
      <header class="dashHeader" v-if="showHeader">
        <div class="hdLeft">
          <div class="hdLogo">
            <v-avatar size="35" style="border: 1px solid rgba(255,255,255,0.25)">
              <v-img class="company_logo" :src="getLogo"></v-img>
            </v-avatar>
          </div>

          <div class="hdTitleBlock">
            <div class="hdSub">
              {{ $auth?.user?.company?.name || "Company" }}
            </div>
          </div>
        </div>

        <div class="hdRight" style="font-size:13px;font-weight: normal;">
          <div class="hdMeta">
            <v-icon small class="mr-1">mdi-clock-outline</v-icon> {{ headerTime }}
          </div>

          <div class="hdMeta">
            <v-icon small class="mr-1">mdi-calendar-month-outline</v-icon> {{ headerDate }}
          </div>

          <div class="hdMeta">
            <span class="hdStatusDot" :class="isConnected ? 'ok' : 'bad'"></span>
            <span class="hdStatusText">{{ isConnected ? "Online" : "Offline" }}</span>
          </div>

          <!-- WEB pagination only -->
          <template v-if="!isTV">
            <v-btn x-small outlined class="hdBtn" @click="prevPage" :disabled="totalPages <= 1">
              <v-icon left small>mdi-chevron-left</v-icon>
            </v-btn>

            <div class="hdPageText">{{ pageIndex + 1 }} / {{ totalPages }}</div>

            <v-btn x-small outlined class="hdBtn" @click="nextPage" :disabled="totalPages <= 1">
              <v-icon right small>mdi-chevron-right</v-icon>
            </v-btn>

            <v-btn small outlined color="error" class="hdBtn" v-if="$auth?.user" @click="logout">
              <v-icon left small>mdi-logout</v-icon> Logout
            </v-btn>
          </template>

          <v-btn icon class="hdBellBtn" :class="{ blinkBell: unreadCount > 0 }" @click="toggleNotifications">
            <v-badge :content="unreadCount" :value="unreadCount > 0" overlap>
              <v-icon>mdi-bell-outline</v-icon>
            </v-badge>
          </v-btn>
        </div>
      </header>

      <!-- CARDS (FULL SCREEN HEIGHT FIT BY ROW COUNT) -->
      <section class="dashCards" ref="dashCards" :style="contentPadStyle">
        <div class="cardsGrid" :style="roomsGridStyle">
          <div v-for="d in pagedDevices" :key="d.id || d.room_id" class="cardCell">
            <v-card outlined class="roomCard" :class="cardClass(d)" @click="onRoomClick(d)">
              <div class="cardTop">
                <div class="cardTitle">
                  <v-icon small class="mr-1">
                    {{ isToilet(d) ? "mdi-toilet" : "mdi-bed" }}
                  </v-icon>
                  <span class="text-truncate">{{ d.name }}</span>
                </div>

                <v-icon small :class="d.device?.status_id == 1 ? 'wifiOk' : 'wifiOff'">
                  {{ d.device?.status_id == 1 ? "mdi-wifi" : "mdi-wifi-off" }}
                </v-icon>
              </div>

              <div class="cardMid">
                <div class="statusCircle" :class="d.alarm_status ? 'isAlarm danger fill dark' : 'isOk'">
                  <v-icon class="statusIcon" :class="d.alarm_status ? 'isAlarm' : 'isOk'">
                    {{ d.alarm_status ? "mdi-close-circle" : "mdi-check-circle-outline" }}
                  </v-icon>
                </div>
              </div>

              <div class="cardBottom" :class="{
                bottomAlarm: d.alarm_status && !d.alarm?.responded_datetime,
                bottomAck: d.alarm_status && d.alarm?.responded_datetime,
                bottomOk: !d.alarm_status
              }">
                <v-icon x-small class="mr-1">
                  {{
                    d.alarm_status
                      ? (d.alarm?.responded_datetime ? "mdi-bell-check" : "mdi-bell-ring")
                      : "mdi-bell-off"
                  }}
                </v-icon>

                <span class="bottomText">
                  <template v-if="d.alarm_status">
                    <template v-if="d.alarm?.responded_datetime">
                      Acknowledged
                    </template>
                    <template v-else>
                      Active: {{ (d.duration || "00:00:00").slice(3) }}
                    </template>
                  </template>

                  <template v-else>
                    No Active Alarm
                  </template>
                </span>
              </div>
            </v-card>
          </div>
        </div>

        <v-progress-linear v-if="loading" indeterminate height="3" class="mt-3" />
        <div v-else-if="pagedDevices.length === 0" class="emptyState">Data is not available</div>
      </section>
    </main>
  </div>
</template>

<script>
import mqtt from "mqtt";
import SosAlarmPopupMqtt from "@/components/SOS/SosAlarmPopupMqtt.vue";
import AudioSoundPlay from "../../components/Alarm/AudioSoundPlay.vue";

const SPLIT_MODE_KEY = "dash_split_mode";
const AUTO_ROTATE_KEY = "dash_auto_rotate_ms";

export default {
  layout: "tvmonitorlayout",
  name: "TvSosFloor",
  components: { SosAlarmPopupMqtt, AudioSoundPlay },

  data() {
    return {
      detailsDialog: false,
      selectedRoom: null,

      filterMode: "all",
      devices: [],
      loading: false,

      snackbar: false,
      snackbarResponse: "",

      navExpanded: false,
      isMuted: false,

      splitMode: 16,
      autoRotateMs: 1000 * 5,
      autoRotateTimer: null,

      timer: null,
      TIMER_MS: 1000,

      mqttUrl: "",
      client: null,
      isConnected: false,
      reqId: "",
      topics: { req: "", rooms: "", stats: "", reload: "", reloadconfig: "" },

      avgResponseText: "00:00",
      avgResponsePct: 100,
      repeated: 0,
      ackCount: 0,
      totalSOSCount: 0,
      activeDisabledSos: 0,

      pageIndex: 0,

      room: null,
      status: null,
      date_from: "",
      date_to: "",

      headerTime: "",
      headerDate: "",
      _clock: null,

      windowW: 0,
      dashboardInterval: null,

      _cardHpx: 160,
      _bellClicked: false
    };
  },

  computed: {
    isTV() {
      const w = typeof window !== "undefined" ? (this.windowW || window.innerWidth || 0) : 0;
      return w === 0 || (w >= 500 && w < 1000);
    },

    showHeader() {
      const w = typeof window !== "undefined" ? (this.windowW || window.innerWidth || 0) : 0;
      return w >= 1000;
    },

    isAndroidTV() {
      return this.isTV;
    },

    useWebSound() {
      return !this.isTV;
    },

    contentPadStyle() {
      return {
        paddingLeft: "76px",
        paddingRight: this.drawerVisible ? "200px" : "12px"
      };
    },

    getLogo() {
      let logosrc = "/no-image.PNG";
      if (this.$auth?.user?.company?.logo) logosrc = this.$auth.user.company.logo;
      return logosrc;
    },

    filteredDevices() {
      if (this.filterMode === "on") return this.devices.filter((d) => d.alarm_status === true);
      if (this.filterMode === "off") return this.devices.filter((d) => d.alarm_status === false);
      return this.devices;
    },

    pageSize() {
      const s = Number(this.splitMode);
      return [4, 8, 16, 32, 64].includes(s) ? s : 16;
    },

    totalPages() {
      const n = this.filteredDevices?.length || 0;
      return Math.max(1, Math.ceil(n / this.pageSize));
    },

    pagedDevices() {
      const start = this.pageIndex * this.pageSize;
      return (this.filteredDevices || []).slice(start, start + this.pageSize);
    },

    splitCols() {
      const s = Number(this.splitMode);
      if (s === 4) return 2;
      if (s === 8) return 4;
      if (s === 16) return 4;
      if (s === 32) return 8;
      if (s === 64) return 8;
      return 4;
    },

    splitRows() {
      const s = Number(this.splitMode);
      if (s === 4) return 2;
      if (s === 8) return 2;
      if (s === 16) return 4;
      if (s === 32) return 4;
      if (s === 64) return 8;
      return 4;
    },

    roomsGridStyle() {
      return {
        gridTemplateColumns: `repeat(${this.splitCols}, minmax(0, 1fr))`,
        gridTemplateRows: `repeat(${this.splitRows}, minmax(0, 1fr))`
      };
    },

    activeAlarmCount() {
      return (this.devices || []).filter(d => d && d.alarm_status === true).length;
    },

    drawerVisible() {
      return this.activeAlarmCount > 0;
    },

    activeAlarmRooms() {
      return (this.devices || []).filter(d => d && d.alarm_status === true);
    },

    unreadCount() {
      // blink until click
      if (!this.drawerVisible) return 0;
      return this._bellClicked ? 0 : this.activeAlarmCount;
    },

    cardHpx() {
      return this._cardHpx || 160;
    },

    stats() {
      return {
        totalPoints: this.devices.length,
        activeSos: this.activeAlarmCount,
        repeated: this.repeated,
        ackCount: this.ackCount,
        totalSOSCount: this.totalSOSCount,
        activeDisabledSos: this.activeDisabledSos
      };
    }
  },

  watch: {
    totalPages() {
      if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;
    },

    splitMode() {
      this.pageIndex = 0;
      this.safeLsSet(SPLIT_MODE_KEY, String(this.splitMode));
      this.restartAutoRotate();
      this.$nextTick(() => this.recalcCardHeightFull());
    },

    filterMode() {
      this.pageIndex = 0;
      this.$nextTick(() => this.recalcCardHeightFull());
    },

    drawerVisible() {
      this.$nextTick(() => this.recalcCardHeightFull());
      this.applySoundPolicy();
      if (!this.drawerVisible) this._bellClicked = false;
    }
  },

  created() {
    this.$vuetify.theme.dark = true;

    const savedSplit = Number(this.safeLsGet(SPLIT_MODE_KEY));
    if ([4, 8, 16, 32, 64].includes(savedSplit)) this.splitMode = savedSplit;

    const savedRotate = Number(this.safeLsGet(AUTO_ROTATE_KEY));
    if (Number.isFinite(savedRotate) && savedRotate >= 5000) this.autoRotateMs = savedRotate;
  },

  mounted() {
    this.windowW = typeof window !== "undefined" ? (window.innerWidth || 0) : 0;
    window.addEventListener("resize", this.onResizeAll);

    this.timer = setInterval(() => this.updateDurationAll(), this.TIMER_MS);

    this.updateHeaderClock();
    this._clock = setInterval(() => this.updateHeaderClock(), 1000);

    this.mqttUrl = process.env.MQTT_SOCKET_HOST;
    this.connectMqtt();

    this.startDashboardPolling();
    this.startAutoRotate();

    this.$nextTick(() => this.recalcCardHeightFull());
  },

  beforeDestroy() {
    window.removeEventListener("resize", this.onResizeAll);

    try {
      if (this.timer) clearInterval(this.timer);
      if (this._clock) clearInterval(this._clock);
      this.stopAutoRotate();
      this.disconnectMqtt();
    } catch (e) { }

    this.stopDashboardPolling();
  },

  methods: {
    onResizeAll() {
      this.windowW = typeof window !== "undefined" ? (window.innerWidth || 0) : 0;
      try { AndroidBridge.stopAlarm(); } catch (e) { }
      this.$nextTick(() => this.recalcCardHeightFull());
    },

    // FULL SCREEN CARD HEIGHT according to COUNT (split rows):
    // Uses dashCards container height; since footer removed, it truly fills remaining viewport.
    recalcCardHeightFull() {
      this.$nextTick(() => {
        const area = this.$refs.dashCards;
        if (!area) return;

        const availableH = area.getBoundingClientRect().height || 0;
        if (availableH <= 0) return;

        const rows = Math.max(1, Number(this.splitRows) || 1);
        const gap = 12; // must match .cardsGrid gap
        const totalGaps = (rows - 1) * gap;

        // No extra padding: make it "CCTV full window"
        const h = Math.floor((availableH - totalGaps) / rows);
        this._cardHpx = Math.max(90, h);
      });
    },

    toggleNotifications() {
      this._bellClicked = true;
    },

    setSplit(n) {
      this.splitMode = n;
    },

    setFilter(v) {
      this.filterMode = v;
    },

    nextPageLoop() {
      if (this.totalPages <= 1) return;
      this.pageIndex = (this.pageIndex + 1) % this.totalPages;
    },

    nextPage() {
      if (this.totalPages <= 1) return;
      this.pageIndex = (this.pageIndex + 1) % this.totalPages;
      this.restartAutoRotate();
    },

    prevPage() {
      if (this.totalPages <= 1) return;
      this.pageIndex = (this.pageIndex - 1 + this.totalPages) % this.totalPages;
      this.restartAutoRotate();
    },

    startAutoRotate() {
      this.stopAutoRotate();
      this.autoRotateTimer = setInterval(() => {
        this.nextPageLoop();
      }, this.autoRotateMs);
    },

    stopAutoRotate() {
      if (this.autoRotateTimer) {
        clearInterval(this.autoRotateTimer);
        this.autoRotateTimer = null;
      }
    },

    restartAutoRotate() {
      this.startAutoRotate();
    },

    toggleMute() {
      this.isMuted = !this.isMuted;
      this.applySoundPolicy(true);
    },

    applySoundPolicy(forceStop = false) {
      const active = (this.stats?.activeSos || 0) > 0;

      if (forceStop || this.isMuted || !active) {
        if (this.isAndroidTV) {
          try { AndroidBridge.stopAlarm(); } catch (e) { }
        }
        return;
      }

      if (this.isAndroidTV) {
        try { AndroidBridge.startAlarm(); } catch (e) { }
      }
    },

    isToilet(d) {
      return d?.room_type === "toilet" || d?.room_type === "toilet-ph";
    },
    isPh(d) {
      return d?.room_type === "room-ph" || d?.room_type === "toilet-ph";
    },

    onRoomClick(room) {
      if (![32, 64].includes(Number(this.splitMode))) return;
      this.selectedRoom = room;
      this.detailsDialog = true;
    },

    cardClass(d) {
      if (!d) return "";
      if (d.alarm_status === true && !d.alarm?.responded_datetime) return "cardOn";
      if (d.alarm_status === true && d.alarm?.responded_datetime) return "cardAck";
      return "cardOff";
    },

    notifKind(d) {
      if (!d || d.alarm_status !== true) return "stop";
      if (d.alarm?.responded_datetime) return "ack";
      return "new";
    },

    getRoomActiveTime(room) {
      if (!room) return "--:--";
      if (room.alarm_status !== true) return "--:--";
      if (room.duration) return room.duration.slice(3);
      if (room.startMs) {
        const diffSec = Math.max(0, Math.floor((Date.now() - room.startMs) / 1000));
        return this.formatMMSSorHHMMSS(diffSec);
      }
      return "--:--";
    },

    formatMMSSorHHMMSS(totalSeconds) {
      const hh = Math.floor(totalSeconds / 3600);
      const mm = Math.floor((totalSeconds % 3600) / 60);
      const ss = totalSeconds % 60;
      if (hh > 0) return `${String(hh).padStart(2, "0")}:${String(mm).padStart(2, "0")}:${String(ss).padStart(2, "0")}`;
      return `${String(mm).padStart(2, "0")}:${String(ss).padStart(2, "0")}`;
    },

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

    logout() {
      this.$router.push("/logout");
    },

    startDashboardPolling() {
      if (this.dashboardInterval) return;
      this.dashboardInterval = setInterval(() => {
        this.requestDashboardSnapshot();
      }, 60 * 1000);
    },

    stopDashboardPolling() {
      if (this.dashboardInterval) {
        clearInterval(this.dashboardInterval);
        this.dashboardInterval = null;
      }
    },

    updateHeaderClock() {
      const d = new Date();
      this.headerTime = d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
      this.headerDate = d.toISOString().slice(0, 10);
    },

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
        this.client.subscribe(
          [this.topics.rooms, this.topics.stats, this.topics.reload, this.topics.reloadconfig],
          { qos: 0 },
          (err) => {
            if (err) return;
            this.requestDashboardSnapshot();
          }
        );
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
      if (
        topic !== this.topics.rooms &&
        topic !== this.topics.stats &&
        topic !== this.topics.reload &&
        topic !== this.topics.reloadconfig
      ) return;

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

        if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;

        this.loading = false;
        this.$nextTick(() => this.recalcCardHeightFull());
        return;
      }

      if (topic === this.topics.reload) {
        try { window.location.reload(); } catch (e) { }
        return;
      }

      if (topic === this.topics.reloadconfig) {
        try { this.requestDashboardSnapshot(); } catch (e) { }
        return;
      }

      if (topic === this.topics.stats) {
        const s = msg?.data || {};
        this.repeated = s.repeated || 0;
        this.ackCount = s.ackCount || 0;
        this.totalSOSCount = s.totalSOSCount || 0;
        this.activeDisabledSos = s.activeDisabledSos || 0;

        this.applySoundPolicy(false);
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

      try { alert("Acknowledgement is Received.."); } catch (e) { }
    }
  }
};
</script>

<style scoped>
/* ===== shell ===== */
.dashShell {
  width: 100%;
  height: 100vh;
  overflow: hidden;
}

/* Use flex instead of grid here (Android TV WebView behaves better) */
.dashMain {
  height: 100vh;
  width: 100%;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

/* ===== left nav ===== */
.leftNav {
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  width: 64px;
  z-index: 90;
  background: rgba(12, 16, 25, 0.96);
  border-right: 1px solid rgba(255, 255, 255, 0.10);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
  display: flex;
  flex-direction: column;
  transition: width 160ms ease;
}

.leftNav.expanded {
  width: 220px;
}

.navTop {
  padding: 12px 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  justify-content: center;
}

.leftNav.expanded .navTop {
  justify-content: flex-start;
}

.navLogo {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.10);
  display: grid;
  place-items: center;
  overflow: hidden;
}

.navLogo img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 6px;
}

.navList {
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  overflow: auto;
}

.navItem {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  border-radius: 12px;
  cursor: pointer;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.07);
  opacity: 0.92;
}

.navItem:hover {
  background: rgba(255, 255, 255, 0.08);
}

.navItem.active {
  background: rgba(99, 102, 241, 0.25);
  border-color: rgba(99, 102, 241, 0.35);
}

.navItem.danger {
  color: #ef4444;
}

.navItem.disabled {
  opacity: 0.35;
  pointer-events: none;
}

.navLabel {
  white-space: nowrap;
  opacity: 0;
  width: 0;
  overflow: hidden;
  transition: opacity 160ms ease, width 160ms ease;
}

.leftNav.expanded .navLabel {
  opacity: 1;
  width: auto;
}

.navDivider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
  margin: 6px 2px;
}

/* ===== header ===== */
.dashHeader {
  padding: 12px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.14);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.hdLeft {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.hdLogo {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  font-weight: 900;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.hdTitleBlock {
  min-width: 0;
}

.hdSub {
  margin-top: 2px;
  font-size: 12px;
  opacity: 0.75;
}

.hdRight {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.hdMeta {
  opacity: 0.9;
  display: flex;
  align-items: center;
}

.hdMeta .v-icon {
  color: rgba(99, 102, 241, 0.95) !important;
  opacity: 0.95;
}

.hdRight .hdMeta:nth-child(2) .v-icon {
  color: rgba(34, 197, 94, 0.95) !important;
}

.hdBtn {
  border-color: rgba(255, 255, 255, 0.25) !important;
}

.hdBtn.v-btn--disabled {
  opacity: 0.45 !important;
  cursor: not-allowed !important;
}

.hdPageText {
  min-width: 30px;
  text-align: center;
  font-weight: 900;
  opacity: 0.85;
}

.hdStatusDot {
  width: 8px;
  height: 8px;
  border-radius: 99px;
  display: inline-block;
  margin-right: 6px;
}

.hdStatusDot.ok {
  background: #22c55e;
}

.hdStatusDot.bad {
  background: #ef4444;
}

.hdStatusText {
  font-weight: 900;
}

.hdBellBtn {
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.blinkBell {
  animation: bellBlink 1s infinite;
}

@keyframes bellBlink {

  0%,
  100% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.08);
  }
}

/* ===== cards: FULL SCREEN ===== */
/* IMPORTANT: remove vertical padding so grid can consume full height */
/* Content takes ALL remaining height */
.dashCards {
  flex: 1 1 auto;
  min-height: 0;
  /* critical for 1fr grid rows */
  height: auto;
  overflow: hidden;
  padding: 0;
  /* no padding so cards can fill */
}

/* Grid fills the entire content area */
.cardsGrid {
  width: 100%;
  height: 100%;
  min-height: 0;
  /* critical */
  display: grid;
  gap: 12px;
  align-content: stretch;
}

/* Grid items must be shrinkable */
.cardCell {
  min-height: 0;
}

/* Card fills the grid cell */
.roomCard {
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.cardOn {
  border-color: rgba(239, 68, 68, 0.28);
  background: linear-gradient(180deg, rgba(239, 68, 68, 0.20), rgba(24, 33, 48, 0.72));
}

.cardAck {
  border-color: rgba(249, 115, 22, 0.28);
  background: linear-gradient(180deg, rgba(249, 115, 22, 0.18), rgba(24, 33, 48, 0.72));
}

.cardTop {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  gap: 10px;
}

.cardTitle {
  display: flex;
  align-items: center;
  min-width: 0;
  font-weight: 900;
  letter-spacing: 0.3px;
}

.wifiOk {
  color: #22c55e;
}

.wifiOff {
  color: #ef4444;
}

.cardMid {
  flex: 1;
  display: grid;
  place-items: center;
  padding: 10px;
}

.statusCircle {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  transition: all 0.25s ease;
}

.statusIcon.isOk {
  color: #22c55e;
  font-size: 42px !important;
}

.statusCircle.isAlarm {
  background: rgba(239, 68, 68, 0.22);
  border-color: rgba(239, 68, 68, 0.65);
  animation: alarmPulse 1.2s infinite;
}

.statusIcon.isAlarm {
  color: #ef4444;
  font-size: 42px !important;
}

.statusCircle.danger {
  box-shadow: 0 0 18px rgba(239, 68, 68, 0.55);
}

@keyframes alarmPulse {
  0% {
    transform: scale(1);
    box-shadow: 0 0 0 rgba(239, 68, 68, 0.0);
  }

  50% {
    transform: scale(1.08);
    box-shadow: 0 0 22px rgba(239, 68, 68, 0.6);
  }

  100% {
    transform: scale(1);
    box-shadow: 0 0 0 rgba(239, 68, 68, 0.0);
  }
}

.cardBottom {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 10px;
  font-weight: 900;
}

.bottomOk {
  background: rgba(34, 197, 94, 0.9);
  color: rgba(255, 255, 255, 0.92);
  font-weight: normal;
}

.bottomAlarm {
  background: rgba(239, 68, 68, 0.85);
  color: rgba(255, 255, 255, 0.92);
}

.bottomAck {
  background: rgba(161, 98, 7, 0.9);
  color: #fff;
}

.bottomText {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 14px;
}

/* ===== notifications drawer ===== */
.notifDrawer {
  position: fixed;
  top: 0;
  right: 0;
  height: 100vh;
  width: 200px;
  max-width: 200px;
  z-index: 80;
  background: rgba(12, 16, 25, 0.96);
  border-left: 1px solid rgba(255, 255, 255, 0.10);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
  display: flex;
  flex-direction: column;
}

.notifHeader {
  padding: 14px 10px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.notifHeaderTitle {
  font-weight: 900;
  letter-spacing: 0.6px;
  font-size: 11px;
  opacity: 0.9;
  line-height: 1.2;
}

.notifMuteBtn {
  border-color: rgba(255, 255, 255, 0.25) !important;
  font-weight: 800 !important;
  padding: 0 8px !important;
  min-width: 0 !important;
}

.notifBody {
  padding: 10px;
  overflow: auto;
}

.notifEmpty {
  opacity: 0.7;
  font-weight: 800;
  padding: 10px 0;
  font-size: 12px;
}

.notifCard {
  border-radius: 12px;
  padding: 10px;
  border: 1px solid rgba(255, 255, 255, 0.10);
  background: rgba(255, 255, 255, 0.03);
  margin-bottom: 10px;
}

.notifCard.new {
  background: rgba(239, 68, 68, 0.16);
  border-color: rgba(239, 68, 68, 0.22);
}

.notifCard.ack {
  background: rgba(249, 115, 22, 0.14);
  border-color: rgba(249, 115, 22, 0.20);
}

.notifTopRow {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
}

.notifTitle {
  font-weight: 900;
  font-size: 12px;
  letter-spacing: 0.2px;
  line-height: 1.2;
}

.notifPill {
  font-size: 10px;
  font-weight: 900;
  padding: 3px 7px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  white-space: nowrap;
}

.pillNew {
  background: rgba(239, 68, 68, 0.22);
}

.pillAck {
  background: rgba(249, 115, 22, 0.22);
}

.notifSub2 {
  margin-top: 6px;
  font-size: 11px;
  opacity: 0.80;
  font-weight: 700;
  color: #c9bcbc;
  line-height: 1.2;
}

.notifActions {
  margin-top: 10px;
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.notifBtn {
  border-radius: 10px !important;
  border: 1px solid rgba(255, 255, 255, 0.16) !important;
  background: rgba(255, 255, 255, 0.08) !important;
  color: rgba(255, 255, 255, 0.92) !important;
  font-weight: 900 !important;
  font-size: 11px !important;
}

.notifBtn.warn {
  background: rgba(234, 179, 8, 0.22) !important;
  border-color: rgba(234, 179, 8, 0.25) !important;
}

.emptyState {
  margin-top: 14px;
  opacity: 0.7;
  font-weight: 800;
}
</style>
