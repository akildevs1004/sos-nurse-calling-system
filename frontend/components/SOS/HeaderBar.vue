<template>
  <header class="dashHeader">
    <div class="hdLeft">
      <v-avatar size="34" class="hdLogo">
        <v-img :src="logo" />
      </v-avatar>

      <div class="hdTitleBlock">
        <div class="hdSub text-truncate">{{ companyName }}</div>
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

      <v-btn x-small outlined class="hdBtn" @click="$emit('toggleMute')">
        <v-icon left small>{{ muted ? "mdi-volume-off" : "mdi-volume-high" }}</v-icon>
        {{ muted ? "Mute" : "Sound On" }}
      </v-btn>
    </div>
  </header>
</template>

<script>
export default {
  name: "SosMonitorHeader",
  props: {
    logo: { type: String, default: "/logo.png" },
    companyName: { type: String, default: "Company" },
    headerTime: { type: String, default: "" },
    headerDate: { type: String, default: "" },
    isConnected: { type: Boolean, default: false },
    muted: { type: Boolean, default: false }
  }
};
</script>

<style scoped>
.dashHeader {
  height: 54px;
  padding: 10px 14px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.14);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  color: rgba(255, 255, 255, 0.92);
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

.hdBtn {
  border-color: rgba(255, 255, 255, 0.25) !important;
}
</style>
