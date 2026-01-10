<template>
  <div>
    <!-- Local audio from /public/alarm_sounds/alarm-sound1.mp3 -->
    <audio ref="audioPlayer" :src="audioFile" preload="auto" loop="true"></audio>
  </div>
</template>

<script>
export default {
  name: "AlarmAudioPlayer",

  props: {
    notificationsMenuItemsCount: {
      type: [Number, String],
      default: 0,
    },
  },

  data() {
    return {
      // Put file here: public/alarm_sounds/alarm-sound1.mp3
      audioFile: "/alarm_sounds/alarm-sound1.mp3",

      isPlaying: false,
      playDelayTimer: null,

      // Helps with autoplay restrictions (Chrome/WebView)
      userGestureArmed: false,
    };
  },

  watch: {
    notificationsMenuItemsCount: {
      immediate: true,
      handler(newVal) {
        const count = this.toCount(newVal);

        if (count > 0) {
          this.playAudio();
        } else {
          this.stopAudio();
        }
      },
    },
  },

  mounted() {
    // Arm after first user interaction (click/tap/remote key)
    const arm = () => {
      this.userGestureArmed = true;

      window.removeEventListener("click", arm);
      window.removeEventListener("touchstart", arm);
      window.removeEventListener("keydown", arm);

      // If notifications already exist, play immediately once armed
      if (this.toCount(this.notificationsMenuItemsCount) > 0) {
        this.playAudio(true); // force immediate try
      }
    };

    window.addEventListener("click", arm);
    window.addEventListener("touchstart", arm);
    window.addEventListener("keydown", arm);

    // Safety: if audio ends (when loop is off), keep state correct
    const audio = this.$refs.audioPlayer;
    if (audio) {
      audio.addEventListener("ended", () => {
        this.isPlaying = false;
      });
    }


    // this.playAudio();
  },

  beforeUnmount() {
    clearTimeout(this.playDelayTimer);
  },

  methods: {
    toCount(val) {
      const n = parseInt(val, 10);
      return Number.isFinite(n) ? n : 0;
    },

    async tryPlay(audio) {
      try {
        await audio.play();
        this.isPlaying = true;
      } catch (err) {
        this.isPlaying = false;
        console.error("Audio playback blocked or failed:", err);
      }
    },

    playAudio(forceImmediate = false) {
      const audio = this.$refs.audioPlayer;
      if (!audio) return;
      audio.play().catch(() => { });
      clearTimeout(this.playDelayTimer);

      // Keep alarming continuously while notifications > 0
      audio.loop = true;


      // Optional: restart from beginning on each play trigger
      audio.currentTime = 0;

      // If your environment blocks autoplay, you can require userGestureArmed
      // If you want it to attempt anyway (TV WebView often allows), keep as-is.
      const delayMs = forceImmediate ? 0 : 5000;

      this.playDelayTimer = setTimeout(() => {
        // If you want strict gesture gating, uncomment:
        // if (!this.userGestureArmed) return;

        this.tryPlay(audio);
      }, delayMs);
    },

    pauseAudio() {
      const audio = this.$refs.audioPlayer;
      if (!audio) return;

      audio.pause();
      audio.currentTime = 0;
      audio.loop = false;
      this.isPlaying = false;
    },

    stopAudio() {
      const audio = this.$refs.audioPlayer;
      if (!audio) return;

      clearTimeout(this.playDelayTimer);

      audio.pause();
      audio.currentTime = 0;
      audio.loop = false;

      this.isPlaying = false;
    },
  },
};
</script>

<style scoped>
/* No UI controls needed */
</style>
