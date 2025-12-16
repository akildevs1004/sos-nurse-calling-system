<template>
  <div style="width: 100%; height: 380px">
    <v-row>
      <v-col>Today - Temperature and Humidity Chart</v-col>
    </v-row>
    <div :id="name" style="width: 100%; margin-top: 0px" class="pt-2"></div>
  </div>
</template>

<script>
// import VueApexCharts from 'vue-apexcharts'
export default {
  props: [
    "name",
    "height",
    "branch_id",
    "device_serial_number",
    "from_date",
    "theme",
    "device_temperature_serial_address",
  ],
  data() {
    return {
      key: 1,
      series: [
        {
          name: "Temperature",
          type: "column",
          data: [], // Your temperature data will go here
          color: "#ff4a4a", // Fixed teal for temperature
        },
        {
          name: "Humidity",
          type: "column",
          data: [],
          color: "#00C1D4", // Fixed orange for humidity
        },
      ],

      // chartOptions2: {
      //   chart: {
      //     type: "line",
      //     height: 350,
      //     background: "#2a3453",
      //     foreColor: "#E0E0E0",
      //     toolbar: { show: false },
      //     stacked: false,
      //     animations: {
      //       enabled: true,
      //       easing: "easeinout",
      //       speed: 500,
      //     },
      //   },
      //   colors: ["#00C1D4", "#FFFFFF"], // Teal for columns, white for trend line
      //   stroke: {
      //     width: [0, 0], // No border for columns, thicker line for trend
      //     curve: "smooth",
      //   },
      //   title: {
      //     text: "Temperature Hourly Chart",
      //     align: "left",
      //     style: {
      //       color: "#FFFFFF",
      //       fontSize: "16px",
      //     },
      //   },
      //   dataLabels: {
      //     enabled: false,
      //   },
      //   plotOptions: {
      //     bar: {
      //       horizontal: false,
      //       columnWidth: "25%",
      //       endingShape: "rounded",
      //     },
      //   },
      //   xaxis: {
      //     type: "category",
      //     categories: [], // Your hour labels will go here
      //     labels: {
      //       style: {
      //         colors: "#B0B0B0", // Light gray labels
      //       },
      //     },
      //     axisBorder: {
      //       show: false,
      //     },
      //     axisTicks: {
      //       show: false,
      //     },
      //   },
      //   yaxis: {
      //     labels: {
      //       style: {
      //         colors: "#B0B0B0", // Light gray labels
      //       },
      //       formatter: function (val) {
      //         return val + "°C"; // Add degree symbol
      //       },
      //     },
      //     min: 0,
      //     max: 40, // Adjust based on your expected temperature range
      //   },
      //   grid: {
      //     borderColor: "#2A2E34",
      //     strokeDashArray: 3,
      //     padding: {
      //       top: 20,
      //       right: 10,
      //       bottom: 0,
      //       left: 10,
      //     },
      //   },
      //   legend: {
      //     position: "top",
      //     horizontalAlign: "right",
      //     labels: {
      //       colors: "#E0E0E0",
      //     },
      //     markers: {
      //       width: 12,
      //       height: 12,
      //       radius: 4,
      //     },
      //   },
      //   tooltip: {
      //     theme: "dark",
      //     y: {
      //       formatter: function (val) {
      //         return val + "°C";
      //       },
      //     },
      //   },
      // },
      intervalObj: null,
    };
  },
  computed: {
    chartOptions() {
      const isDark = this.$vuetify.theme.dark;

      const textColor = isDark ? "#E0E0E0" : "#212121";
      const gridColor = isDark ? "#2A2E34" : "#e0e0e0";

      return {
        series: [
          {
            name: "Temperature",
            type: "column",
            data: [], // Your temperature data will go here
            color: "#ff4a4a", // Fixed teal for temperature
          },
          {
            name: "Humidity",
            type: "column",
            data: [],
            color: "#00C1D4", // Fixed orange for humidity
          },
        ],
        theme: {
          mode: isDark ? "dark" : "light",
        },
        chart: {
          background: "transparent", //

          toolbar: {
            show: false,
          },
          type: "bar",
          width: "98%",
          height: 280,
          foreColor: textColor,
          stacked: false,
          animations: {
            enabled: true,
            easing: "easeinout",
            speed: 500,
          },
        },
        plotOptions: {
          bar: {
            horizontal: false,
            columnWidth: "50%",
            endingShape: "rounded",
          },
        },
        dataLabels: {
          enabled: false,
        },
        stroke: {
          show: true,
          width: 2,
          colors: ["transparent"],
        },
        xaxis: {
          categories: [],
          labels: {
            style: {
              colors: textColor,
            },
          },
          axisBorder: {
            show: true,
            color: gridColor,
          },
          axisTicks: {
            show: true,
            color: gridColor,
          },
        },
        yaxis: {
          labels: {
            style: {
              colors: textColor,
            },
          },
          title: {
            text: " ",
            style: {
              color: textColor,
            },
          },
        },
        fill: {
          opacity: 1,
        },
        grid: {
          borderColor: gridColor,
          strokeDashArray: 3,
          padding: {
            top: 20,
            right: 10,
            bottom: 0,
            left: 10,
          },
        },
        legend: {
          position: "top",
          horizontalAlign: "right",
          labels: {
            colors: textColor,
          },
          markers: {
            width: 12,
            height: 12,
            radius: 4,
          },
        },
        tooltip: {
          theme: isDark ? "dark" : "light",
          y: {
            formatter: function (val) {
              return val + "°C";
            },
          },
        },
      };
    },
  },
  beforeDestroy() {
    clearInterval(this.intervalObj);
    this.intervalObj = null;
  },
  watch: {
    from_date(val) {
      this.getDataFromApi();
    },
    "$vuetify.theme.dark"(val) {
      if (this.chart) {
        this.chart.updateOptions(this.chartOptions);
        this.getDataFromApi();
      }
    },
  },

  created() {
    // console.log("from_date", this.from_date);
    /////////this.getDataFromApi();
  },
  mounted() {
    this.chartOptions.chart.height = this.height;
    this.chartOptions.series = this.series;
    // setTimeout(() => {
    ////this.getDataFromApi();
    // this.intervalObj = setInterval(() => {
    //   this.getDataFromApi();
    // }, 1000 * 60 * 15);

    console.log("Mounted");
    /// }, 2000);

    // this.$store.commit(
    //   "AlarmDashboard/alarm_temperature_chart2_date",
    //   this.from_date
    // );
    // try {
    //   new ApexCharts(
    //     document.querySelector("#" + this.name),
    //     this.chartOptions
    //   ).render();
    // } catch (error) {}
    // this.getDataFromApi();
    setTimeout(() => {
      this.getDataFromApi();
    }, 1000 * 10);

    // setTimeout(() => {
    //   this.getDataFromApi();
    // }, 6000);
  },

  methods: {
    filterDate() {},

    handleDatesFilter(dates) {
      //console.log(dates);
      //if (dates.length > 1)
      {
        this.filter_from_date = dates.from; // dates[0];
        this.filter_to_date = dates.to; // dates[1];

        this.getDataFromApi(this.endpoint, "dates", [dates.from, dates.to]);

        // this.payloadOptions.params["from_date"] = filter_value[0];
        // this.payloadOptions.params["to_date"] = filter_value[1];
      }
    },
    getDataFromApi() {
      if (!this.device_serial_number) return;

      this.key = 1;
      // const data = await this.$store.dispatch("dashboard/every_hour_count");
      this.$axios
        .get("alarm_dashboard_get_humidity_temperature_hourly_data", {
          params: {
            company_id: this.$auth.user.company_id,
            branch_id: this.branch_id > 0 ? this.branch_id : null,
            device_serial_number: this.device_serial_number,
            from_date: this.from_date,
            device_temperature_serial_address:
              this.device_temperature_serial_address,
          },
        })
        .then(({ data }) => {
          this.key = this.key + 1;
          this.data = data;
          this.loading = false;

          this.temperature_hourly_data = data.temperature;
          this.humidity_hourly_data = data.humidity;

          this.renderChart(
            this.temperature_hourly_data,
            this.humidity_hourly_data
          );
        });
    },
    async renderChart(data1, data2) {
      this.chartOptions.labels = [];

      if (data1.length == 0 && data2.length == 0) {
        this.chart.destroy();
        return;
      }
      let counter = 0;

      this.chartOptions.series[0]["data"] = [];
      data1.forEach((item, index) => {
        this.chartOptions.series[0]["data"][index] = item.count; //parseInt(item.count);
        // console.log("item.hour", item.hour);

        this.chartOptions.labels[index] = item.hour;
        counter++;
      });
      counter = 0;
      data2.forEach((item, index) => {
        this.chartOptions.series[1]["data"][index] = item.count; //parseInt(item.count);
        // console.log("item.hour", item.hour);

        this.chartOptions.labels[index] = item.hour;
        counter++;
      });

      // Render the chart
      this.chart = await new ApexCharts(
        document.querySelector("#" + this.name),
        this.chartOptions
      );
      if (this.chart) this.chart.render();
      // if (this.chart) this.chart.render();
    },
  },
};
</script>
