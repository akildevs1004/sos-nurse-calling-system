<template>
  <div style="width: 100%; height: 190px">
    <!-- <ComonPreloader
      icon="face-scan"

      style="max-height: 180px"
      height="150px"
    /> -->
    <div v-if="loading" style="height: 180px; margin: auto; padding-top: 20%">
      Loading....
    </div>
    <VueGauge
      v-if="!loading"
      :options="VueGaugeoptions"
      :refid="name"
      :id="name"
      style="width: 200px; margin-top: 0px"
    />

    <div style="font-size: 12px; margin-top: -50px">
      Updated at : {{ temperature_date_time }}
    </div>
  </div>
</template>

<script>
// import VueGauge from "vue-gauge";
export default {
  props: [
    "name",
    "temperature_latest",
    "height",
    "branch_id",
    "temperature_date_time",
  ],
  // components: { VueGauge },
  data() {
    return {
      filterDeviceId: null,
      devices: [],
      loading: true,
      display_title: "Recent 7 days Attendance",
      date_from: "",
      date_to: "",
      VueGaugeoptions: {
        needleValue: 0,
        centralLabel: "0",
        hasNeedle: true,
        arcDelimiters: [23, 50, 99],

        //arcLabels: [23, 50, 99],
        arcColors: ["#008450", "#EFB700", "#B81D13"],
      },

      key: 1,
    };
  },
  watch: {},

  mounted() {
    setTimeout(() => {
      this.VueGaugeoptions = {
        needleValue: this.temperature_latest,
        centralLabel: this.temperature_latest + "°C",
        hasNeedle: true,
        arcDelimiters: [23, 50, 99],
        //arcLabels: [23, 50, 99],
        arcColors: ["#008450", "#EFB700", "#B81D13"],
        chartWidth: 350,
        label: {
          show: true, // Display labels
        },
      };

      this.loading = false;
    }, 1000);
  },
  created() {},

  methods: {},
};
</script>
