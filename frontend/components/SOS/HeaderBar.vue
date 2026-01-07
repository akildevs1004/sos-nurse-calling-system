<template>
  <header class="hdr" v-if="visible">
    <div class="hdrLeft">
      <v-avatar size="34" class="hdrLogo">
        <v-img :src="logoSrc" />
      </v-avatar>
      <div class="hdrTitle">
        <div class="hdrCompany text-truncate">{{ companyName }}</div>
        <div class="hdrSub text-truncate">{{ subtitle }}</div>
      </div>
    </div>

    <div class="hdrRight">
      <div class="hdrMeta">
        <v-icon small class="icoClock mr-1">mdi-clock-outline</v-icon>
        <span>{{ timeText }}</span>
      </div>

      <div class="hdrMeta">
        <v-icon small class="icoDate mr-1">mdi-calendar-month-outline</v-icon>
        <span>{{ dateText }}</span>
      </div>

      <div class="hdrMeta">
        <span class="dot" :class="isConnected ? 'ok' : 'bad'"></span>
        <span>{{ isConnected ? "Online" : "Offline" }}</span>
      </div>

      <!-- Mute/Unmute (no slider, no %). -->
      <v-btn x-small outlined class="btn" @click="$emit('toggleMute')">
        <v-icon left small>{{ muted ? "mdi-volume-off" : "mdi-volume-high" }}</v-icon>
        {{ muted ? "Mute" : "Sound On" }}
      </v-btn>

      <v-btn x-small outlined class="btn" @click="$emit('prevPage')" :disabled="disablePaging">
        <v-icon left small>mdi-chevron-left</v-icon>
      </v-btn>

      <div class="pageText">{{ pageText }}</div>

      <v-btn x-small outlined class="btn" @click="$emit('nextPage')" :disabled="disablePaging">
        <v-icon right small>mdi-chevron-right</v-icon>
      </v-btn>

      <v-btn small outlined color="error" class="btn" v-if="showLogout" @click="$emit('logout')">
        <v-icon left small>mdi-logout</v-icon>
        Logout
      </v-btn>
    </div>
  </header>
</template>

<script>
export default {
  name: "HeaderBar",
  props: {
    visible: { type: Boolean, default: true },
    companyName: { type: String, default: "Company" },
    subtitle: { type: String, default: "MONITORING DASHBOARD" },
    logoSrc: { type: String, default: "/logo.png" },

    isConnected: { type: Boolean, default: false },

    timeText: { type: String, default: "" },
    dateText: { type: String, default: "" },

    muted: { type: Boolean, default: false },

    pageText: { type: String, default: "1 / 1" },
    disablePaging: { type: Boolean, default: true },

    showLogout: { type: Boolean, default: false }
  }
};
</script>

<style scoped>
.hdr {
  height: 54px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 10px 14px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.14);
}

.hdrLeft {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.hdrLogo {
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.hdrTitle {
  min-width: 0;
}

.hdrCompany {
  font-weight: 900;
  font-size: 13px;
  letter-spacing: .4px;
}

.hdrSub {
  opacity: .7;
  font-size: 11px;
  margin-top: 2px;
}

.hdrRight {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.hdrMeta {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  opacity: .92;
  font-size: 12px;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 99px;
  display: inline-block;
}

.dot.ok {
  background: #22c55e;
}

.dot.bad {
  background: #ef4444;
}

.icoClock {
  color: rgba(99, 102, 241, 0.95) !important;
}

.icoDate {
  color: rgba(34, 197, 94, 0.95) !important;
}

.btn {
  border-color: rgba(255, 255, 255, 0.25) !important;
}

.pageText {
  min-width: 48px;
  text-align: center;
  font-weight: 900;
  opacity: .85;
}
</style>
