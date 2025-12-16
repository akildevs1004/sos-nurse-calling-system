<template>
  <div style="width: 100%; height: 200px">
    <div v-if="loading" style="height: 180px; margin: auto; padding-top: 20%">
      Loading...
    </div>
    <VueGauge
      v-else
      :options="gaugeOptions"
      :refid="name"
      :id="name"
      style="width: 300px; margin-top: 0px"
    />
    <v-row>
      <v-col class="text-center">
        <div style="font-size: 14px">
          <v-icon color="#FFF">mdi-clock-outline</v-icon> Updated at :
          {{ humidity_date_time }}
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script>
export default {
  props: ["name", "humidity_latest", "humidity_date_time"],
  data() {
    return {
      loading: true,
    };
  },
  computed: {
    gaugeOptions() {
      // return {
      //   hasNeedle: true,
      //   needleColor: "black",
      //   arcColors: [
      //     "rgb(255, 84, 84)",
      //     "rgb(239, 214, 19)",
      //     "rgb(61, 204, 91)",
      //   ],
      //   arcDelimiters: [40, 60],
      //   rangeLabel: ["52", "8"],
      //   needleStartValue: 50,
      //   chartWidth: 350,
      // };
      return {
        needleValue: this.humidity_latest,
        // centralLabel: `${this.humidity_latest}%`,
        hasNeedle: true,
        arcDelimiters: [60, 80, 99],
        arcColors: ["#0071bd", "#a9dcf4", "#cc86ec"],
        chartWidth: 350,
        needleColor: "#0071bd",
        arcOverEffect: true,
        top: true,
        label: { show: true },
        needleStartValue: 50,
      };
    },
  },
  mounted() {
    setTimeout(() => {
      this.loading = false;
    }, 500); // simulate delay only if needed
  },
};
</script>
