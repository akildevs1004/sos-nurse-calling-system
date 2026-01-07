<template>
  <div class="dashBody" :class="{ hasNotif: (activeAlarmRooms || []).length > 0 }">
    <!-- Left Rail -->
    <aside class="rail">
      <!-- Split -->
      <v-btn icon class="railBtn" :class="{ active: splitMode === 4 }" @click="$emit('setSplit', 4)" title="4-way">
        <v-icon>mdi-view-grid</v-icon>
      </v-btn>
      <v-btn icon class="railBtn" :class="{ active: splitMode === 8 }" @click="$emit('setSplit', 8)" title="8-way">
        <v-icon>mdi-view-grid-plus</v-icon>
      </v-btn>
      <v-btn icon class="railBtn" :class="{ active: splitMode === 16 }" @click="$emit('setSplit', 16)" title="16-way">
        <v-icon>mdi-view-module</v-icon>
      </v-btn>
      <v-btn icon class="railBtn" :class="{ active: splitMode === 32 }" @click="$emit('setSplit', 32)" title="32-way">
        <v-icon>mdi-view-comfy</v-icon>
      </v-btn>
      <v-btn icon class="railBtn" :class="{ active: splitMode === 64 }" @click="$emit('setSplit', 64)" title="64-way">
        <v-icon>mdi-view-dashboard</v-icon>
      </v-btn>

      <div class="railDivider"></div>

      <!-- Mute/Unmute -->
      <v-btn icon class="railBtn" @click="$emit('toggleMute')" :title="muted ? 'Unmute' : 'Mute'">
        <v-icon>{{ muted ? "mdi-volume-off" : "mdi-volume-high" }}</v-icon>
      </v-btn>

      <!-- TV: Prev/Next + Logout -->
      <template v-if="isTv">
        <v-btn icon class="railBtn" @click="$emit('prevPage')" :disabled="totalPages <= 1" title="Prev">
          <v-icon>mdi-chevron-left</v-icon>
        </v-btn>
        <v-btn icon class="railBtn" @click="$emit('nextPage')" :disabled="totalPages <= 1" title="Next">
          <v-icon>mdi-chevron-right</v-icon>
        </v-btn>

        <v-btn icon class="railBtn" @click="$emit('logout')" title="Logout">
          <v-icon>mdi-logout</v-icon>
        </v-btn>
      </template>

      <div class="railDivider"></div>

      <!-- Bell (blink until click) -->
      <v-btn icon class="railBtn" :class="{ blink: bellBlink }" :disabled="(activeAlarmRooms || []).length === 0"
        @click="$emit('bellSeen')" title="NOTIFICATION QUEUE FOR ALERTS">
        <v-badge :content="(activeAlarmRooms || []).length" :value="(activeAlarmRooms || []).length > 0" overlap>
          <v-icon>mdi-bell-outline</v-icon>
        </v-badge>
      </v-btn>

      <!-- Desktop: Prev/Next + Logout -->
      <template v-if="!isTv">
        <div class="railDivider"></div>

        <v-btn icon class="railBtn" @click="$emit('prevPage')" :disabled="totalPages <= 1" title="Prev">
          <v-icon>mdi-chevron-left</v-icon>
        </v-btn>

        <div class="railPageText">{{ pageIndex + 1 }}/{{ totalPages }}</div>

        <v-btn icon class="railBtn" @click="$emit('nextPage')" :disabled="totalPages <= 1" title="Next">
          <v-icon>mdi-chevron-right</v-icon>
        </v-btn>

        <v-btn icon class="railBtn" @click="$emit('logout')" title="Logout">
          <v-icon>mdi-logout</v-icon>
        </v-btn>
      </template>
    </aside>

    <!-- Main Grid -->
    <main class="dashMain">
      <section class="dashCards">
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

            <div v-else class="blankCell"></div>
          </div>
        </div>
      </section>
    </main>

    <!-- Notification Queue (fixed 200px, only when alarms exist) -->
    <aside v-if="(activeAlarmRooms || []).length > 0" class="notifDrawer">
      <div class="notifHeader">
        <div class="notifHeaderTitle">
          NOTIFICATION QUEUE FOR ALERTS ({{ (activeAlarmRooms || []).length }})
        </div>
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
            @click="$emit('ack', r.alarm?.id)">
            SUBMIT ACKNOWLEDGEMENT
          </v-btn>
        </div>
      </div>
    </aside>
  </div>
</template>

<script>
export default {
  name: "SosMonitorBody",
  props: {
    isTv: { type: Boolean, default: false },
    splitMode: { type: Number, default: 16 },
    pageIndex: { type: Number, default: 0 },
    totalPages: { type: Number, default: 1 },
    devices: { type: Array, default: () => [] },
    pagedDevices: { type: Array, default: () => [] },
    activeAlarmRooms: { type: Array, default: () => [] },
    muted: { type: Boolean, default: false },
    bellBlink: { type: Boolean, default: false }
  },

  computed: {
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

    // pad with blanks to keep full-height division perfect
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
    }
  },

  methods: {
    notifKind(r) {
      if (r?.alarm_status === true && r?.alarm?.responded_datetime) return "ack";
      if (r?.alarm_status === true) return "new";
      return "stop";
    },

    isToilet(d) {
      return d?.room_type === "toilet" || d?.room_type === "toilet-ph";
    },

    cardClass(d) {
      if (!d) return "";
      if (d.alarm_status === true && !d.alarm?.responded_datetime) return "cardOn";
      if (d.alarm_status === true && d.alarm?.responded_datetime) return "cardAck";
      return "cardOff";
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
    }
  }
};
</script>

<style scoped>
.dashBody {
  height: 100vh;
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

/* Main grid */
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
  max-height: 100%;
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

/* Notification queue */
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
</style>
