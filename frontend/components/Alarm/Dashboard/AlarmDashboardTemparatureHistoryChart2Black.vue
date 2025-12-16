<template>
  <div style="width: 100%; height: 400px; padding: 10px">
    <v-row style="">
      <v-col
        cols="6"
        :style="$vuetify.breakpoint.smAndDown ? 'font-size:12px' : ''"
        >Temperature and Humidity History</v-col
      >
      <v-col cols="6">
        <v-row>
          <v-col style="float: right; text-align: right"
            ><DateRangeComponent
              @filter-attr="handleDatesFilter"
              :defaultFilterType="1"
              :default_date_from="filter_from_date"
              :default_date_to="filter_to_date"
              :height="'40px'"
              style="float: right; text-align: right"
              :class="this.$vuetify.theme.dark ? 'daterange-blacktheme' : ''"
            />
          </v-col>
          <v-col style="max-width: 50px">
            <!-- <v-icon size="40" color="blue" @click="toggleLiveData()"
              >mdi-toggle-switch-off</v-icon
            > -->
          </v-col>
        </v-row>
        <!-- <span style="max-width: 250px"> </span
        ><span style="float: right"></span> -->
      </v-col>
    </v-row>

    <div
      :id="nameChart"
      style="width: 100%; margin-top: 0px"
      class="pt-2"
    ></div>
  </div>
</template>

<script>
import DateRangeComponent from "../../DateRangeComponent.vue";

export default {
  props: [
    "nameChart",
    "height",
    "branch_id",
    "device_serial_number",
    "from_date",
    "theme",
    "device_temperature_serial_address",
  ],
  components: {
    DateRangeComponent,
  },
  data() {
    return {
      filter_alarm_status: null,
      filter_device_serial_number: null,
      filter_from_date: null,
      filter_to_date: null,
      chart: null,

      series: [
        {
          name: "Temperature",

          data: [], // Your temperature data will go here
          color: "#ff4a4a", // Fixed teal for temperature
        },
        {
          name: "Humidity",

          data: [],
          color: "#00C1D4", // Fixed orange for humidity
        },
      ],

      intervalObj: null,
      loading: false,
    };
  },
  computed: {
    chartOptions() {
      const isDark = this.$vuetify.theme.dark;
      return {
        theme: {
          mode: isDark ? "dark" : "light",
        },
        borderColor: isDark ? "#444" : "#e0e0e0",
        row: {
          colors: ["transparent"], // 🔍 Transparent row colors
        },
        dataLabels: {
          enabled: false,
        },
        chart: {
          foreColor: isDark ? "#FFF" : "#000",
          background: "transparent", //
          toolbar: {
            show: false,
          },
          height: this.height,
          type: "area",
          animations: {
            enabled: false,
          },
          width: "98%",
        },

        fill: {
          type: "gradient",
          gradient: {
            shadeIntensity: 1,
            inverseColors: false,
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [20, 100, 100, 100],
          },
        },
        stacked: false,
        stroke: {
          curve: "smooth",
          width: 2,
        },
        xaxis: {
          type: "category",
          categoriesDate: [],
          // labels: {},
          labels: {
            rotate: -45,
            style: {
              colors: isDark ? "#FFF" : "#000",
            },
          },
        },
        yaxis: {
          title: {
            text: " ",
          },
          labels: {
            style: {
              colors: isDark ? "#FFF" : "#000",
            },
          },
        },
        colors: [isDark ? "#FFF" : "#000"],

        tooltip1: {
          theme: "dark",
          y: {
            formatter: function (val) {
              console.log("val", val);

              return val + "°C";
            },
          },
        },
        tooltip: {
          theme: "dark",
          shared: true,
          intersect: false,
          custom: ({ series, seriesIndex, dataPointIndex }) => {
            const date = this.fullDates[dataPointIndex];
            const temp = series[0][dataPointIndex];
            const humidity = series[1][dataPointIndex];

            return `
              <div class="apexcharts-tooltip-title"  style="font-size:12px;">${date}</div>
              <div class="apexcharts-tooltip-series-group1" style="font-size:12px;">


                  <div class="apexcharts-tooltip-y-label">Temp: ${temp}°C</div>


              </div>
              <div class="apexcharts-tooltip-series-group1"  style="font-size:12px;">


                  <div class="apexcharts-tooltip-y-label">Hum: ${humidity}%</div>


              </div>
            `;
          },
        },
        grid: {
          borderColor: "#2A2E34",
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
            colors: isDark ? "#fff" : "#000",
          },
          markers: {
            width: 12,
            height: 12,
            radius: 4,
          },
        },
      };
    },
  },

  watch: {
    // // Optional: force re-render when theme changes
    // "$vuetify.theme.dark"(val) {
    //   this.$forceUpdate();

    // },

    "$vuetify.theme.dark"(val) {
      if (this.chart) {
        this.chart.updateOptions(this.chartOptions);
      }
    },

    from_date() {
      this.getDataFromApi();
    },
    device_serial_number() {
      this.getDataFromApi();
    },
    branch_id() {
      this.getDataFromApi();
    },
  },

  mounted() {
    this.initializeChart();
    this.getDataFromApi();

    this.intervalObj = setInterval(() => {
      this.getDataFromApi();
    }, 1000 * 60 * 15); // 15 minutes
  },

  beforeDestroy() {
    if (this.intervalObj) clearInterval(this.intervalObj);
    if (this.chart) this.chart.destroy();
  },
  created() {
    // Get today's date
    let today = new Date();

    let monthObj = this.$dateFormat.monthStartEnd(today);

    // Subtract 7 days from today
    let sevenDaysAgo = new Date(today);

    if (this.$vuetify.breakpoint.smAndDown) {
      sevenDaysAgo.setDate(today.getDate() - 7);
    } else {
      sevenDaysAgo.setDate(today.getDate() - 30);
    }

    // Format the dates (optional)
    this.filter_to_date = today.toISOString().split("T")[0];
    this.filter_from_date = sevenDaysAgo.toISOString().split("T")[0];
  },
  methods: {
    toggleLiveData() {
      this.$emit("switchBacktoLiveData");
    },
    handleDatesFilter(dates) {
      //console.log(dates);
      //if (dates.length > 1)
      {
        this.filter_from_date = dates.from; // dates[0];
        this.filter_to_date = dates.to; // dates[1];

        this.getDataFromApi();

        // this.payloadOptions.params["from_date"] = filter_value[0];
        // this.payloadOptions.params["to_date"] = filter_value[1];
      }
    },
    initializeChart() {
      if (this.chart) this.chart.destroy();

      this.chart = new ApexCharts(
        document.querySelector("#" + this.nameChart),
        {
          ...this.chartOptions,
          series: this.series,
        }
      );
      this.chart.render();
    },

    async getDataFromApi() {
      if (!this.device_serial_number) return;

      this.loading = true;
      try {
        const { data } = await this.$axios.get(
          "alarm_dashboard_get_humidity_temperature_monthly_data",
          {
            params: {
              company_id: this.$auth.user.company_id,
              branch_id: this.branch_id > 0 ? this.branch_id : null,
              device_serial_number: this.device_serial_number,
              from_date: this.filter_from_date,
              to_date: this.filter_to_date,
              device_temperature_serial_address:
                this.device_temperature_serial_address,
            },
          }
        );

        this.updateChartData(data);
      } catch (error) {
        console.error("Failed to fetch chart data:", error);
      } finally {
        this.loading = false;
      }
    },

    updateChartData(data) {
      if (!data || data.length === 0) {
        this.noData = true;
        return;
      }

      this.noData = false;

      const tempData = [];
      const humidityData = [];
      const categories = [];
      this.fullDates = []; // Reset full dates array

      data.forEach((item) => {
        const dateObj = new Date(item.date);
        const dayOnly = dateObj.getDate(); // Gets day of month (1-31)

        categories.push(dayOnly.toString());
        this.fullDates.push(item.date); // Store full date for tooltip

        tempData.push(item.avg_temperature);
        humidityData.push(item.avg_humidity);
      });

      this.chart.updateOptions({
        xaxis: {
          categories,
          labels: {
            formatter: function (value) {
              return value; // Just show the day number
            },
          },
        },
      });

      this.chart.updateSeries([
        { name: "Temperature", data: tempData },
        { name: "Humidity", data: humidityData },
      ]);
    },
  },
};
</script>
