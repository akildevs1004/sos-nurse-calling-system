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
          <div class="hdSub text-truncate">{{ $auth?.user?.company?.name || "Company" }}</div>
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

        <v-btn x-small outlined class="hdBtn" @click="toggleMute">
          <v-icon left small>{{ muted ? "mdi-volume-off" : "mdi-volume-high" }}</v-icon>
          {{ muted ? "Mute" : "Sound On" }}
        </v-btn>
      </div>
    </header> -->

    <!-- ===== BODY: Rail + Grid + NotifDrawer(200px only when alarms > 0) ===== -->
    <div class="dashBody" :class="{ hasNotif: activeAlarmRooms.length > 0 }">
      <!-- Left Rail -->
      <aside class="rail">
        <!-- Split buttons -->
        <v-btn icon class="railBtn" :class="{ active: splitMode === 4 }" @click="setSplit(4)" title="4-way">
          <v-icon>mdi-view-grid</v-icon>
        </v-btn>
        <v-btn icon class="railBtn" :class="{ active: splitMode === 8 }" @click="setSplit(8)" title="8-way">
          <v-icon>mdi-view-grid-plus</v-icon>
        </v-btn>
        <v-btn icon class="railBtn" :class="{ active: splitMode === 16 }" @click="setSplit(16)" title="16-way">
          <v-icon>mdi-view-module</v-icon>
        </v-btn>
        <v-btn icon class="railBtn" :class="{ active: splitMode === 32 }" @click="setSplit(32)" title="32-way">
          <v-icon>mdi-view-comfy</v-icon>
        </v-btn>
        <v-btn icon class="railBtn" :class="{ active: splitMode === 64 }" @click="setSplit(64)" title="64-way">
          <v-icon>mdi-view-dashboard</v-icon>
        </v-btn>

        <div class="railDivider"></div>

        <!-- Mute/Unmute in navigation list -->
        <v-btn icon class="railBtn" @click="toggleMute" :title="muted ? 'Unmute' : 'Mute'">
          <v-icon>{{ muted ? "mdi-volume-off" : "mdi-volume-high" }}</v-icon>
        </v-btn>

        <!-- TV: Prev/Next + Logout in rail -->
        <template v-if="isTv">
          <v-btn icon class="railBtn" @click="prevPage" :disabled="totalPages <= 1" title="Prev">
            <v-icon>mdi-chevron-left</v-icon>
          </v-btn>
          <v-btn icon class="railBtn" @click="nextPage" :disabled="totalPages <= 1" title="Next">
            <v-icon>mdi-chevron-right</v-icon>
          </v-btn>

          <v-btn icon class="railBtn" v-if="$auth?.user" @click="logout" title="Logout">
            <v-icon>mdi-logout</v-icon>
          </v-btn>
        </template>

        <div class="railDivider"></div>

        <!-- Bell (blink until user clicks) -->
        <v-btn icon class="railBtn" :class="{ blink: bellBlink }" :disabled="activeAlarmRooms.length === 0"
          @click="markBellSeen" title="Notification  Alerts">
          <v-badge :content="activeAlarmRooms.length" :value="activeAlarmRooms.length > 0" overlap>
            <v-icon>mdi-bell-outline</v-icon>
          </v-badge>
        </v-btn>

        <!-- Desktop paging (rail) -->
        <template v-if="!isTv">
          <div class="railDivider"></div>

          <v-btn icon class="railBtn" @click="prevPage" :disabled="totalPages <= 1" title="Prev">
            <v-icon>mdi-chevron-left</v-icon>
          </v-btn>

          <div class="railPageText">{{ pageIndex + 1 }}/{{ totalPages }}</div>

          <v-btn icon class="railBtn" @click="nextPage" :disabled="totalPages <= 1" title="Next">
            <v-icon>mdi-chevron-right</v-icon>
          </v-btn>

          <v-btn icon class="railBtn" v-if="$auth?.user" @click="logout" title="Logout">
            <v-icon>mdi-logout</v-icon>
          </v-btn>
        </template>
      </aside>

      <!-- Main (grid only) -->
      <main class="dashMain">
        <section class="dashCards">
          <!-- ✅ FIXED ROW COUNT PER SPLIT, FULL HEIGHT ALWAYS -->
          <div class="cardsGrid" :style="roomsGridStyle">
            <div v-for="(d, i) in gridItems" :key="d ? (d.id || d.room_id) : `blank-${i}`" class="cardCell">
              <v-card v-if="d" outlined class="roomCard" :class="cardClass(d)">
                <div class="cardTop">
                  <div class="cardTitle">
                    <v-icon small class="mr-1">{{ isToilet(d) ? "mdi-toilet" : "mdi-bed" }}</v-icon>
                    <span class="text-truncate">{{ (d.name || "ROOM").toUpperCase() }}</span>
                  </div>

                  <v-icon small :class="d.device?.status_id == 1 ? 'wifiOk' : 'wifiOff'">
                    {{ d.device?.status_id == 1 ? "mdi-wifi" : "mdi-wifi-off" }}
                  </v-icon>
                </div>

                <div class="cardMid">
                  <div class="statusCircle" :class="d.alarm_status ? 'isAlarm' : 'isOk'">
                    <v-icon class="statusIcon" :class="d.alarm_status ? 'isAlarm' : 'isOk'">
                      {{ d.alarm_status ? "mdi-close-circle" : "mdi-check-circle" }}
                    </v-icon>
                  </div>
                </div>

                <div class="cardBottom" :class="{
                  bottomAlarm: d.alarm_status && !d.alarm?.responded_datetime,
                  bottomAck: d.alarm_status && d.alarm?.responded_datetime,
                  bottomOk: !d.alarm_status
                }">
                  <v-icon x-small class="mr-2">
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

              <!-- Placeholder (keeps fixed rows/height division) -->
              <div v-else class="blankCell"></div>
            </div>
          </div>

          <v-progress-linear v-if="loading" indeterminate height="3" class="mt-3" />
          <div v-else-if="pagedDevices.length === 0" class="emptyState">Data is not available</div>
        </section>

        <!-- Desktop sound: use AudioSoundPlay only when NOT TV -->
        <AudioSoundPlay v-if="!isTv && stats.activeSos > 0 && !muted" :key="totalSOSCount"
          :notificationsMenuItemsCount="stats.activeSos" />
      </main>

      <!-- Notifications Drawer: fixed 200px, visible ONLY when alarms > 0 -->
      <aside v-if="activeAlarmRooms.length > 0" class="notifDrawer">
        <div class="notifHeader">
          <div class="notifHeaderTitle">NOTIFICATION QUEUE FOR ALERTS ({{ activeAlarmRooms.length }})</div>
        </div>

        <div class="notifBody">
          <div v-for="r in activeAlarmRooms" :key="r.id || r.room_id" class="notifCard" :class="notifKind(r)">
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
              SUBMIT ACKNOWLEDGEMENT
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
  layout: "tvmonitorlayout",
  name: "TvSosFloor",
  components: { AudioSoundPlay },

  data() {
    return {
      // UI
      splitMode: 16,
      pageIndex: 0,
      muted: false,

      // Data
      devices: [],
      loading: false,

      snackbar: false,
      snackbarResponse: "",

      // Screen size
      screenW: 0,
      screenH: 0,

      // Header clock
      headerTime: "",
      headerDate: "",
      _clock: null,

      // MQTT
      mqttUrl: "",
      client: null,
      isConnected: false,
      topics: { req: "", rooms: "", stats: "", reload: "", reloadconfig: "" },

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

      autoRotateMs: 5000,
      autoRotateTimer: null,
    };
  },

  computed: {
    // TV means width 0 OR between 500..1000
    isTv() {
      const w = Number(this.screenW || 0);
      if (w === 0) return true;
      return w >= 500 && w <= 1000;
    },

    getLogo() {
      return this.$auth?.user?.company?.logo || "/logo.png";
    },

    // Pagination
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

    // ✅ fixed grid geometry per split (exactly as you requested)
    splitGrid() {
      const s = Number(this.splitMode);
      if (s === 4) return { cols: 2, rows: 2 };     // Height / 2
      if (s === 8) return { cols: 3, rows: 3 };     // Height / 3
      if (s === 16) return { cols: 4, rows: 4 };    // Height / 4
      if (s === 32) return { cols: 7, rows: 5 };    // Height / 5
      if (s === 64) return { cols: 12, rows: 6 };   // Height / 6
      return { cols: 4, rows: 4 };
    },

    gridSlots() {
      return this.splitGrid.cols * this.splitGrid.rows;
    },

    // ✅ render placeholders so row height ALWAYS divides correctly
    gridItems() {
      const items = (this.pagedDevices || []).slice(0, this.gridSlots);
      const blanks = Math.max(0, this.gridSlots - items.length);
      for (let i = 0; i < blanks; i++) items.push(null);
      return items;
    },

    roomsGridStyle() {
      return {
        gridTemplateColumns: `repeat(${this.splitGrid.cols}, minmax(0, 1fr))`,
        gridTemplateRows: `repeat(${this.splitGrid.rows}, minmax(0, 1fr))`
      };
    },

    activeAlarmRooms() {
      return (this.devices || []).filter((d) => d && d.alarm_status === true);
    },

    bellBlink() {
      return this.activeAlarmRooms.length > 0 && !this.bellSeen;
    },

    activeSosCount() {
      return this.activeAlarmRooms.length;
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

    const savedSplit = Number(this.safeLsGet(SPLIT_MODE_KEY));
    if ([4, 8, 16, 32, 64].includes(savedSplit)) this.splitMode = savedSplit;
  },

  mounted() {
    this.readScreen();
    window.addEventListener("resize", this.readScreen);

    this.updateHeaderClock();
    this._clock = setInterval(() => this.updateHeaderClock(), 1000);

    this.timer = setInterval(() => this.updateDurationAll(), this.TIMER_MS);

    this.mqttUrl = process.env.MQTT_SOCKET_HOST;
    this.connectMqtt();

    this.startAutoRotate();
  },

  beforeDestroy() {
    window.removeEventListener("resize", this.readScreen);

    try {
      if (this._clock) clearInterval(this._clock);
      if (this.timer) clearInterval(this.timer);
      this.disconnectMqtt();
    } catch (e) { }

    this.stopAutoRotate();

  },

  methods: {
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
      // only rotate if there is more than 1 page
      if (this.totalPages <= 1) return;
      this.pageIndex = (this.pageIndex + 1) % this.totalPages;
    },
    notifKind(r) {
      if (r?.alarm_status === true && r?.alarm?.responded_datetime) return "ack";
      if (r?.alarm_status === true) return "new";
      return "stop";
    },

    markBellSeen() {
      this.bellSeen = true;
    },

    toggleMute() {
      this.muted = !this.muted;

      // TV: AndroidBridge
      if (this.isTv) {
        try {
          if (this.muted) AndroidBridge.stopAlarm();
          else if (this.activeSosCount > 0) AndroidBridge.startAlarm();
        } catch (e) { }
      }
    },

    setSplit(n) {
      this.splitMode = n;
      this.pageIndex = 0;
      this.safeLsSet(SPLIT_MODE_KEY, String(n));
      this.restartAutoRotate();
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

    logout() {
      this.$router.push("/logout");
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

    // ===== SAFE LOCALSTORAGE =====
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

    // ===== MQTT =====
    connectMqtt() {
      if (this.client) return;

      if (!this.mqttUrl) {
        this.snackbar = true;
        this.snackbarResponse = "MQTT_SOCKET_HOST missing in env";
        return;
      }

      const companyId = this.$auth?.user
        ? this.$auth?.user?.company_id
        : Number(process.env.TV_COMPANY_ID || 0);

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
        this.client.end(true);
        this.client = null;
        this.isConnected = false;
      } catch (e) { }
    },

    requestDashboardSnapshot() {
      if (!this.client || !this.isConnected) return;

      const companyId = this.$auth?.user
        ? this.$auth?.user?.company_id
        : Number(process.env.TV_COMPANY_ID || 0);

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
            if (this.muted) {
              AndroidBridge.stopAlarm();
            } else if (this.activeSosCount > 0) {
              AndroidBridge.startAlarm();
            } else {
              AndroidBridge.stopAlarm();
            }
          } catch (e) { }
        }

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

        // TV sound control (safe)
        if (this.isTv) {
          try {
            if (this.muted) {
              AndroidBridge.stopAlarm();
            } else if (this.activeSosCount > 0) {
              AndroidBridge.startAlarm();
            } else {
              AndroidBridge.stopAlarm();
            }
          } catch (e) { }
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

    udpateResponse(alarmId) {
      if (!alarmId) return;

      const companyId = this.$auth?.user
        ? this.$auth?.user?.company_id
        : Number(process.env.TV_COMPANY_ID || 0);

      const payload = {
        reqId: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
        params: { company_id: companyId, alarmId }
      };

      try {
        if (this.client && this.isConnected) {
          this.client.publish(`tv/${companyId}/dashboard_alarm_response`, JSON.stringify(payload), {
            qos: 0,
            retain: false
          });
        }
      } catch (e) { }

      this.requestDashboardSnapshot();
    }
  }
};
</script>

<style scoped>
/* Root */
.dashShell {
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

.hdTitleBlock {
  min-width: 0;
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

.hdBtn {
  border-color: rgba(255, 255, 255, 0.25) !important;
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
.rail {
  height: 100%;
  min-height: 0;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.18);
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 10px 6px;
  gap: 10px;
  overflow: hidden;
}

.railBtn {
  width: 44px !important;
  height: 44px !important;
  border-radius: 12px !important;
  border: 1px solid rgba(255, 255, 255, 0.10) !important;
  background: rgba(255, 255, 255, 0.04) !important;
}

.railBtn.active {
  background: rgba(99, 102, 241, 0.35) !important;
  border-color: rgba(99, 102, 241, 0.45) !important;
}

.railDivider {
  width: 42px;
  height: 1px;
  background: rgba(255, 255, 255, 0.10);
  margin: 4px 0;
}

.railPageText {
  font-size: 11px;
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

/* Grid FULL HEIGHT, fixed rows per split */
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

/* Card fills cell, NO MIN HEIGHT */
.roomCard {
  height: 100% !important;
  min-height: 0 !important;
  max-height: 100%;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: linear-gradient(180deg, rgba(30, 41, 59, 0.92), rgba(15, 23, 42, 0.92));
  box-shadow: 0 14px 34px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.03);
  display: flex;
  flex-direction: column;
}

/* Ensure Vuetify doesn't inject min-height */
.v-card {
  min-height: 0 !important;
}

/* Placeholder cell */
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

.cardTop {
  height: 36px;
  padding: 8px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  background: rgba(255, 255, 255, 0.03);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.cardTitle {
  display: flex;
  align-items: center;
  min-width: 0;
  font-weight: 900;
  font-size: 13px;
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

.statusCircle {
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
  font-size: 54px !important;
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
  background: rgba(34, 197, 94, 0.90);
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
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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
</style>
