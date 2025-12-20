<template>
  <div style="width: 100%; height: 400px; padding: 10px">
    <v-row>
      <v-col cols="12">
        Temperature and Humidity History (Sample)
      </v-col>
    </v-row>

    <div :id="nameChart" style="width: 100%;" class="pt-2"></div>
  </div>
</template>

<script>
export default {
  props: {
    nameChart: {
      type: String,
      default: "tempHumChart",
    },
    height: {
      type: Number,
      default: 320,
    },
  },

  data() {
    return {
      chart: null,

      // ✅ Sample static series
      series: [
        {
          name: "Temperature",
          data: [26, 27, 28, 30, 29, 31, 32],
        },
        {
          name: "Humidity",
          data: [60, 62, 65, 63, 61, 64, 66],
        },
      ],

      // ✅ Sample dates
      categories: [
        "2024-01-01",
        "2024-01-02",
        "2024-01-03",
        "2024-01-04",
        "2024-01-05",
        "2024-01-06",
        "2024-01-07",
      ],
    };
  },

  mounted() {
    this.initChart();
  },

  beforeDestroy() {
    if (this.chart) {
      this.chart.destroy();
    }
  },

  methods: {
    initChart() {
      if (this.chart) this.chart.destroy();

      const options = {
        chart: {
          type: "area",
          height: this.height,
          toolbar: { show: false },
          animations: { enabled: false },
        },

        dataLabels: {
          enabled: false,
        },

        stroke: {
          curve: "smooth",
          width: 2,
        },

        fill: {
          type: "gradient",
          gradient: {
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [20, 100],
          },
        },

        colors: ["#ff4a4a", "#00C1D4"],

        xaxis: {
          type: "datetime",
          categories: this.categories,
        },

        tooltip: {
          shared: true,
          intersect: false,
          x: {
            format: "dd MMM yyyy",
          },
          y: {
            formatter: (val, opts) => {
              return opts.seriesIndex === 0
                ? `${val} °C`
                : `${val} %`;
            },
          },
        },

        legend: {
          position: "top",
          horizontalAlign: "right",
        },

        grid: {
          strokeDashArray: 3,
        },

        series: this.series,
      };

      this.chart = new ApexCharts(
        document.querySelector("#" + this.nameChart),
        options
      );

      this.chart.render();
    },
  },
};
</script>
