<template>
  <div style="width: 100%; height: 190px">
    <v-row v-if="loading" justify="center" align="center" style="height: 250px">
      <v-progress-circular indeterminate color="white" size="40" />
    </v-row>

    <VueGauge
      v-else
      :options="VueGaugeoptions"
      :refid="name"
      :id="name"
      style="width: 300px; margin: 0 auto"
    />

    <v-row>
      <v-col class="text-center">
        <div style="font-size: 14px">
          <v-icon color="#FFF">mdi-clock-outline</v-icon>
          {{ temperature_date_time }}
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script>
// import VueGauge from "vue-gauge";

export default {
  // components: { VueGauge },

  props: ["name", "temperature_latest", "temperature_date_time"],

  data() {
    return {
      loading: true,

      VueGaugeoptions: {
        needleValue: 0,
        // centralLabel: "0°C",
        hasNeedle: true,
        arcDelimiters: [23, 50, 99],
        arcColors: ["#008450", "#EFB700", "#B81D13"],
        chartWidth: 350,
        needleColor: "#FFFFFF",
        centralLabelStyle: {
          color: "#FFFFFF",
          fontSize: "20px",
          fontFamily: "Arial, sans-serif",
        },
        label: {
          show: true,
          color: "#FFFFFF",
        },
        arcLabelColor: "#FFFFFF",
        scaleLabelColor: "#FFFFFF",
      },
    };
  },

  watch: {
    temperature_latest(newVal) {
      this.updateGauge(newVal);
    },
  },

  mounted() {
    // Simulate loading delay
    setTimeout(() => {
      this.updateGauge(this.temperature_latest);
      this.loading = false;
    }, 1000);
  },

  methods: {
    updateGauge(value) {
      this.VueGaugeoptions = {
        ...this.VueGaugeoptions,
        needleValue: value,
        // centralLabel: value + "°C",
      };
    },
  },
};
</script>
