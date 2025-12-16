<template>
  <div style="width: 100%; position: relative">
    <div :id="name" ref="chartRef" style="width: 100%" class="pt-2"></div>
    <v-row>
      <v-col class="text-center">
        <div style="font-size: 14px">
          <v-icon color="default-font-color">mdi-clock-outline</v-icon> Updated
          at :
          {{ humidity_date_time }}
        </div>
      </v-col>
    </v-row>
  </div>
</template>

<script>
// import ApexCharts from 'apexcharts';

export default {
  props: [
    "name",
    "height",
    "branch_id",
    "device_serial_number",
    "from_date",
    "humidity_latest",
    "humidity_date_time",
  ],
  data() {
    return {
      series: [0],
      chart: null,
      interval: null,
    };
  },

  computed: {
    chartOptions() {
      const isDark = this.$vuetify.theme.dark;

      const textColor = isDark ? "#fff" : "#000";
      const trackColor = isDark ? "#333" : "#333";
      const fillColor = isDark ? "#2196F3" : "#2196F3";

      return {
        chart: {
          type: "radialBar",
          offsetY: -20,
          height: 300,
          animations: {
            enabled: true,
            easing: "easeinout",
            speed: 800,
          },
        },
        plotOptions: {
          radialBar: {
            startAngle: -135,
            endAngle: 135,
            track: {
              background: trackColor, // ← dynamic track color
              startAngle: -135,
              endAngle: 135,
            },
            dataLabels: {
              name: {
                show: false,
              },
              value: {
                fontSize: "30px",
                color: textColor, // ← dynamic text color
                show: true,
                formatter: function (val) {
                  return val + " %";
                },
              },
            },
          },
        },
        stroke: {
          lineCap: "butt",
        },
        labels: ["Humidity"],
        fill: {
          type: "solid",
          colors: [fillColor], // ← dynamic fill color
        },
      };
    },
  },
  watch: {
    "$vuetify.theme.dark"(val) {
      if (this.chart) {
        this.chart.updateOptions(this.chartOptions);
        this.getDataFromApi();
      }
    },
  },
  mounted() {
    // Set height from prop or default
    this.chartOptions.chart.height = Number(this.height) || 300;

    // Create the chart
    this.chart = new ApexCharts(this.$refs.chartRef, {
      ...this.chartOptions,
      series: this.series,
    });

    this.chart.render();

    // Initial temperature fetch
    this.getDataFromApi();

    // Update every 15 minutes
    // this.interval = setInterval(() => {
    //   this.getDataFromApi();
    // }, 1000 * 5);
  },
  beforeDestroy() {
    clearInterval(this.interval);
    if (this.chart) {
      this.chart.destroy();
    }
  },
  methods: {
    getGradientColors(temp) {
      temp = parseFloat(temp);
      if (temp < 50) {
        return {
          colors: ["#4FACFE"],
        };
      } else if (temp < 70) {
        return {
          colors: ["#ccff66"],
        };
      } else {
        return {
          colors: ["#FF512F"],
        };
      }
    },
    getGradientColors1(temp) {
      temp = parseFloat(temp);
      if (temp < 23) {
        return {
          type: "gradient",
          gradient: {
            shade: "light",
            type: "horizontal",
            gradientToColors: ["#4FACFE"],
            stops: [0, 100],
          },
        };
      } else if (temp < 27) {
        return {
          gradient: {
            shade: "light",
            type: "horizontal",
            gradientToColors: ["#ccff66"],
            stops: [0, 100],
          },
        };
        return {
          type: "gradient",
          gradient: {
            shade: "light",
            type: "horizontal",
            gradientToColors: ["#ccff66"], // Yellow-ish
            stops: [0, 100],
          },
        };
      } else {
        return {
          type: "gradient",
          gradient: {
            shade: "light",
            type: "horizontal",
            gradientToColors: ["#FF512F"], // Red-ish
            stops: [0, 100],
          },
        };
      }

      temp = parseFloat(temp);
      if (temp < 23) {
        return {
          type: "gradient",
          gradient: {
            shade: "dark",
            type: "horizontal",
            gradientToColors: ["#87D4F9"],

            stops: [0, 100],
          },
        };
      }
      if (temp < 27) {
        //yellow
        return {
          type: "gradient",
          gradient: {
            shade: "dark",
            type: "horizontal",
            gradientToColors: ["#87D4F9"],
            // colorStops: [
            //   { offset: 0, color: "#0000FF", opacity: 1 },
            //   { offset: 100, color: "#FFFF00", opacity: 1 },
            // ],
            // stops: [0, 100],
          },
        };
      }
      if (temp >= 27) {
        return {
          type: "gradient",
          gradient: {
            shade: "dark",
            type: "horizontal",
            gradientToColors: ["#87D4F9"],
            colorStops: [
              { offset: 0, color: "#0000FF", opacity: 1 },
              { offset: 50, color: "#0000FF", opacity: 1 },
              { offset: 100, color: "#FFFF00", opacity: 1 },
            ],
            stops: [0, 100],
          },
        };
      }
    },

    async getDataFromApi() {
      try {
        // Simulate a temperature between 18°C and 35°C
        const temp = this.humidity_latest; //(Math.random() * (100 - 50) + 30).toFixed(1);

        const gradientFill = this.getGradientColors(temp);

        // Update chart appearance
        this.chart.updateOptions({
          fill: gradientFill,
        });

        // Update chart value
        this.chart.updateSeries([parseFloat(temp)]);
      } catch (error) {
        console.error("Temperature update failed:", error);
      }
    },
  },
};
</script>

<style scoped>
/* Optional: add styling here */
</style>
