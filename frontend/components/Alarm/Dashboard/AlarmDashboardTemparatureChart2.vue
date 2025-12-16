<template>
  <div style="width: 100%">
    <!-- <CustomFilter
      style="float: right; padding-top: 5px; z-index: 9999"
      @filter-attr="filterAttr"
      :default_date_from="date_from"
      :default_date_to="date_to"
      :defaultFilterType="1"
      :height="'40px'"
    /> -->
    <!-- <v-menu
      style="z-index: 9999"
      v-model="from_menu"
      :close-on-content-click="false"
      :nudge-left="50"
      transition="scale-transition"
      offset-y
      min-width="auto"
    >
      <template v-slot:activator="{ on, attrs }">
        <v-text-field
          style="
            width: 230px;
            float: right;
            z-index: 9999;
            height: 5px;
            padding-top: 16px;
          "
          outlined
          v-model="from_date"
          v-bind="attrs"
          v-on="on"
          dense
          class="custom-text-box shadow-none"
          label="Date Filter"
        ></v-text-field>
      </template>
      <v-date-picker
        no-title
        scrollable
        v-model="from_date"
        @input="from_menu = false"
        @change="getDataFromApi()"
      ></v-date-picker>
    </v-menu> -->
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
  ],
  data() {
    return {
      key: 1,
      //from_date: "",
      //from_menu: false,
      // series: [
      //   {
      //     data: [30, 40, 50, 60, 70, 80, 90],
      //   },
      // ],
      // chartOptions: {
      //   chart: {
      //     type: "bar",
      //   },
      //   plotOptions: {
      //     bar: {
      //       colors: {
      //         ranges: [
      //           {
      //             from: 0,
      //             to: 50,
      //             color: "#FF5733",
      //           },
      //           {
      //             from: 51,
      //             to: 1000,
      //             color: "#36B37E",
      //           },
      //         ],
      //       },
      //     },
      //   },
      //   xaxis: {
      //     categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul"],
      //   },
      //   yaxis: {
      //     max: 100,
      //   },
      //   dataLabels: {
      //     enabled: false,
      //   },
      // },
      series: [
        {
          name: "Temparature",
          type: "column",
          data: [],
        },
        // {
        //   name: "",
        //   type: "line",
        //   data: [],
        // },
      ],
      chartOptions: {
        chart: {
          //  height: 230,//
          type: "line",
          toolbar: {
            show: false,
          },
        },
        stroke: {
          width: [0, 2],

          curve: "smooth",
        },
        title: {
          text: "Temperature Hourly Chart",
        },
        dataLabels: {
          enabled: true,
          enabledOnSeries: [1],
        },
        labels: [],
        plotOptions: {
          bar: {
            columnWidth: "20%",
            colors: {
              ranges: [
                {
                  from: 0,
                  to: 23,
                  color: "#008450",
                },
                {
                  from: 23,
                  to: 50,
                  color: "#EFB700",
                },
                {
                  from: 50,
                  to: 99,
                  color: "#B81D13",
                },
              ],
            },
          },
        },
        xaxis: {
          type: "string",
        },
        // yaxis: [
        //   {
        //     title: {
        //       text: "Temparature",
        //     },
        //   },
        //   {
        //     opposite: true,
        //     title: {
        //       text: " ",
        //     },
        //   },
        // ],
        dataLabels: {
          enabled: false,
        },
      },
      // chartOptions: {
      //   plotOptions: {
      //     bar: {
      //       columnWidth: "10%",
      //     },
      //     colors: {
      //       ranges: [
      //         {
      //           from: 0,
      //           to: 30,
      //           color: "#FFF",
      //         },
      //         {
      //           from: 31,
      //           to: 70,
      //           color: "green",
      //         },
      //         {
      //           from: 71,
      //           to: 100,
      //           color: "red",
      //         },
      //       ],
      //     },
      //   },
      //   //colors: ["#c00000", "#efb700"],

      //   chart: {
      //     height: 220,
      //     type: "line",
      //     toolbar: {
      //       show: false,
      //     },
      //   },
      //   stroke: {
      //     width: [0, 2],

      //     curve: "smooth",
      //   },
      //   title: {
      //     text: "Temperature Hourly Chart",
      //   },
      //   dataLabels: {
      //     enabled: true,
      //     enabledOnSeries: [1],
      //   },
      //   labels: [],
      //   xaxis: {
      //     type: "string",
      //   },
      //   yaxis: [
      //     {
      //       title: {
      //         text: "Temparature",
      //       },
      //     },
      //     {
      //       opposite: true,
      //       title: {
      //         text: " ",
      //       },
      //     },
      //   ],
      // },
    };
  },
  watch: {
    // branch_id() {
    //   try {
    //     this.$store.commit("AlarmDashboard/alarm_temparature_hourly", null);
    //     this.getDataFromApi();
    //   } catch (e) {}
    // },
    from_date(val) {
      this.getDataFromApi();
    },
  },

  created() {
    // const today = new Date();
    // this.from_date = today.toISOString().slice(0, 10);

    console.log("from_date", this.from_date);
  },
  mounted() {
    this.chartOptions.chart.height = this.height;
    this.chartOptions.series = this.series;
    // setTimeout(() => {
    ////this.getDataFromApi();
    setInterval(() => {
      if (this.$route.name == "alarm-dashboard") {
        this.getDataFromApi();
      }
    }, 1000 * 60 * 15);

    console.log("Mounted");
    /// }, 2000);

    this.$store.commit(
      "AlarmDashboard/alarm_temperature_chart2_date",
      this.from_date
    );
    try {
      new ApexCharts(
        document.querySelector("#" + this.name),
        this.chartOptions
      ).render();
    } catch (error) {}
    // this.getDataFromApi();
    setTimeout(() => {
      this.getDataFromApi();
    }, 1000 * 10);

    setTimeout(() => {
      this.getDataFromApi();
    }, 6000);
  },

  methods: {
    filterDate() {},
    viewLogs() {
      this.$router.push("/attendance_report");
    },
    getDataFromApi() {
      if (!this.device_serial_number) return;

      this.key = 1;
      // const data = await this.$store.dispatch("dashboard/every_hour_count");
      this.$axios
        .get("alarm_dashboard_get_hourly_data", {
          params: {
            company_id: this.$auth.user.company_id,
            branch_id: this.branch_id > 0 ? this.branch_id : null,
            device_serial_number: this.device_serial_number,
            from_date: this.from_date,
          },
        })
        .then(({ data }) => {
          this.key = this.key + 1;
          this.data = data;
          this.loading = false;
          this.$store.commit("AlarmDashboard/alarm_temparature_hourly", data);

          this.temperature_hourly_data = data.houry_data;

          this.renderChart(this.temperature_hourly_data);

          // setTimeout(() => {
          //   this.key = this.key + 1;
          // }, 1000);
        });
    },
    async renderChart(data) {
      if (this.theme) {
        this.chartOptions.chart.background = "#000000";
        this.chartOptions.chart.foreColor = "#ffffff";
      }
      //this.chartOptions.series[0]["data"] = [];
      //this.chartOptions.series[1]["data"] = [];
      this.chartOptions.labels = [];
      //this.chartOptions.series[0]["data"] = [];
      //this.chartOptions.series[1]["data"] = [];

      if (data.length == 0) {
        this.chart.destroy();
        return;
      }
      let counter = 0;

      this.chartOptions.series[0]["data"] = [];
      data.forEach((item, index) => {
        this.chartOptions.series[0]["data"][index] = item.count; //parseInt(item.count);

        //this.chartOptions.series[1]["data"][counter] = item.count;

        this.chartOptions.labels[index] = item.hour;
        counter++;
      });
      // try {
      //   new ApexCharts(
      //     document.querySelector("#" + this.name),
      //     this.chartOptions
      //   ).render();
      // } catch (error) {}

      // Render the chart
      this.chart = await new ApexCharts(
        document.querySelector("#" + this.name),
        this.chartOptions
      );
      if (this.chart) this.chart.render();
    },
  },
};
</script>
