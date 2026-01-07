<template>
  <div class="dashShell">
    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" elevation="24" class="center-snackbar">
      {{ snackbarResponse }}
    </v-snackbar>

    <!-- ===== HEADER (ONLY WHEN NOT TV) ===== -->
    <!-- <header v-if="!isTv" class="dashHeader">
      <div class="hdLeft">
        <v-avatar size="34" class="hdLogo">
          <v-img :src="getLogo" />
        </v-avatar>

        <div class="hdTitleBlock">
          <div class="hdSub text-truncate">
            {{ $auth?.user?.company?.name || "Company" }}
          </div>
        </div>
      </div>

      <div class="hdRight">
        <div class="hdMeta">
          <v-icon small class="mr-1 icoClock">mdi-clock-outline</v-icon>
          {{ headerTime }}
        </div>

        <div class="hdMeta">
          <v-icon small class="mr-1 icoDate">mdi-calendar-month-outline</v-icon>
          {{ headerDate }}
        </div>

        <div class="hdMeta">
          <span class="hdStatusDot" :class="isConnected ? 'ok' : 'bad'"></span>
          <span class="hdStatusText">{{ isConnected ? "Online" : "Offline" }}</span>
        </div>
      </div>
    </header> -->
    <SosAlarmPopupMqtt @triggerUpdateDashboard="requestDashboardSnapshot()" />
    <!-- ===== BODY ===== -->
    <div class="dashBody" :class="{ hasNotif: activeNewAlarmRooms.length > 0 }">
      <!-- Left Rail (EXPAND ON HOVER) -->
      <aside class="rail" :class="{ expanded: railExpanded }" @mouseenter="railExpanded = true"
        @mouseleave="railExpanded = false">
        <div class="railPanel">
          <!-- Split -->
          <button class="railItem" :class="{ active: splitMode === 4 }" @click="setSplit(4)" title="4-way">
            <v-icon class="railIcon">mdi-view-grid</v-icon>
            <span class="railText">4-way Split</span>
          </button>

          <button class="railItem" :class="{ active: splitMode === 8 }" @click="setSplit(8)" title="8-way">
            <v-icon class="railIcon">mdi-view-grid-plus</v-icon>
            <span class="railText">8-way Split</span>
          </button>

          <button class="railItem" :class="{ active: splitMode === 16 }" @click="setSplit(16)" title="16-way">
            <v-icon class="railIcon">mdi-view-module</v-icon>
            <span class="railText">16-way Split</span>
          </button>

          <button class="railItem" :class="{ active: splitMode === 32 }" @click="setSplit(32)" title="32-way">
            <v-icon class="railIcon">mdi-view-comfy</v-icon>
            <span class="railText">32-way Split</span>
          </button>

          <button class="railItem" :class="{ active: splitMode === 64 }" @click="setSplit(64)" title="64-way">
            <v-icon class="railIcon">mdi-view-dashboard</v-icon>
            <span class="railText">64-way Split</span>
          </button>

          <div class="railDivider"></div>

          <!-- Mute / Unmute -->
          <button class="railItem" @click="toggleMute" :title="muted ? 'Unmute' : 'Mute'">
            <v-icon class="railIcon">{{ muted ? "mdi-volume-off" : "mdi-volume-high" }}</v-icon>
            <span class="railText">{{ muted ? "Muted" : "Sound On" }}</span>
          </button>

          <!-- TV only: Prev/Next + Logout -->
          <template v-if="isTv">
            <button class="railItem" @click="prevPage" :disabled="totalPages <= 1" title="Prev">
              <v-icon class="railIcon">mdi-chevron-left</v-icon>
              <span class="railText">Prev1</span>
            </button>
            <div class="railPageText">
              {{ pageIndex + 1 }}/{{ totalPages }}
            </div>

            <button class="railItem" @click="nextPage" :disabled="totalPages <= 1" title="Next">
              <v-icon class="railIcon">mdi-chevron-right</v-icon>
              <span class="railText">Next</span>
            </button>

            <button v-if="$auth?.user" class="railItem" @click="logout" title="Logout">
              <v-icon class="railIcon">mdi-logout</v-icon>
              <span class="railText">Logout</span>
            </button>
          </template>

          <div class="railDivider"></div>

          <!-- Bell -->
          <button class="railItem" :class="{ blink: bellBlink }" :disabled="activeNewAlarmRooms.length === 0"
            @click="markBellSeen" title="NOTIFICATION   ALERTS">
            <v-badge :content="activeNewAlarmRooms.length" :value="activeNewAlarmRooms.length > 0" overlap>
              <v-icon class="railIcon">mdi-bell-outline</v-icon>
            </v-badge>
            <span class="railText">Alerts</span>
          </button>

          <!-- Desktop paging + logout -->
          <template v-if="!isTv">
            <div class="railDivider"></div>

            <button class="railItem" @click="prevPage" :disabled="totalPages <= 1" title="Prev">
              <v-icon class="railIcon">mdi-chevron-left</v-icon>
              <span class="railText">Prev2</span>
            </button>

            <div class="railPageText railItem railIcon" style="display:grid">
              {{ pageIndex + 1 }}/{{ totalPages }}
            </div>

            <button class="railItem" @click="nextPage" :disabled="totalPages <= 1" title="Next">
              <v-icon class="railIcon">mdi-chevron-right</v-icon>
              <span class="railText">Next</span>
            </button>

            <button v-if="$auth?.user" class="railItem" @click="logout" title="Logout">
              <v-icon class="railIcon">mdi-logout</v-icon>
              <span class="railText">Logout</span>
            </button>
          </template>
        </div>
      </aside>

      <!-- Main grid -->
      <main class="dashMain">
        <section class="dashCards">
          <div class="cardsGrid" :class="splitClass" :style="roomsGridStyle">
            <div v-for="(d, i) in gridItems" :key="d ? (d.id || d.room_id) : `blank-${i}`" class="cardCell">
              <v-card v-if="d" outlined class="roomCard" :class="cardClass(d)">
                <div class="cardTop " :class="{
                  topAlarm: d.alarm_status && !d.alarm?.responded_datetime,
                  topAck: d.alarm_status && d.alarm?.responded_datetime,
                  topOk: !d.alarm_status
                }">
                  <div class="cardTitle">
                    <v-icon small class="mr-2" style="font-size: 30px;">{{ isToilet(d) ? "mdi-toilet" : "mdi-bed"
                    }}</v-icon>
                    <span class="text-truncate">{{ (d.name || "ROOM").toUpperCase() }}</span>
                  </div>

                  <v-icon small :class="d.device?.status_id == 1 ? 'wifiOk' : 'wifiOff'">
                    {{ d.device?.status_id == 1 ? "mdi-wifi" : "mdi-wifi-off" }}
                  </v-icon>
                </div>

                <div class="cardMid">
                  <div class="statusCircle" :class="d.alarm_status ? 'isAlarm' : 'isOk'">
                    <v-icon medium class="statusIcon" :class="d.alarm_status ? 'isAlarm' : 'isOk'">
                      {{ d.alarm_status ? "mdi-close-circle" : "mdi-check-circle-outline" }}
                    </v-icon>
                  </div>
                </div>

                <div class="cardBottom" :class="{
                  bottomAlarm: d.alarm_status && !d.alarm?.responded_datetime,
                  bottomAck: d.alarm_status && d.alarm?.responded_datetime,
                  bottomOk: !d.alarm_status
                }">
                  <v-icon medium class="mr-2">
                    {{
                      d.alarm_status
                        ? (d.alarm?.responded_datetime ? "mdi-bell-check" : "mdi-bell-ring")
                        : "mdi-bell-off"
                    }}
                  </v-icon>

                  <span class="bottomText">
                    <template v-if="d.alarm_status">
                      <template v-if="d.alarm?.responded_datetime">Acknowledged</template>
                      <template v-else>Active Alarm: {{ (d.duration || "00:00:00").slice(3) }}</template>
                    </template>
                    <template v-else style="font-size:16px">No Active Alarm</template>
                  </span>
                </div>
              </v-card>

              <div v-else class="blankCell"></div>
            </div>
          </div>

          <v-progress-linear v-if="loading" indeterminate height="3" class="mt-3" />
          <div v-else-if="pagedDevices.length === 0" class="emptyState">Data is not available</div>
        </section>

        <!-- Desktop sound (ONLY when NOT TV) -->
        <AudioSoundPlay v-if="!isTv && stats.activeSos > 0 && !muted && Date.now() > soundSuppressUntil"
          :key="totalSOSCount" :notificationsMenuItemsCount="stats.activeSos" />
      </main>

      <!-- Notifications Drawer (ONLY when alarms > 0) -->
      <aside v-if="activeNewAlarmRooms.length > 0" class="notifDrawer">
        <div class="notifHeader">
          <div class="notifHeaderTitle">
            NOTIFICATION ALERTS ({{ activeNewAlarmRooms.length }})
          </div>
        </div>

        <div class="notifBody">
          <div v-for="r in activeNewAlarmRooms" :key="r.id || r.room_id" class="notifCard" :class="notifKind(r)">
            <div class="notifTopRow">
              <div class="notifTitle">{{ (r.name || "ROOM").toUpperCase() }}</div>
              <div class="notifPill" :class="r.alarm?.responded_datetime ? 'pillAck' : 'pillNew'">
                {{ r.alarm?.responded_datetime ? "ACK" : "NEW" }}
              </div>
            </div>

            <div class="notifSub2">Active Time: {{ getRoomActiveTime(r) }}</div>
            <div class="notifSub2">Start: {{ r.alarm?.alarm_start_datetime || r.alarm_start_datetime || "-" }}</div>

            <v-btn x-small class="notifBtn warn" :disabled="!!r.alarm?.responded_datetime"
              @click="udpateResponse(r.alarm?.id)">
              ACKNOWLEDGEMENT
            </v-btn>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script>
import mqtt from "mqtt";
import AudioSoundPlay from "@/components/Alarm/AudioSoundPlay.vue";

const SPLIT_MODE_KEY = "dash_split_mode";

export default {
  name: "SosMonitorBody",
  components: { AudioSoundPlay },

  data() {
    return {
      // UI
      splitMode: 16,
      pageIndex: 0,
      muted: false,
      railExpanded: false,

      // Data
      devices: [],
      loading: false,

      snackbar: false,
      snackbarResponse: "",

      // Screen
      screenW: 0,
      screenH: 0,

      // Clock
      headerTime: "",
      headerDate: "",
      _clock: null,

      // MQTT
      mqttUrl: "",
      client: null,
      isConnected: false,
      topics: { req: "", rooms: "", stats: "", reload: "", reloadconfig: "" },
      _onMqttMessageBound: null,

      // Stats
      repeated: 0,
      ackCount: 0,
      totalSOSCount: 0,
      activeDisabledSos: 0,

      // Duration timer
      timer: null,
      TIMER_MS: 1000,

      // Bell blink
      bellSeen: false,

      // Auto rotate
      autoRotateTimer: null,
      AUTO_ROTATE_MS: 1000 * 60,

      // Desktop sound suppression after ACK
      soundSuppressUntil: 0
    };
  },

  computed: {
    // TV: width 0 OR between 500..1000
    splitClass() {
      return `split-${this.splitMode}`; // split-4 / split-8 / split-16 / split-32 / split-64
    },
    isTv() {
      const w = Number(this.screenW || 0);
      if (w === 0) return true;
      return w >= 500 && w <= 1000;
    },

    getLogo() {
      return this.$auth?.user?.company?.logo || "/logo.png";
    },

    pageSize() {
      const s = Number(this.splitMode);
      return [4, 8, 16, 32, 64].includes(s) ? s : 16;
    },

    totalPages() {
      const n = (this.devices || []).length;
      return Math.max(1, Math.ceil(n / this.pageSize));
    },

    pagedDevices() {
      const start = this.pageIndex * this.pageSize;
      return (this.devices || []).slice(start, start + this.pageSize);
    },

    // Exact grid geometry (your spec)
    splitGrid() {
      const s = Number(this.splitMode);
      if (s === 4) return { cols: 2, rows: 2 };
      if (s === 8) return { cols: 3, rows: 3 };
      if (s === 16) return { cols: 4, rows: 4 };
      if (s === 32) return { cols: 7, rows: 5 };
      if (s === 64) return { cols: 12, rows: 6 };
      return { cols: 4, rows: 4 };
    },

    gridSlots() {
      return this.splitGrid.cols * this.splitGrid.rows;
    },

    gridItems() {
      const items = (this.pagedDevices || []).slice(0, this.gridSlots);
      const blanks = Math.max(0, this.gridSlots - items.length);
      for (let i = 0; i < blanks; i++) items.push(null);
      return items;
    },

    roomsGridStyle() {
      return {
        gridTemplateColumns: `repeat(${this.splitGrid.cols}, minmax(0, 1fr))`,
        gridTemplateRows: `repeat(${this.splitGrid.rows}, minmax(0, 1fr))`,
        "--grid-rows": String(this.splitGrid.rows),
      };
    },
    activeNewAlarmRooms() {
      return (this.devices || []).filter(
        d => d && d.alarm_status === true
      );
    },

    activeSosCount() {
      return this.activeNewAlarmRooms.length;
    },

    bellBlink() {
      return this.activeNewAlarmRooms.length > 0 && !this.bellSeen;
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
    }
  },

  watch: {
    totalPages() {
      if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;
    }
  },

  created() {
    this.$vuetify.theme.dark = true;

    // screen
    this.readScreen();

    // saved split
    const savedSplit = Number(this.safeLsGet(SPLIT_MODE_KEY));
    if ([4, 8, 16, 32, 64].includes(savedSplit)) this.splitMode = savedSplit;

    // mqttUrl must be set BEFORE connect
    this.mqttUrl = process.env.MQTT_SOCKET_HOST || "";
  },

  mounted() {
    window.addEventListener("resize", this.readScreen);

    // Clock
    this.updateHeaderClock();
    this._clock = setInterval(() => this.updateHeaderClock(), 1000);

    // Duration ticking
    this.timer = setInterval(() => this.updateDurationAll(), this.TIMER_MS);

    // MQTT
    this.connectMqtt();

    // Auto rotate
    this.startAutoRotate();
  },

  beforeDestroy() {
    window.removeEventListener("resize", this.readScreen);

    try {
      if (this._clock) clearInterval(this._clock);
      if (this.timer) clearInterval(this.timer);
      this.stopAutoRotate();
      this.disconnectMqtt();
    } catch (e) { }
  },

  methods: {
    // ========== Rail behavior ==========
    collapseRailAfterAction() {
      this.railExpanded = false;
    },

    // ========== Auto rotate ==========
    startAutoRotate() {
      this.stopAutoRotate();
      this.autoRotateTimer = setInterval(() => {
        this.nextPageLoop();
      }, this.AUTO_ROTATE_MS);
    },

    stopAutoRotate() {
      if (this.autoRotateTimer) {
        clearInterval(this.autoRotateTimer);
        this.autoRotateTimer = null;
      }
    },

    nextPageLoop() {
      if (this.totalPages <= 1) return;
      this.pageIndex = (this.pageIndex + 1) % this.totalPages;
    },

    // ========== UI ==========
    notifKind(r) {
      if (r?.alarm_status === true && r?.alarm?.responded_datetime) return "ack";
      if (r?.alarm_status === true) return "new";
      return "stop";
    },

    markBellSeen() {
      this.bellSeen = true;
      this.collapseRailAfterAction();
    },

    toggleMute() {
      this.muted = !this.muted;

      // TV: AndroidBridge only
      if (this.isTv) {
        try {
          if (this.muted) AndroidBridge.stopAlarm();
          else if (this.activeSosCount > 0) AndroidBridge.startAlarm();
        } catch (e) { }
      }

      this.collapseRailAfterAction();
    },

    setSplit(n) {
      this.splitMode = n;
      this.pageIndex = 0;
      this.safeLsSet(SPLIT_MODE_KEY, String(n));
      this.collapseRailAfterAction();
    },

    nextPage() {
      if (this.totalPages <= 1) return;
      this.pageIndex = (this.pageIndex + 1) % this.totalPages;
      this.collapseRailAfterAction();
    },

    prevPage() {
      if (this.totalPages <= 1) return;
      this.pageIndex = (this.pageIndex - 1 + this.totalPages) % this.totalPages;
      this.collapseRailAfterAction();
    },

    logout() {
      this.$router.push("/logout");
      this.collapseRailAfterAction();
    },

    readScreen() {
      try {
        this.screenW = window.innerWidth || 0;
        this.screenH = window.innerHeight || 0;
      } catch (e) {
        this.screenW = 0;
        this.screenH = 0;
      }
    },

    updateHeaderClock() {
      const d = new Date();
      this.headerTime = d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
      this.headerDate = d.toISOString().slice(0, 10);
    },

    cardClass(d) {
      if (!d) return "";
      if (d.alarm_status === true && !d.alarm?.responded_datetime) return "cardOn";
      if (d.alarm_status === true && d.alarm?.responded_datetime) return "cardAck";
      return "cardOff";
    },

    isToilet(d) {
      return d?.room_type === "toilet" || d?.room_type === "toilet-ph";
    },

    getRoomActiveTime(room) {
      if (!room) return "--:--";
      if (room.duration) return (room.duration || "00:00:00").slice(3);

      if (room.startMs) {
        const diffSec = Math.max(0, Math.floor((Date.now() - room.startMs) / 1000));
        const mm = String(Math.floor(diffSec / 60)).padStart(2, "0");
        const ss = String(diffSec % 60).padStart(2, "0");
        return `${mm}:${ss}`;
      }
      return "--:--";
    },

    // ========== Sound control ==========
    stopAlarmSoundNow() {
      // Desktop: suppress AudioSoundPlay briefly
      this.soundSuppressUntil = Date.now() + 3000;

      // TV: stop Android alarm immediately
      if (this.isTv) {
        try {
          AndroidBridge.stopAlarm();
        } catch (e) { }
      }
    },

    // ========== SAFE LOCALSTORAGE ==========
    safeLsGet(key) {
      try {
        return window?.localStorage?.getItem(key) ?? null;
      } catch (e) {
        return null;
      }
    },

    safeLsSet(key, value) {
      try {
        window?.localStorage?.setItem(key, value);
      } catch (e) { }
    },

    // ========== MQTT (IMPORTANT FIX: bind handlers) ==========
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

      const clientId = `tvmonitor-${companyId}-${Date.now()}-${Math.random().toString(16).slice(2)}`;

      this.client = mqtt.connect(this.mqttUrl, {
        reconnectPeriod: 3000,
        keepalive: 30,
        clean: true,
        clientId
      });

      // ✅ bind message handler so Vue "this" is correct
      this._onMqttMessageBound = (t, p) => this.onMqttMessage(t, p);

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

      this.client.on("message", this._onMqttMessageBound);
      this.client.on("close", () => (this.isConnected = false));
      this.client.on("offline", () => (this.isConnected = false));
      this.client.on("error", () => (this.isConnected = false));
    },

    disconnectMqtt() {
      try {
        if (!this.client) return;

        if (this._onMqttMessageBound) {
          this.client.removeListener("message", this._onMqttMessageBound);
          this._onMqttMessageBound = null;
        }

        this.client.end(true);
        this.client = null;
        this.isConnected = false;
      } catch (e) { }
    },

    requestDashboardSnapshot() {
      if (!this.client || !this.isConnected) return;

      const companyId = this.$auth?.user ? this.$auth?.user?.company_id : Number(process.env.TV_COMPANY_ID || 0);
      const securityId = this.$auth?.user?.security?.id || 0;

      const payload = {
        reqId: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
        params: {
          company_id: companyId,
          securityId: securityId || null
        }
      };

      this.client.publish(this.topics.req, JSON.stringify(payload), { qos: 0, retain: false });
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
        const data = msg?.data;
        const list = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];

        this.devices = this.normalizeRooms(list);
        this.updateDurationAll();

        if (this.pageIndex > this.totalPages - 1) this.pageIndex = 0;

        // TV sound control
        if (this.isTv) {
          try {
            if (this.muted) AndroidBridge.stopAlarm();
            else if (this.activeSosCount > 0) AndroidBridge.startAlarm();
            else AndroidBridge.stopAlarm();
          } catch (e) { }
        }

        // Bell resets when alarms exist
        if (this.activeSosCount > 0) this.bellSeen = false;

        return;
      }

      if (topic === this.topics.reload) {
        try {
          window.location.reload();
        } catch (e) { }
        return;
      }

      if (topic === this.topics.reloadconfig) {
        try {
          this.requestDashboardSnapshot();
        } catch (e) { }
        return;
      }

      if (topic === this.topics.stats) {
        const s = msg?.data || {};
        this.repeated = s.repeated || 0;
        this.ackCount = s.ackCount || 0;
        this.totalSOSCount = s.totalSOSCount || 0;
        this.activeDisabledSos = s.activeDisabledSos || 0;

        // TV sound control
        if (this.isTv) {
          try {
            if (this.muted) AndroidBridge.stopAlarm();
            else if (this.activeSosCount > 0) AndroidBridge.startAlarm();
            else AndroidBridge.stopAlarm();
          } catch (e) { }
        }
      }
    },

    // ========== Rooms normalize + duration ==========
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

    // ========== ACK ==========
    udpateResponse(alarmId) {
      if (!alarmId) return;

      // ✅ stop sound immediately (TV + Desktop)
      this.stopAlarmSoundNow();

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

      // refresh after short delay
      setTimeout(() => this.requestDashboardSnapshot(), 400);
    }
  }
};
</script>

<style scoped>
/* force LEFT layout always */
.dashShell {
  direction: ltr !important;
  width: 100%;
  height: 100vh;
  overflow: hidden;
  background: linear-gradient(180deg, #0a0e16, #0e1420);
  color: rgba(255, 255, 255, 0.92);
}

/* Header desktop only */
.dashHeader {
  height: 54px;
  padding: 10px 14px;
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
  border: 1px solid rgba(255, 255, 255, 0.18);
  background: rgba(255, 255, 255, 0.04);
}

.hdSub {
  font-size: 12px;
  opacity: 0.85;
  font-weight: 900;
}

.hdRight {
  display: flex;
  align-items: center;
  gap: 10px;
  justify-content: flex-end;
}

.hdMeta {
  display: flex;
  align-items: center;
  font-size: 12px;
  opacity: 0.9;
}

.icoClock {
  color: rgba(99, 102, 241, 0.95) !important;
}

.icoDate {
  color: rgba(34, 197, 94, 0.95) !important;
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

/* Body layout */
.dashBody {
  height: 100%;
  min-height: 0;
  display: grid;
  grid-template-columns: 60px 1fr;
  overflow: hidden;
}

.dashBody.hasNotif {
  grid-template-columns: 60px 1fr 200px;
}

/* Rail */
/* Keep grid column fixed, allow overlay */
.rail {
  position: relative;
  width: 60px;
  /* DO NOT animate width */
  height: 100%;
  min-height: 0;
  overflow: visible;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  background: transparent;
}

/* The actual sliding panel */
.rail {
  position: relative;
  width: 60px;
  /* fixed grid column */
  height: 100%;
  overflow: visible;
}

/* panel */
/* grid column width = collapsed visible width */
.rail {
  position: relative;
  width: 60px;
  /* COLLAPSED WIDTH */
  height: 100%;
  overflow: visible;
  z-index: 99999;
  /* optional */

  border-right: 1px solid rgba(255, 255, 255, 0.08);
}

/* panel expands over content */
.railPanel {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;

  width: 200px;
  /* EXPANDED WIDTH */
  padding: 10px 8px;
  display: flex;
  flex-direction: column;
  gap: 10px;

  background: rgba(0, 0, 0, 0.18);
  border-right: 1px solid rgba(255, 255, 255, 0.08);

  /* collapsed: show only 80px */
  transform: translate3d(-140px, 0, 0);
  /* 220 - 80 = 140 */
  transition: transform 160ms ease;
  will-change: transform;
  z-index: 9999;
}

.rail.expanded .railPanel {
  transform: translate3d(0, 0, 0);
  background-color: #0a0e17;
}



/* icons always visible */
/* default (expanded) stays as-is */
.railItem {
  width: 100%;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 12px;
}

/* COLLAPSED: make item narrower and pinned to the right edge */
.rail:not(.expanded) .railItem {
  width: 56px;
  /* reduced item width (adjust 48–64) */
  margin-left: auto;
  /* push item to the RIGHT side */
  /* justify-content: center; */
  /* icon centered inside the small item */
  padding: 0;
  /* no left/right padding */
  gap: 0;
  /* no spacing needed */
}


/* collapsed: keep icon inside the visible right-strip */
.rail:not(.expanded) .railItem {
  /* justify-content: flex-end; */
  /* push content to the visible side */
  padding: 0 10px;
  /* right padding */
  gap: 0;
  width: 50px;
}

/* make sure icon has no extra left spacing */
.rail:not(.expanded) .railIcon {
  margin: 0 !important;
}

.railIcon {
  min-width: 22px;
  font-size: 22px !important;
  color: currentColor !important;
}

/* Text transitions (no layout) */
.railText {
  opacity: 0;
  transform: translateX(-8px);
}

.rail.expanded .railText {
  opacity: 1;
  transform: translateX(0);
}

.rail::after {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  width: 10px;
  /* buffer */
  height: 100%;
}

.railItem {
  width: 100%;
  height: 44px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.10);
  background: rgba(255, 255, 255, 0.04);
  color: rgba(255, 255, 255, 0.92);
  cursor: pointer;

  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 10px;
}

.railItem.active {
  background: rgba(99, 102, 241, 0.35);
  border-color: rgba(99, 102, 241, 0.45);
}

.railItem:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.railIcon {
  font-size: 22px !important;
  min-width: 22px;
  color: currentColor !important;

}

.railText {
  opacity: 0;
  transform: translateX(-6px);
  transition: all 50ms ease;
  white-space: nowrap;
  font-weight: 800;
  font-size: 12px;
}

.rail.expanded .railText {
  opacity: 1;
  transform: translateX(0);
}

.railDivider {
  width: 100%;
  height: 1px;
  background: rgba(255, 255, 255, 0.10);
  margin: 2px 0;
}

.railPageText {
  text-align: center;
  font-size: 12px;
  font-weight: 900;
  opacity: 0.85;
}

/* Blink bell */
.blink {
  animation: blinkPulse 1s infinite;
}

@keyframes blinkPulse {

  0%,
  100% {
    box-shadow: 0 0 0 rgba(239, 68, 68, 0);
  }

  50% {
    box-shadow: 0 0 18px rgba(239, 68, 68, 0.55);
  }
}

/* Main */
.dashMain {
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.dashCards {
  height: 100%;
  min-height: 0 !important;
  overflow: hidden;
  padding: 12px;
}

/* Grid: FULL HEIGHT with fixed rows per split */
.cardsGrid {
  width: 100%;
  height: 100% !important;
  min-height: 0 !important;
  display: grid;
  gap: 12px;
  align-content: stretch;
  justify-content: stretch;
}

.cardCell {
  height: 100%;
  min-height: 0 !important;
}

.roomCard {
  height: 100% !important;
  min-height: 0 !important;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: linear-gradient(180deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.92));
  box-shadow: 0 14px 34px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.03);
  display: flex;
  flex-direction: column;
}

.v-card {
  min-height: 0 !important;
}

.blankCell {
  height: 100%;
  border-radius: 14px;
  border: 1px dashed rgba(255, 255, 255, 0.10);
  background: rgba(255, 255, 255, 0.02);
}

/* Card states */
.cardOn {
  border-color: rgba(239, 68, 68, 0.28);
  background: linear-gradient(180deg, rgba(239, 68, 68, 0.16), rgba(15, 23, 42, 0.92));
}

.cardAck {
  border-color: rgba(161, 98, 7, 0.30);
  background: linear-gradient(180deg, rgba(161, 98, 7, 0.14), rgba(15, 23, 42, 0.92));
}


.topAlarm {
  background: rgb(56 29 40) !important;
  color: rgba(255, 255, 255, 0.95);
  border: 1px solid #6f2931 !important;
}

.topAck {
  background: rgba(161, 98, 7, 0.92);
  color: rgba(255, 255, 255, 0.95);
}

.cardTop {
  height: 50px;
  padding: 8px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  background: rgb(17 24 39 / 0.5);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.cardTitle {
  display: flex;
  align-items: center;
  min-width: 0;
  font-weight: 900;
  font-size: 16px;
  letter-spacing: 0.3px;
}

.wifiOk {
  color: #22c55e;
}

.wifiOff {
  color: #ef4444;
}

.cardMid {
  flex: 1 1 auto;
  min-height: 0;
  display: grid;
  place-items: center;
}

/* .statusCircle {
  width: 76px;
  height: 76px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: rgba(0, 0, 0, 0.16);
  border: 2px solid rgba(255, 255, 255, 0.12);
}

.statusCircle.isAlarm {
  border-color: rgba(239, 68, 68, 0.65);
  box-shadow: 0 0 18px rgba(239, 68, 68, 0.55);
  animation: alarmPulse 1.2s infinite;
} */

.roomCard.cardOn {
  border-color: rgba(239, 68, 68, 0.65);
  /* box-shadow: 0 0 18px rgba(239, 68, 68, 0.55); */
  animation: pulseOpacity 1.2s infinite;
}

@keyframes pulseOpacity {
  0% {
    opacity: 1;
  }

  50% {
    opacity: 0.6;
  }

  100% {
    opacity: 1;
  }
}

@keyframes alarmPulse {
  0% {
    transform: scale(1);
    box-shadow: 0 0 0 rgba(239, 68, 68, 0);
  }

  50% {
    transform: scale(1.08);
    box-shadow: 0 0 22px rgba(239, 68, 68, 0.6);
  }

  100% {
    transform: scale(1);
    box-shadow: 0 0 0 rgba(239, 68, 68, 0);
  }
}

.statusIcon {
  font-size: clamp(22px, calc(90vh / var(--grid-rows) / 1.6), 78px) !important;
}

.statusIcon.isOk {
  color: #22c55e;
}

.statusIcon.isAlarm {
  color: #ef4444;
}

.cardBottom {
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 10px;
  font-weight: 900;
}

.bottomOk {
  background: rgb(34 197 94 / var(--tw-bg-opacity, 1));
  color: rgba(255, 255, 255, 0.95);
  font-weight: normal;
}

.bottomAlarm {
  background: rgba(239, 68, 68, 0.85);
  color: rgba(255, 255, 255, 0.95);
}

.bottomAck {
  background: rgba(161, 98, 7, 0.92);
  color: rgba(255, 255, 255, 0.95);
}

.bottomText {
  font-size: 17px;
  ;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 900;
}

/* Notif drawer fixed 200px */
.notifDrawer {
  height: 100%;
  min-height: 0;
  width: 200px;
  border-left: 1px solid rgba(255, 255, 255, 0.10);
  background: rgba(0, 0, 0, 0.16);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.notifHeader {
  padding: 10px 10px 8px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.10);
  background: rgba(0, 0, 0, 0.14);
}

.notifHeaderTitle {
  font-weight: 900;
  letter-spacing: 0.4px;
  font-size: 12px;
  opacity: 0.92;
}

.notifBody {
  padding: 10px;
  overflow: auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.notifCard {
  border-radius: 12px;
  padding: 10px;
  border: 1px solid rgba(255, 255, 255, 0.10);
  background: rgba(255, 255, 255, 0.03);
}

.notifCard.new {
  background: rgba(239, 68, 68, 0.16);
  border-color: rgba(239, 68, 68, 0.22);
}

.notifCard.ack {
  background: rgba(161, 98, 7, 0.14);
  border-color: rgba(161, 98, 7, 0.22);
}

.notifTopRow {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.notifTitle {
  font-weight: 900;
  font-size: 12px;
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
  background: rgba(161, 98, 7, 0.22);
}

.notifSub2 {
  margin-top: 6px;
  font-size: 11px;
  opacity: 0.75;
  font-weight: 700;
  color: #897575;
}

.notifBtn {
  margin-top: 10px;
  width: 100%;
  border-radius: 10px !important;
  font-weight: 900 !important;
  border: 1px solid rgba(255, 255, 255, 0.16) !important;
  color: rgba(255, 255, 255, 0.92) !important;
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

/* Base (fallback) */
.statusCircle {
  width: 70px;
  height: 70px;
}

.statusIcon {
  font-size: 64px !important;
}

/* 4-way: biggest */
.cardsGrid.split-4 .statusCircle {
  width: 180px;
  height: 180px;
}

.cardsGrid.split-4 .statusIcon {
  font-size: 160px !important;
}

/* 8-way */
.cardsGrid.split-8 .statusCircle {
  width: 95px;
  height: 95px;
}

.cardsGrid.split-8 .statusIcon {
  font-size: 82px !important;
}

/* 16-way */
.cardsGrid.split-16 .statusCircle {
  width: 78px;
  height: 78px;
}

.cardsGrid.split-16 .statusIcon {
  font-size: 70px !important;
}

/* 32-way */
.cardsGrid.split-32 .statusCircle {
  width: 58px;
  height: 58px;
}

.cardsGrid.split-32 .statusIcon {
  font-size: 50px !important;
}

/* 64-way: smallest */
.cardsGrid.split-64 .statusCircle {
  width: 44px;
  height: 44px;
}

.cardsGrid.split-64 .statusIcon {
  font-size: 36px !important;
}
</style>
