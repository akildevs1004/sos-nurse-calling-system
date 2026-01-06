<template>
  <div class="dashShell">
    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" elevation="24" class="center-snackbar">
      {{ snackbarResponse }}
    </v-snackbar>

    <SosAlarmPopupMqtt @triggerUpdateDashboard="requestDashboardSnapshot()" />

    <!-- Audio (kept) -->
    <!-- <AudioSoundPlay :key="totalSOSCount" v-if="stats?.activeSos > 0" :notificationsMenuItemsCount="stats?.activeSos" /> -->

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

    <!-- Overlay MENU Backdrop -->
    <div v-if="menuOpen" class="menuBackdrop" @click="menuOpen = false"></div>

    <!-- Overlay MENU -->
    <aside class="overlayMenu" :class="{ open: menuOpen }" @click.stop>
      <div class="menuHeader">
        <div class="menuHeaderLeft">
          <v-icon>mdi mdi-view-grid-outline</v-icon>
          <!-- <v-img class="company_logo" :src="getLogo"></v-img> -->
          <!-- <div class="menuTitleBlock">
            {{ $auth?.user?.company?.name || "Company" }}
          </div> -->
          Views
        </div>

        <v-btn icon class="menuCloseBtn" @click="menuOpen = false" aria-label="Close menu">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </div>

      <div class="menuBody">
        <!-- <div class="menuSectionTitle">Split (cards per page)</div> -->
        <div class="menuGrid">
          <button class="menuItem" :class="{ active: splitMode === 4 }" @click="setSplit(4)">
            <v-icon small class="mr-2">mdi-view-grid</v-icon> 4-way Split
          </button>
          <button class="menuItem" :class="{ active: splitMode === 8 }" @click="setSplit(8)">
            <v-icon small class="mr-2">mdi-view-grid-plus</v-icon> 8-way Split
          </button>
          <button class="menuItem" :class="{ active: splitMode === 16 }" @click="setSplit(16)">
            <v-icon small class="mr-2">mdi-view-module</v-icon> 16-way Split
          </button>
          <button class="menuItem" :class="{ active: splitMode === 32 }" @click="setSplit(32)">
            <v-icon small class="mr-2">mdi-view-comfy</v-icon> 32-way Split
          </button>
          <button class="menuItem" :class="{ active: splitMode === 64 }" @click="setSplit(64)">
            <v-icon small class="mr-2">mdi-view-dashboard</v-icon> 64-way Split
          </button>
        </div>

        <div class="menuSectionTitle mt-4">Filter</div>
        <div class="menuGrid">
          <button class="menuItem" :class="{ active: filterMode === 'all' }" @click="setFilter('all')">
            <v-icon small class="mr-2">mdi-format-list-bulleted</v-icon> All
          </button>
          <button class="menuItem" :class="{ active: filterMode === 'on' }" @click="setFilter('on')">
            <v-icon small class="mr-2">mdi-bell</v-icon> SOS ON
          </button>
          <button class="menuItem" :class="{ active: filterMode === 'off' }" @click="setFilter('off')">
            <v-icon small class="mr-2">mdi-bell-off</v-icon> SOS OFF
          </button>
        </div>

        <!--<div class="menuSectionTitle mt-4">Auto rotate</div>
        <div class="menuRow">
          <div class="menuRowLabel">Interval</div>
          <div class="menuRowValue">{{ autoRotateMs / 1000 }}s</div>
        </div>

       <v-slider class="menuSlider" v-model="autoRotateMs" :min="5 * 1000" :max="60 * 1000" :step="5 * 1000"
          thumb-label hide-details @change="restartAutoRotate" /> -->

        <!-- <div class="menuFooter">
          <div class="menuFooterRow">
            <span class="menuFooterLabel">MQTT</span>
            <span class="menuFooterValue" :class="isConnected ? 'ok' : 'bad'">
              {{ isConnected ? "Online" : "Offline" }}
            </span>
          </div>
          <div class="menuFooterRow">
            <span class="menuFooterLabel">Active SOS</span>
            <span class="menuFooterValue">{{ activeSosCount }}</span>
          </div>
        </div> -->
      </div>
    </aside>

    <!-- Notifications Backdrop -->
    <div v-if="notificationsOpen" class="notifBackdrop" @click="notificationsOpen = false"></div>

    <!-- Notifications Drawer (Right side overlay) -->
    <aside class="notifDrawer" :class="{ open: notificationsOpen }" @click.stop>
      <div class="notifHeader">
        <div class="notifHeaderTitle">
          NOTIFICATION ALERTS ({{ notifications.length }})
        </div>
        <v-btn icon class="notifCloseBtn" @click="notificationsOpen = false" aria-label="Close notifications">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </div>

      <div class="notifBody">
        <div v-if="notifications.length === 0" class="notifEmpty">
          No notifications
        </div>

        <div v-for="n in notifications" :key="n.id" class="notifCard" :class="n.kind">
          <div class="notifTopRow">
            <div class="notifTitle">
              {{ n.roomName }} - {{ n.title }}
            </div>

            <!-- <v-icon @click="dismissNotification(n.id)" class="fill dark danger">mdi-close-circle</v-icon> -->

            <div class="notifPill" :class="n.pillClass">
              {{ n.pillText }}
            </div>
          </div>

          <!-- <div class="notifSub">{{ n.message }}</div> -->
          <!-- <div class="notifSub2">Active Time: {{ n.activeTime || "--:--" }}</div> -->

          <div class="notifActions">
            <!-- <v-btn x-small class="notifBtn" @click="openFromNotification(n)">
              View Details
            </v-btn> -->
            <v-btn x-small class="notifBtn success" v-if="!n.action">
              SOS Closed
            </v-btn>

            <v-btn x-small class="notifBtn warn" v-if="n.action === 'ack'" @click="ackFromNotification(n)">
              Click to Acknowledge
            </v-btn>

            <v-btn x-small class="notifBtn ok" @click="dismissNotification(n.id)" style="margin-left:auto">
              Dismiss
            </v-btn>
            <!-- <v-btn x-small class="notifBtn notifPill" :class="n.pillClass">
              {{ n.pillText }}
            </v-btn> -->

          </div>
        </div>
      </div>
    </aside>

    <!-- ===== MAIN ===== -->
    <main class="dashMain">
      <!-- HEADER -->
      <header class="dashHeader">
        <div class="hdLeft">
          <v-btn icon class="hdMenuBtn" @click="menuOpen = true" aria-label="Open menu">
            <v-icon>mdi-menu</v-icon>
          </v-btn>

          <div class="hdLogo"> <v-avatar size="35" style="border: 1px solid #fff">
              <v-img class="company_logo" :src="getLogo"></v-img>
            </v-avatar></div>

          <div class="hdTitleBlock">
            <!-- <div class="hdTitle">Monitoring Dashboard</div> -->
            <div class="hdSub">
              {{ $auth?.user?.company?.name || "Company" }}
              <!-- <span class="hdSep">•</span>
              {{ $auth?.user?.branch?.name || "Location" }} -->
            </div>
          </div>
        </div>

        <div class="hdRight" style="font-size:13px;font-weight: normal;">
          <!-- Bell -->
          <!-- <v-btn icon class="hdBellBtn" @click="toggleNotifications" aria-label="Notifications">
            <v-badge :content="unreadCount" :value="unreadCount > 0" overlap>
              <v-icon>mdi-bell-outline</v-icon>
            </v-badge>
          </v-btn> -->

          <div class="hdMeta" style="font-size:13px">
            <v-icon small class="mr-1">mdi-clock-outline</v-icon> {{ headerTime }}
          </div>

          <div class="hdMeta" style="font-size:13px">
            <v-icon small class="mr-1">mdi-calendar-month-outline</v-icon> {{ headerDate }}
          </div>

          <div class="hdMeta">
            <span class="hdStatusDot" :class="isConnected ? 'ok' : 'bad'"></span>
            <span class="hdStatusText">{{ isConnected ? "Online" : "Offline" }}</span>
          </div>

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
          <!-- Bell (AFTER logout) -->
          <v-btn icon class="hdBellBtn" @click="toggleNotifications" aria-label="Notifications">
            <v-badge :content="unreadCount" :value="unreadCount > 0" overlap>
              <v-icon>mdi-bell-outline</v-icon>
            </v-badge>
          </v-btn>
        </div>
      </header>

      <!-- CARDS -->
      <section class="dashCards">
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


                <!-- <v-btn x-small class="ackBtn" v-if="d.alarm_status === true && !d.alarm?.responded_datetime"
                  @click.stop="udpateResponse(d.alarm?.id)">
                  ACK
                </v-btn> -->
              </div>
            </v-card>
          </div>
        </div>

        <v-progress-linear v-if="loading" indeterminate height="3" class="mt-3" />
        <div v-else-if="pagedDevices.length === 0" class="emptyState">Data is not available</div>
      </section>
      <footer class="dashFooter">

        <span>© 2026 Akil Security Alarm Systems (XtremeGuard)</span> <img src="/logo.png" alt="XtremeGuard Logo"
          class="footerLogo" />
      </footer>
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
      dashboardInterval: null,

      detailsDialog: false,
      selectedRoom: null,

      // UI
      filterMode: "all",
      devices: [],
      loading: false,

      snackbar: false,
      snackbarResponse: "",

      // Overlay menu
      menuOpen: false,

      // Notifications
      notificationsOpen: false,
      notifications: [],
      notifMax: 50,
      prevRoomsMap: {},

      // Split mode = cards per page
      splitMode: 16, // 4 / 8 / 16 / 32 / 64

      // Auto rotate
      autoRotateMs: 1000 * 5,
      autoRotateTimer: null,

      // Duration timer
      timer: null,
      TIMER_MS: 1000,

      // MQTT
      mqttUrl: "",
      client: null,
      isConnected: false,
      reqId: "",
      topics: { req: "", rooms: "", stats: "", reload: "", reloadconfig: "" },

      // Stats (kept for AudioSoundPlay)
      avgResponseText: "00:00",
      avgResponsePct: 100,
      repeated: 0,
      ackCount: 0,
      totalSOSCount: 0,
      activeDisabledSos: 0,

      // Pagination
      pageIndex: 0,

      // params
      room: null,
      status: null,
      date_from: "",
      date_to: "",

      filterRoomTableIds: [],

      // Header clock
      headerTime: "",
      headerDate: "",
      _clock: null
    };
  },

  computed: {

    getLogo() {
      let logosrc = "/no-image.PNG";

      // console.log("this.$auth.user ", this.$auth.user);


      if (
        this.$auth.user &&

        this.$auth.user.company.logo
      ) {
        logosrc = this.$auth.user.company.logo;
      }

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

    roomsGridStyle() {
      return {
        gridTemplateColumns: `repeat(${this.splitCols}, minmax(0, 1fr))`
      };
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

    unreadCount() {
      return (this.notifications || []).filter(n => !n.read).length;
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
    },

    autoRotateMs() {
      this.safeLsSet(AUTO_ROTATE_KEY, String(this.autoRotateMs));
      this.restartAutoRotate();
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

    const savedSplit = Number(this.safeLsGet(SPLIT_MODE_KEY));
    if ([4, 8, 16, 32, 64].includes(savedSplit)) this.splitMode = savedSplit;

    const savedRotate = Number(this.safeLsGet(AUTO_ROTATE_KEY));
    if (Number.isFinite(savedRotate) && savedRotate >= 5000) this.autoRotateMs = savedRotate;

    const sosRooms = this.$auth?.user?.security?.sos_rooms ?? [];
    this.filterRoomTableIds = Array.isArray(sosRooms) ? sosRooms.map(r => r.id) : [];
  },

  mounted() {
    window.addEventListener("keydown", this.onKeyDown);

    this.timer = setInterval(() => this.updateDurationAll(), this.TIMER_MS);

    this.updateHeaderClock();
    this._clock = setInterval(() => this.updateHeaderClock(), 1000);

    this.mqttUrl = process.env.MQTT_SOCKET_HOST;
    this.connectMqtt();

    this.startDashboardPolling();
    this.startAutoRotate();
  },

  beforeDestroy() {
    window.removeEventListener("keydown", this.onKeyDown);

    try {
      if (this.timer) clearInterval(this.timer);
      if (this._clock) clearInterval(this._clock);
      this.stopAutoRotate();
      this.disconnectMqtt();
    } catch (e) { }

    this.stopDashboardPolling();
  },

  methods: {
    onKeyDown(e) {
      if (e.key === "Escape") {
        if (this.menuOpen) this.menuOpen = false;
        if (this.notificationsOpen) this.notificationsOpen = false;
      }
    },

    setSplit(n) {
      this.splitMode = n;
      this.menuOpen = false;
    },

    setFilter(v) {
      this.filterMode = v;
      this.menuOpen = false;
    },

    toggleNotifications() {
      this.notificationsOpen = !this.notificationsOpen;
      if (this.notificationsOpen) this.markAllRead();
    },

    markAllRead() {
      this.notifications = (this.notifications || []).map(n => ({ ...n, read: true }));
    },

    dismissNotification(id) {
      this.notifications = (this.notifications || []).filter(n => n.id !== id);
    },

    openFromNotification(n) {
      if (n?.roomId) {
        const room = (this.devices || []).find(d => (d.id || d.room_id) === n.roomId);
        if (room) {
          this.selectedRoom = room;
          this.detailsDialog = true;
        }
      }
    },

    ackFromNotification(n) {
      if (n?.alarmId) this.udpateResponse(n.alarmId);
    },

    pushNotification(payload) {
      const id = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
      const notif = {
        id,
        read: false,
        createdAt: new Date().toISOString(),
        kind: payload.kind || "info",
        roomId: payload.roomId || null,
        roomName: payload.roomName || "Room",
        title: payload.title || "Notification",
        message: payload.message || "",
        pillText: payload.pillText || "New SOS",
        pillClass: payload.pillClass || "pillNew",
        action: payload.action || null,
        alarmId: payload.alarmId || null,
        activeTime: payload.activeTime || null
      };

      this.notifications = [notif, ...(this.notifications || [])].slice(0, this.notifMax);
    },

    buildRoomsMap(list) {
      const map = {};
      (list || []).forEach(d => {
        const rid = d.id || d.room_id;
        map[rid] = d;
      });
      return map;
    },

    formatNowTime() {
      const d = new Date();
      return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", second: "2-digit" });
    },

    processRoomTransitions(newList) {
      const prev = this.prevRoomsMap || {};
      const next = this.buildRoomsMap(newList);

      Object.keys(next).forEach((rid) => {
        const oldR = prev[rid];
        const newR = next[rid];
        if (!newR) return;

        const roomName = newR.name || "Room";
        const alarmId = newR?.alarm?.id || null;

        const oldOn = oldR ? oldR.alarm_status === true : false;
        const newOn = newR.alarm_status === true;

        const oldAck = oldR ? !!oldR?.alarm?.responded_datetime : false;
        const newAck = !!newR?.alarm?.responded_datetime;

        // Triggered: OFF -> ON
        if (!oldOn && newOn) {
          this.pushNotification({
            kind: "new",
            roomId: rid,
            roomName,
            title: "SOS Triggered",
            message: `${roomName} SOS   at ${this.formatNowTime()}`,
            pillText: "NEW",
            pillClass: "pillNew",
            action: "ack",
            alarmId,
            activeTime: (newR.duration || "00:00:00").slice(3)
          });
        }

        // Acknowledged: (on) ACK becomes true
        if (newOn && !oldAck && newAck) {
          const at = newR?.alarm?.responded_datetime || this.formatNowTime();
          this.pushNotification({
            kind: "ack",
            roomId: rid,
            roomName,
            title: "Acknowledged",
            message: `${roomName} SOS is   at ${at}`,
            pillText: "ACK",
            pillClass: "pillAck",
            action: "resolve",
            alarmId,
            activeTime: (newR.duration || "00:00:00").slice(3)
          });
        }

        // Stopped: ON -> OFF
        if (oldOn && !newOn) {
          this.pushNotification({
            kind: "stop",
            roomId: rid,
            roomName,
            title: "Stopped",
            message: `${roomName}   at ${this.formatNowTime()}`,
            pillText: "STOP",
            pillClass: "pillStop",
            action: null,
            alarmId: null,
            activeTime: null
          });
        }
      });

      this.prevRoomsMap = next;
    },

    updateHeaderClock() {
      const d = new Date();
      // this.headerTime = d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", second: "2-digit" });
      this.headerTime = d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });

      this.headerDate = d.toISOString().slice(0, 10);
    },

    // ===== Auto rotate =====
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

    // ===== helpers =====
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

    // ===== polling =====
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

        // Build notifications based on transitions
        this.processRoomTransitions(list);

        this.devices = this.normalizeRooms(list);
        this.updateDurationAll();
        if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;
        this.loading = false;
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
/* ===== shell ===== */
.dashShell {
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: linear-gradient(180deg, #0a0e16, #0e1420);
  color: rgba(255, 255, 255, 0.92);
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

.hdMenuBtn {
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
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

.hdTitle {
  font-weight: 900;
  font-size: 16px;
  letter-spacing: 0.6px;
}

.hdSub {
  margin-top: 2px;
  font-size: 12px;
  opacity: 0.75;
}

.hdSep {
  margin: 0 8px;
  opacity: 0.6;
}

.hdRight {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.hdMeta {
  /* font-weight: 800; */
  opacity: 0.9;
  display: flex;
  align-items: center;
}

.hdBtn {
  border-color: rgba(255, 255, 255, 0.25) !important;
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

/* Bell */
.hdBellBtn {
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

/* ===== main ===== */
.dashMain {
  height: 100%;
  width: 100%;
  display: grid;
  grid-template-rows: auto 1fr;
  min-width: 0;
}

/* ===== cards ===== */
.dashCards {
  padding: 14px 16px;
  overflow: auto;
  min-width: 0;
}

.cardsGrid {
  display: grid;
  gap: 12px;
  width: 100%;
}

.roomCard {
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: linear-gradient(180deg, rgba(24, 33, 48, 0.85), rgba(24, 33, 48, 0.72));
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.03);
  display: flex;
  flex-direction: column;
  min-height: 140px;
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

/*
.statusCircle {
  width: 50px;
  height: 50px;
  border-radius: 999px;
  display: grid;
  place-items: center;
  background: rgba(0, 0, 0, 0.18);
  border: 4px solid #22c55e;
} */


/*
.statusIcon {
  font-size: 42px !important;
}

.statusIcon.isOk {
  color: #22c55e;
}

.statusIcon.isAlarm {
  color: #ef4444;
} */

.cardBottom {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 10px;
  font-weight: 900;
  position: relative;
}

.bottomOk {
  background: rgba(34, 197, 94, 0.9);
  /* color: rgba(0, 0, 0, 0.85); */
  color: rgba(255, 255, 255, 0.92);

  font-weight: normal;
}

.bottomAlarm {
  background: rgba(239, 68, 68, 0.85);
  color: rgba(255, 255, 255, 0.92);
}

/* ✅ NEW: acknowledged (brown / amber) */
.bottomAck {
  background: rgba(161, 98, 7, 0.9);
  /* amber/brown */
  color: #fff;
}

.bottomText {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 14px;
}

.ackBtn {
  position: absolute;
  right: 8px;
  bottom: 8px;
  min-width: 44px !important;
  height: 20px !important;
  padding: 0 10px !important;
  border-radius: 999px !important;
  background: rgba(255, 255, 255, 0.18) !important;
  color: rgba(255, 255, 255, 0.95) !important;
  border: 1px solid rgba(255, 255, 255, 0.25) !important;
}

.emptyState {
  margin-top: 14px;
  opacity: 0.7;
  font-weight: 800;
}

/* ===== overlay menu ===== */
.menuBackdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 50;
}

.overlayMenu {
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  width: 300px;
  max-width: 86vw;
  transform: translateX(-110%);
  transition: transform 180ms ease;
  z-index: 60;
  background: rgba(12, 16, 25, 0.96);
  border-right: 1px solid rgba(255, 255, 255, 0.10);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
  display: flex;
  flex-direction: column;
}

.overlayMenu.open {
  transform: translateX(0);
}

.menuHeader {
  padding: 14px 14px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.menuHeaderLeft {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.menuLogo {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  font-weight: 900;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.menuTitleBlock {
  min-width: 0;
}

.menuTitle {
  font-weight: 900;
  letter-spacing: 0.4px;
}

.menuSub {
  font-size: 12px;
  opacity: 0.75;
  margin-top: 2px;
}

.menuCloseBtn {
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.menuBody {
  padding: 12px 14px 14px;
  overflow: auto;
}

.menuSectionTitle {
  font-size: 12px;
  opacity: 0.7;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  padding: 10px 2px 6px;
}

.menuGrid {
  display: grid;
  gap: 8px;
}

.menuItem {
  width: 100%;
  display: flex;
  align-items: center;
  padding: 10px 10px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.06);
  cursor: pointer;
  color: inherit;
  /* font-weight: 900; */
}

.menuItem.active {
  background: #5046e5;
  border-color: rgba(99, 102, 241, 0.35);
}

.menuRow {
  display: flex;
  justify-content: space-between;
  padding: 4px 2px;
  margin-top: 6px;
}

.menuRowLabel {
  font-size: 12px;
  opacity: 0.75;
  font-weight: 800;
}

.menuRowValue {
  font-size: 12px;
  font-weight: 900;
}

.menuSlider {
  padding: 0 2px;
}

.menuFooter {
  margin-top: 14px;
  padding: 10px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.03);
}

.menuFooterRow {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
}

.menuFooterLabel {
  font-size: 12px;
  opacity: 0.75;
  font-weight: 700;
}

.menuFooterValue {
  font-weight: 900;
}

.menuFooterValue.ok {
  color: #22c55e;
}

.menuFooterValue.bad {
  color: #ef4444;
}

/* ===== notifications ===== */
.notifBackdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 70;
}

.notifDrawer {
  position: fixed;
  top: 0;
  right: 0;
  height: 100vh;
  width: 300px;
  max-width: 92vw;
  transform: translateX(110%);
  transition: transform 180ms ease;
  z-index: 80;
  background: rgba(12, 16, 25, 0.96);
  border-left: 1px solid rgba(255, 255, 255, 0.10);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
  display: flex;
  flex-direction: column;
}

.notifDrawer.open {
  transform: translateX(0);
}

.notifHeader {
  padding: 14px 14px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.notifHeaderTitle {
  font-weight: 900;
  letter-spacing: 0.6px;
  font-size: 12px;
  opacity: 0.9;
}

.notifCloseBtn {
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.notifBody {
  padding: 12px 14px 14px;
  overflow: auto;
}

.notifEmpty {
  opacity: 0.7;
  font-weight: 800;
  padding: 10px 0;
}

/* notif cards */
.notifCard {
  border-radius: 12px;
  padding: 12px;
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

.notifCard.stop {
  background: rgba(34, 197, 94, 0.10);
  border-color: rgba(34, 197, 94, 0.18);
}

.notifTopRow {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.notifTitle {
  font-weight: 900;
  font-size: 13px;
  letter-spacing: 0.2px;
}

.notifPill {
  font-size: 11px;
  font-weight: 900;
  padding: 4px 8px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.16);
}

.pillNew {
  background: rgba(239, 68, 68, 0.22);
}

.pillAck {
  background: rgba(249, 115, 22, 0.22);
}

.pillStop {
  background: rgba(34, 197, 94, 0.18);
}

.notifSub {
  margin-top: 6px;
  font-size: 12px;
  opacity: 0.9;
  font-weight: 700;
}

.notifSub2 {
  margin-top: 4px;
  font-size: 11px;
  opacity: 0.75;
  font-weight: 700;
  color: #897575
}

.notifActions {
  margin-top: 10px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.notifBtn {
  border-radius: 10px !important;
  border: 1px solid rgba(255, 255, 255, 0.16) !important;
  background: rgba(255, 255, 255, 0.08) !important;
  color: rgba(255, 255, 255, 0.92) !important;
  font-weight: 900 !important;
}

.notifBtn.warn {
  background: rgba(234, 179, 8, 0.22) !important;
  border-color: rgba(234, 179, 8, 0.25) !important;
}

.notifBtn.ok {
  background: rgba(34, 197, 94, 0.18) !important;
  border-color: rgba(34, 197, 94, 0.22) !important;
}

.notifBtn.ghost {
  background: rgba(255, 255, 255, 0.04) !important;
}

.hdMeta .v-icon {
  color: rgba(99, 102, 241, 0.95) !important;
  /* Indigo / blue-ish */
  opacity: 0.95;
}

/* optional: make date icon slightly different */
.hdMeta:nth-of-type(2) .v-icon {
  color: rgba(34, 197, 94, 0.95) !important;
  /* Green */
}

/* Improve disabled look for buttons */
.hdBtn.v-btn--disabled {
  opacity: 0.45 !important;
  cursor: not-allowed !important;
}

/* Make main layout include footer row */
.dashMain {
  height: 100%;
  width: 100%;
  display: grid;
  grid-template-rows: auto 1fr auto;
  /* header, content, footer */
  min-width: 0;
}

.footerLogo {
  height: 18px;
  width: auto;
  margin-right: 10px;
  vertical-align: middle;
  opacity: 0.95;
}

.dashFooter {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  /* ✅ move content to right */

  gap: 8px;
  padding: 10px 16px;

  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.6px;
  opacity: 0.8;

  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.14);
}

/* Base circle */
.statusCircle {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  /* border: 2px solid rgba(255, 255, 255, 0.12);
  background: rgba(0, 0, 0, 0.18); */
  transition: all 0.25s ease;
}

/* OK state */
/* .statusCircle.isOk {
  background: rgba(34, 197, 94, 0.18);
  border-color: rgba(34, 197, 94, 0.45);
} */

.statusIcon.isOk {
  color: #22c55e;
  font-size: 42px !important;
}

/* ALARM state */
.statusCircle.isAlarm {
  background: rgba(239, 68, 68, 0.22);
  border-color: rgba(239, 68, 68, 0.65);
  animation: alarmPulse 1.2s infinite;
}

.statusIcon.isAlarm {
  color: #ef4444;
  font-size: 42px !important;
}

/* Optional stronger alarm */
.statusCircle.danger {
  box-shadow: 0 0 18px rgba(239, 68, 68, 0.55);
}

/* Alarm pulse */
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
</style>
