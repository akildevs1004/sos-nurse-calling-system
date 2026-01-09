<template>
  <div class="dashShell">
    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" elevation="24" class="center-snackbar">
      {{ snackbarResponse }}
    </v-snackbar>

    <SosAlarmPopupMqtt @triggerUpdateDashboard="requestDashboardSnapshot()" :isMQTT="isMQTT" />

    <!-- ===== BODY ===== -->
    <div class="dashBody" :class="{ hasNotif: activeNewAlarmRooms.length > 0 }" :style="dashBodyStyle">
      <!-- Left Rail (EXPAND ON HOVER) -->
      <aside class="rail" :class="{ expanded: railExpanded }" @mouseenter="railExpanded = true"
        @mouseleave="railExpanded = false">
        <div class="railPanel">
          <!-- <div class="railDivider"></div> -->

          <!-- Split Modes -->
          <button class="railItem" :class="{ active: splitMode === 4 }" @click="setSplit(4)" title="4-way">
            <v-icon class="railIcon">mdi-view-grid-outline</v-icon>
            <span class="railText">4-way Split</span>
          </button>

          <button class="railItem" :class="{ active: splitMode === 9 }" @click="setSplit(9)" title="9-way">
            <v-icon class="railIcon">mdi-view-module</v-icon>
            <span class="railText">9-way Split</span>
          </button>

          <button class="railItem" :class="{ active: splitMode === 12 }" @click="setSplit(12)" title="12-way">
            <v-icon class="railIcon">mdi-grid</v-icon>
            <span class="railText">12-way Split</span>
          </button>

          <button class="railItem" :class="{ active: splitMode === 16 }" @click="setSplit(16)" title="16-way">
            <v-icon class="railIcon">mdi-view-headline</v-icon>
            <span class="railText">16-way Split</span>
          </button>

          <button class="railItem" :class="{ active: splitMode === 35 }" @click="setSplit(35)" title="35-way">
            <v-icon class="railIcon">mdi-view-grid-compact</v-icon>
            <span class="railText">35-way Split</span>
          </button>

          <button class="railItem" :class="{ active: splitMode === 60 }" @click="setSplit(60)" title="60-way">
            <v-icon class="railIcon">mdi-view-grid-plus</v-icon>
            <span class="railText">60-way Split</span>
          </button>

          <div class="railDivider"></div>


          <button class="railItem" @click="toggleMute" :title="muted ? 'Unmute' : 'Mute'">
            <v-icon class="railIcon">{{ muted ? "mdi-volume-off" : "mdi-volume-high" }}</v-icon>
            <span class="railText">{{ muted ? "Muted" : "Sound On" }}</span>
          </button>

          <!-- TV only: Prev/Next + Logout -->
          <template>
            <button class="railItem" @click="prevPage" :disabled="totalPages <= 1" title="Prev">
              <v-icon class="railIcon">mdi-chevron-left</v-icon>
              <span class="railText">Prev</span>
            </button>

            <div class="railItem" style="display:grid">
              {{ pageIndex + 1 }}/{{ totalPages }}
            </div>

            <button class="railItem" @click="nextPage" :disabled="totalPages <= 1" title="Next">
              <v-icon class="railIcon">mdi-chevron-right</v-icon>
              <span class="railText">Next</span>
            </button>


          </template>

          <div class="railDivider"></div>

          <!-- Bell -->
          <button class="railItem" :class="{ blink: bellBlink }" :disabled="activeNewAlarmRooms.length === 0"
            @click="markBellSeen" title="NOTIFICATION ALERTS">
            <v-badge :content="activeNewAlarmRooms.length" :value="activeNewAlarmRooms.length > 0" overlap>
              <v-icon class="railIcon">mdi-bell-outline</v-icon>
            </v-badge>
            <span class="railText">Alerts</span>
          </button>

          <div class="railSpacer"></div>

          <button class="railItem" @click="logout" title="Logout">
            <v-icon class="railIcon">mdi-logout</v-icon>
            <span class="railText">Logout {{ offset }}</span>
          </button>
          <div class="railSpacer"></div>
          <button class="railItem railItem--img" title="Brand">
            <v-img class="railImg" src="/logo.png" contain eager />
            <span class="railText">XtremeGuard</span>
          </button>

          <!-- Desktop paging + logout -->
          <!-- <template v-if="!isTv">
            <div class="railDivider"></div>

            <button class="railItem" @click="prevPage" :disabled="totalPages <= 1" title="Prev">
              <v-icon class="railIcon">mdi-chevron-left</v-icon>
              <span class="railText">Prev</span>
            </button>

            <div class="railItem" style="display:grid">
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


          <div class="railSpacer"></div>


          <button class="railItem railItem--img" title="Brand">
            <v-img class="railImg" src="/logo.png" contain eager />
            <span class="railText">XtremeGuard</span>
          </button>
</template> -->
        </div>
      </aside>

      <!-- Main grid -->
      <main class="dashMain">
        <section class="dashCards">
          <div class="cardsGrid" :class="splitClass" :style="roomsGridStyle">
            <div v-for="(d, i) in gridItems" :key="d ? (d.id || d.room_id) : `blank-${i}`" class="cardCell">
              <v-card v-if="d" outlined class="roomCard"
                :class="[cardClass(d), { clickable: d.alarm_status && !d.alarm?.responded_datetime }]"
                @click="d.alarm_status && !d.alarm?.responded_datetime && udpateResponse(d.alarm?.id)">
                <div class="cardTop" :class="{
                  topAlarm: d.alarm_status && !d.alarm?.responded_datetime,
                  topAck: d.alarm_status && d.alarm?.responded_datetime,
                  topOk: !d.alarm_status
                }">
                  <div class="cardTitle">
                    <v-icon small class="mr-2" style="font-size: 30px;">
                      {{ isToilet(d) ? "mdi-toilet" : "mdi-bed" }}
                    </v-icon>
                    <span class="text-truncate">{{ (d.name || "ROOM").toUpperCase() }}</span>
                  </div>

                  <v-icon small :class="d.device?.status_id == 1 ? 'wifiOk' : 'wifiOff'">
                    {{ d.device?.status_id == 1 ? "mdi-wifi" : "mdi-wifi-off" }}
                  </v-icon>
                </div>

                <div class="cardMid">
                  <div class="statusCircle" :class="d.alarm_status ? 'isAlarm' : 'isOk'">
                    <v-icon medium class="statusIcon"
                      :class="d.alarm_status ? d.alarm.responded_datetime ? 'isAck' : 'isAlarm' : 'isOk'">
                      {{ d.alarm_status ? "mdi-close-circle" : "mdi-check-circle-outline" }}
                    </v-icon>
                  </div>
                </div>

                <div class="cardBottom" :class="{
                  bottomAlarm: d.alarm_status && !d.alarm?.responded_datetime,
                  bottomAck: d.alarm_status && d.alarm?.responded_datetime,
                  bottomOk: !d.alarm_status
                }">
                  <v-icon medium class="mr-2" :class="{
                    isAlarm: d.alarm_status && !d.alarm?.responded_datetime,
                    isAck: d.alarm_status && d.alarm?.responded_datetime,
                    isOk: !d.alarm_status
                  }">
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
                    <template v-else>No Active Alarm</template>
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
          <div class="notifHeaderTitle" style="padding-top:10px;">
            <!-- <span>{{ this.screenW }} {{ this.screenH }} </span> -->

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
            <div class="notifSub2">Start: {{ formatStartDate(r.alarm?.alarm_start_datetime || r.alarm_start_datetime) }}
            </div>

            <v-btn x-small class="notifBtn danger" :disabled="!!r.alarm?.responded_datetime"
              v-if="!r.alarm?.responded_datetime" @click="udpateResponse(r.alarm?.id)">
              ACKNOWLEDGEMENT
            </v-btn>
            <div style="height:30px;" v-if="!!r.alarm?.responded_datetime" @click="udpateResponse(r.alarm?.id)">
              &nbsp;
            </div>
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
  props: {
    offset: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      isMQTT: false,
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
    computed: {
      dashBodyStyle() {
        return {
          '--offset': `${Number(this.offset || 0)}px`
        };
      }
    },
    splitClass() {
      return `split-${this.splitMode}`; // split-4 / split-9 / split-12 / split-16 / split-35 / split-60
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
      return [4, 9, 12, 16, 35, 60].includes(s) ? s : 16;
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
      if (s === 9) return { cols: 3, rows: 3 };
      if (s === 12) return { cols: 3, rows: 4 };
      if (s === 16) return { cols: 4, rows: 4 };
      if (s === 35) return { cols: 7, rows: 5 };
      if (s === 60) return { cols: 10, rows: 6 }; // ✅ 10x6
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
      return (this.devices || []).filter(d => d && d.alarm_status === true);
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
    if ([4, 9, 12, 16, 35, 60].includes(savedSplit)) this.splitMode = savedSplit;

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

    // this.isTVUserAgent();
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
    // isTVUserAgent() {
    //   const ua = navigator.userAgent.toLowerCase();



    //   return (
    //     ua.includes("android tv") ||
    //     ua.includes("smart-tv") ||
    //     ua.includes("smarttv") ||
    //     ua.includes("googletv") ||
    //     ua.includes("hbbtv") ||
    //     ua.includes("tizen") ||
    //     ua.includes("webos") ||
    //     ua.includes("aft") // Amazon Fire TV
    //   );
    // },
    formatStartDate(value) {
      if (!value) return "-";

      const d = new Date(value);
      if (isNaN(d.getTime())) return "-";

      const time = d.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
        hour12: true
      });

      const day = d.getDate();
      const month = d.toLocaleString("en-US", { month: "short" });
      const year = d.getFullYear();

      const suffix =
        day % 10 === 1 && day !== 11 ? "st" :
          day % 10 === 2 && day !== 12 ? "nd" :
            day % 10 === 3 && day !== 13 ? "rd" : "th";

      return `${time} · ${day}${suffix} ${month} ${year}`;
    },
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

    // ========== MQTT ==========
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



      if (confirm("Are you sure you want to Acknowledge this alarm?")) {
        if (!alarmId) return;

        // stop sound immediately (TV + Desktop)
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

        setTimeout(() => this.requestDashboardSnapshot(), 400);

      }
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

/* Body layout */
.dashBody {
  height: calc(100vh - var(--offset, 0px));
  min-height: 0;
  display: grid;
  grid-template-columns: 60px 1fr;
  overflow: hidden;
}

:root {
  --offset: 100px;
}

/* .dashBody {
  height: 100%;
  min-height: 0;
  display: grid;
  grid-template-columns: 60px 1fr;
  overflow: hidden;
} */

.dashBody.hasNotif {
  grid-template-columns: 60px 1fr 300px;
}

/* Rail */
.rail {
  position: relative;
  width: 60px;
  /* fixed grid column */
  height: 100%;
  overflow: visible;
  z-index: 99999;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  background: transparent;
}

/* panel expands over content */
.railPanel {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;

  width: 200px;
  /* expanded width */
  padding: 10px 8px;
  display: flex;
  flex-direction: column;
  gap: 10px;

  background: rgba(0, 0, 0, 0.18);
  border-right: 1px solid rgba(255, 255, 255, 0.08);

  transform: translate3d(-140px, 0, 0);
  transition: transform 160ms ease;
  will-change: transform;
  z-index: 9999;
}

.rail.expanded .railPanel {
  transform: translate3d(0, 0, 0);
  background-color: #0a0e17;
}

/* buffer */
.rail::after {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  width: 10px;
  height: 100%;
}

/* item */
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

/* collapsed: show only right strip */
.rail:not(.expanded) .railItem {
  width: 50px;
  margin-left: auto;
  padding: 0 10px;
  gap: 0;
  justify-content: center;
}

.rail:not(.expanded) .railText {
  display: none;
}

/* icons */
.railIcon {
  min-width: 22px;
  font-size: 22px !important;
  color: currentColor !important;
}

/* image in rail item */
.railImg {
  width: 28px;
  height: 28px;
  flex: 0 0 28px;
  border-radius: 8px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.10);
}

.railImg :deep(.v-responsive),
.railImg :deep(.v-image),
.railImg :deep(.v-image__image),
.railImg :deep(.v-responsive__content) {
  width: 100% !important;
  height: 100% !important;
}

/* text */
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

/* Push bottom section down */
.railSpacer {
  flex: 1 1 auto;
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

/* Grid */
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

.roomCard.cardOn {
  border-color: rgba(239, 68, 68, 0.65);
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

/* Dynamic icon sizing by rows */
.statusIcon {
  font-size: clamp(22px, calc(90vh / var(--grid-rows) / 1.6), 78px) !important;
}

.statusIcon.isOk {
  color: #22c55e;
}

.statusIcon.isAlarm {
  color: #ef4444;
}

.statusIcon.isAck {
  color: #a16207;
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
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 900;
}

/* Notif drawer fixed 200px */
.notifDrawer {
  height: 100%;
  min-height: 0;
  width: 300px;
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
  padding: 14px;
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
  /* border-color: rgba(239, 68, 68, 0.22); */
  border-color: red;


}

.notifCard.ack {
  background: rgba(161, 98, 7, 0.14);
  /* border-color: rgba(161, 98, 7, 0.22); */
  border-color: rgb(245, 160, 4);

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
  color: #ffffff;
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

.notifBtn.danger {
  background: rgba(239, 68, 68, 0.85) !important;
  border-color: rgba(239, 68, 68, 0.85) !important;
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

  text-align: center;
}

.statusIcon {
  font-size: 64px !important;
}

/* Optional per-split sizing overrides */
.cardsGrid.split-4 .statusCircle {
  width: 180px;
  height: 180px;
}

.cardsGrid.split-4 .statusIcon {
  font-size: 160px !important;
}

.cardsGrid.split-9 .statusCircle {
  width: 120px;
  height: 120px;
}

.cardsGrid.split-9 .statusIcon {
  font-size: 100px !important;
}

.cardsGrid.split-12 .statusCircle {
  width: 95px;
  height: 95px;
}

.cardsGrid.split-12 .statusIcon {
  font-size: 82px !important;
}

.cardsGrid.split-16 .statusCircle {
  width: 78px;
  height: 78px;
}

.cardsGrid.split-16 .statusIcon {
  font-size: 70px !important;
}

.cardsGrid.split-35 .statusCircle {
  width: 56px;
  height: 56px;
}

.cardsGrid.split-35 .statusIcon {
  font-size: 48px !important;
}

.cardsGrid.split-60 .statusCircle {
  width: 44px;
  height: 44px;
}

.cardsGrid.split-60 .statusIcon {
  font-size: 36px !important;
}

/* =========================
   SPLIT-4: Bigger top/bottom
   ========================= */

/* Increase header/footer height ONLY for split-4 */
.cardsGrid.split-4 .cardTop {
  height: 100px !important;
  padding: 14px 14px !important;
}

.cardsGrid.split-4 .cardBottom {
  height: 100px !important;
  padding: 0 14px !important;
}

/* Increase title + bottom text sizes ONLY for split-4 */
.cardsGrid.split-4 .cardTitle {
  font-size: 28px !important;
}

.cardsGrid.split-4 .bottomText {
  font-size: 26px !important;
}

/* Optional: make icons in top/bottom slightly larger for split-4 */
.cardsGrid.split-4 .cardTop .v-icon {
  font-size: 42px !important;
}

.cardsGrid.split-4 .cardBottom .v-icon {
  font-size: 40px !important;
}

/* =========================
   ACKNOWLEDGED STATE (CENTER)
   ========================= */

.statusCircle.isAck {
  background: rgba(161, 98, 7, 0.92);
  border: 2px solid rgba(234, 179, 8, 0.75);
  box-shadow: 0 0 16px rgba(234, 179, 8, 0.55);
}

.roomCard.clickable {
  cursor: pointer;
}

.roomCard:not(.clickable) {
  cursor: default;
}
</style>

<style scoped>
@media (min-width: 500px) and (max-width: 1000px) {
  .cardsGrid.split-4 .cardTop {
    height: 60px !important;
    padding: 14px 14px !important;
  }

  .cardsGrid.split-4 .cardBottom {
    height: 60px !important;
    padding: 0 14px !important;
  }

  .notifDrawer {
    width: 200px;
  }

  .dashBody.hasNotif {
    grid-template-columns: 60px 1fr 200px;
  }

  .cardsGrid.split-4 .statusIcon {
    font-size: 100px !important;
  }


  .cardsGrid.split-9 .statusIcon {
    font-size: 80px !important;
  }

  .cardsGrid.split-12 .statusIcon {
    font-size: 45px !important;
  }

  .cardsGrid.split-16 .statusIcon {
    font-size: 40px !important;
  }

  .cardsGrid.split-35 .statusIcon {
    font-size: 20px !important;
  }

  .cardsGrid.split-35 .cardTop {
    height: 30px !important;
    padding: 14px 14px !important;
  }

  .cardsGrid.split-35 .cardBottom {
    height: 30px !important;
    padding: 0 14px !important;
  }


  .cardsGrid.split-60 .statusIcon {
    font-size: 18px !important;
  }

  .cardsGrid.split-60 .statusIcon {
    display: none !important;
  }
}
</style>

<style scoped>
/* =========================
   MOBILE (<500px): vertical list
   ========================= */


/* =========================
   MOBILE (<500px): show only Rail + Notifications
   ========================= */
@media (max-width: 499px) {

  /* One-column layout */
  /* .dashBody,
  .dashBody.hasNotif {
    grid-template-columns: 1fr !important;
  } */
  .notifDrawer {
    width: 100%;
    min-width: 350px !important;
  }

  /* Rail becomes normal (not overlay) */
  .rail {
    width: 100% !important;
    height: auto !important;
    border-right: 0 !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  }

  .railPanel {
    position: relative !important;
    width: 100% !important;
    transform: none !important;
    border-right: 0 !important;
  }

  /* HIDE cards area completely */
  .dashMain,
  .dashCards,
  .cardsGrid,
  .cardCell,
  .roomCard,
  .blankCell {
    display: none !important;
  }

  /* Notifications full width under rail */
  .notifDrawer {
    width: 100% !important;
    height: calc(100vh - 220px) !important;
    /* adjust if your rail height differs */
    border-left: 0 !important;
    border-top: 1px solid rgba(255, 255, 255, 0.10);
  }

  .notifBody {
    overflow: auto !important;
  }
}
</style>
