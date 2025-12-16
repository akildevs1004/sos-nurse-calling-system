<template>
  <div class="gauge-wrapper">
    <div ref="gauge" class="gauge-chart">
      <!-- <client-only>
        <apexchart
          type="radialBar"
          :options="chartOptions"
          :series="series"
          height="300"
        ></apexchart
      ></client-only> -->
      <div class="needle" :style="needleStyle"></div>
    </div>
  </div>
</template>

<script>
// import VueApexCharts from "vue-apexcharts";

export default {
  name: "GaugeWithNeedle",
  components: {
    // apexchart: VueApexCharts,
  },
  data() {
    return {
      series: [67], // Dynamic value (0–100)
      chartOptions: {
        chart: {
          type: "radialBar",
          offsetY: 0,
        },
        plotOptions: {
          radialBar: {
            startAngle: -90,
            endAngle: 90,
            track: {
              background: "#333",
              strokeWidth: "100%",
            },
            hollow: {
              margin: 0,
              size: "60%",
              background: "#fff",
            },
            dataLabels: {
              show: true,
              name: {
                show: false,
              },
              value: {
                fontSize: "28px",
                offsetY: 10,
                show: true,
              },
            },
          },
        },
        fill: {
          type: "gradient",
          gradient: {
            shade: "dark",
            type: "horizontal",
            gradientToColors: ["#87D4F9"],
            stops: [0, 100],
          },
        },
        stroke: {
          lineCap: "round",
        },
        labels: ["Progress"],
      },
    };
  },
  computed: {
    needleStyle() {
      const angle = (this.series[0] / 100) * 180 - 90; // -90 to +90
      return {
        position: "absolute",
        top: "50%",
        left: "50%",
        width: "2px",
        height: "90px",
        backgroundColor: "red",
        transform: `rotate(${angle}deg) translate(-50%, -100%)`,
        transformOrigin: "bottom center",
        zIndex: 10,
      };
    },
  },
};
</script>

<style scoped>
.gauge-wrapper {
  display: flex;
  justify-content: center;
}
.gauge-chart {
  position: relative;
  width: 300px;
  height: 300px;
}
.needle {
  transition: transform 0.5s ease;
}
</style>
