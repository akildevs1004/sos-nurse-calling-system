<template>
  <div v-if="can(`logs_access`)">
    <div class="text-center ma-2">
      <v-snackbar v-model="snackbar" top="top" color="secondary" elevation="24">
        {{ response }}
      </v-snackbar>
    </div>

    <v-row>
      <v-col>
        <v-card elevation="0" class="mt-2">
          <v-toolbar
            extended
            class="mb-2"
            dense
            flat
            :style="$vuetify.breakpoint.smAndDown ? 'height:150px;' : ''"
          >
            <v-toolbar-title class="mr-2"></v-toolbar-title>

            <template v-slot:extension>
              <v-row>
                <v-col cols="12" sm="6" md="3"
                  >Temperature Reports
                  <v-btn
                    title="Reload"
                    dense
                    class="ma-0 px-0"
                    x-small
                    :ripple="false"
                    @click="getDataFromApi()"
                    text
                  >
                    <v-icon class="ml-2" dark>mdi-reload</v-icon>
                  </v-btn></v-col
                >

                <v-col v-if="!$vuetify.breakpoint.smAndDown"></v-col>
                <v-col style="max-width: 400px">
                  <v-row
                    ><v-col style="max-width: 200px"
                      ><v-select
                        style="width: 200px"
                        dense
                        outlined
                        hide-details
                        label="Room"
                        v-model="device_serial_number_with_sensor"
                        :items="[
                          { name: 'All Rooms', serial_number: null },
                          ...devices,
                        ]"
                        :item-value="
                          (item) =>
                            `${item.serial_number}|${
                              item.temperature_serial_address ?? 'null'
                            }`
                        "
                        :item-text="
                          (item) =>
                            item.temperature_sensor_name
                              ? `${item.name} - ${item.temperature_sensor_name}`
                              : item.name
                        "
                    /></v-col>

                    <v-col style="max-width: 160px">
                      <DateRangeComponent
                        @filter-attr="handleDatesFilter"
                        :defaultFilterType="1"
                        :height="'40px'"
                        style="margin: 5px; width: 100%"
                        :class="
                          $vuetify.theme.dark ? 'daterange-blacktheme' : ''
                        "
                      /> </v-col
                  ></v-row>
                </v-col>
              </v-row>
            </template>
          </v-toolbar>

          <v-snackbar v-model="snack" :timeout="3000" :color="snackColor">
            {{ snackText }}

            <template v-slot:action="{ attrs }">
              <v-btn v-bind="attrs" text @click="snack = false"> Close </v-btn>
            </template>
          </v-snackbar>

          <v-data-table
            dense
            :headers="headers_table"
            :items="data"
            model-value="data.id"
            :loading="loading"
            :options.sync="options"
            :footer-props="{
              itemsPerPageOptions: [50, 100, 500, 1000],
            }"
            class="elevation-1"
            :server-items-length="totalRowsCount"
            fixed-header
            :height="tableHeight"
          >
            <template v-slot:item.sno="{ item, index }">
              {{
                currentPage
                  ? (currentPage - 1) * options.itemsPerPage +
                    (cumulativeIndex + data.indexOf(item))
                  : ""
              }}
            </template>

            <template v-slot:item.device_name="{ item }">
              {{ item.device.name }}

              <span v-if="item.device_temperature_sensor">
                - {{ item.device_temperature_sensor?.name }}
              </span>
            </template>
            <template v-slot:item.log_time="{ item }">
              {{ $dateFormat.format6(item.log_time) }}
              <div class="secondary-value">
                {{ $dateFormat.format1(item.log_time) }}
              </div>
            </template>
            <template v-slot:item.temperature="{ item }">
              <span
                v-if="item.temparature"
                v-html="item.temparature + '&deg;C'"
              ></span>
              <div v-else>---</div>
            </template>
            <template v-slot:item.humidity="{ item }">
              {{ item.humidity ? item.humidity + "%" : "---" }}
            </template>
            <template v-slot:item.temperature_alarm="{ item }">
              <v-icon :style="getPriorityColor(item.temperature_alarm)"
                >mdi mdi-alarm-light
              </v-icon>
            </template>
            <template v-slot:item.humidity_alarm="{ item }">
              <v-icon :style="getPriorityColor(item.humidity_alarm)"
                >mdi mdi-alarm-light
              </v-icon>
            </template>
            <!-- <template v-slot:item.water_leakage="{ item }">
              <v-icon :style="getPriorityColor(item.water_leakage)"
                >mdi mdi-alarm-light
              </v-icon>
            </template>
            <template v-slot:item.power_failure="{ item }">
              <v-icon :style="getPriorityColor(item.power_failure)"
                >mdi mdi-alarm-light
              </v-icon>
            </template>
            <template v-slot:item.door_status="{ item }">
              <v-icon :style="getPriorityColor(item.door_status)"
                >mdi mdi-alarm-light
              </v-icon>
            </template> -->
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>
  </div>
  <NoAccess v-else />
</template>

<script>
import DateRangeComponent from "../../components/DateRangeComponent.vue";

export default {
  components: {
    DateRangeComponent,
  },
  data: () => ({
    device_serial_number_with_sensor: null,
    cumulativeIndex: 1,
    filter_alarm_status: null,
    filter_device_serial_number: null,
    filter_from_date: null,
    filter_to_date: null,

    currentPage: 1,
    totalRowsCount: 0,
    branchesList: [],
    tableHeight: 700,
    id: "",
    from_menu_filter: "",
    from_date_filter: "",

    filters: {},
    isFilter: false,
    generateLogsDialog: false,
    totalRowsCount: 0,
    //server_datatable_totalItems: 10,
    datatable_search_textbox: "",
    datatable_searchById: "",
    filter_employeeid: "",
    snack: false,
    snackColor: "",
    snackText: "",
    departments: [],
    Model: "Log",
    endpoint: "alarm_temperature_logs",

    from_date: null,
    from_menu: false,
    to_date: null,
    to_menu: false,

    payload: {},

    loading: true,

    date: null,
    menu: false,

    loading: false,
    time_menu: false,

    log_payload: {
      user_id: 41,
      device_id: "OX-8862021010100",
      date: null,
      time: null,
    },

    ids: [],
    device_temperature_serial_address: null,
    data: [],
    devices: [],
    total: 0,
    pagination: {
      current: 1,
      total: 0,
      itemsPerPage: 1000,
    },
    payloadOptions: {},
    options: {
      current: 1,
      total: 0,
      itemsPerPage: 100,
    },
    errors: [],
    response: "",
    snackbar: false,
    headers_table: [
      {
        text: "#",
        align: "left",
        sortable: false,
        key: "sno",
        value: "sno",
      },

      {
        text: "Date",
        align: "center",
        sortable: false,
        key: "log_time",
        value: "log_time",
        width: "150px",
        filterable: true,
        filterSpecial: false,
      },
      {
        text: "Room",
        align: "left",
        sortable: false,
        key: "device_name",
        value: "device_name",

        filterable: true,
        filterSpecial: false,
      },
      {
        text: "Temperature",
        align: "center",
        sortable: false,
        key: "temperature", //sorting
        value: "temperature", //edit purpose

        filterable: true,
        filterSpecial: false,
      },
      {
        text: "Humidity",
        align: "center",
        sortable: false,
        key: "humidity", //sorting
        value: "humidity", //edit purpose

        filterable: true,
        filterSpecial: false,
      },
      {
        text: "Temperature Alarm",
        align: "center",
        sortable: false,
        key: "temperature_alarm", //sorting
        value: "temperature_alarm", //edit purpose

        filterable: true,
        filterSpecial: false,
      },
      {
        text: "Humidity Alarm",
        align: "center",
        sortable: false,
        key: "humidity_alarm", //sorting
        value: "humidity_alarm", //edit purpose

        filterable: true,
        filterSpecial: false,
      },
      // {
      //   text: "Water Leakage",
      //   align: "center",
      //   sortable: false,
      //   key: "water_leakage", //sorting
      //   value: "water_leakage", //edit purpose

      //   filterable: true,
      //   filterSpecial: false,
      // },
      // {
      //   text: "AC Power Failure  ",
      //   align: "center",
      //   sortable: false,
      //   key: "power_failure", //sorting
      //   value: "power_failure", //edit purpose

      //   filterable: true,
      //   filterSpecial: false,
      // },
      // {
      //   text: "Door Status",
      //   align: "center",
      //   sortable: false,
      //   key: "door_status", //sorting
      //   value: "door_status", //edit purpose

      //   filterable: true,
      //   filterSpecial: false,
      // },
    ],
    filterApplied: true,
    intervalObj: null,
  }),
  beforeDestroy() {
    // 1. Clear interval (with null check)
    if (this.intervalObj) {
      clearInterval(this.intervalObj);
      this.intervalObj = null; // Prevent memory leaks
    }
  },
  mounted() {
    this.tableHeight = window.innerHeight - 270;

    try {
      if (window)
        window.addEventListener("resize", () => {
          this.tableHeight = window.innerHeight - 270;
        });
    } catch (e) {}

    this.intervalObj = setInterval(() => {
      this.getDataFromApi();
    }, 1000 * 60 * 1);
  },
  created() {
    if (this.$auth.user.branch_id == null) {
      let branch_header = [
        {
          text: "Branch",
          align: "left",
          sortable: true,
          key: "branch_id", //sorting
          value: "employee.branch.branch_name", //edit purpose
          width: "300px",
          filterable: true,
          filterSpecial: true,
        },
      ];
      this.headers_table.splice(1, 0, ...branch_header);
    }
    this.firstLoad();
    this.getDepartments();
    this.getbranchesList();
  },
  watch: {
    options: {
      handler() {
        this.getDataFromApi();
      },
      deep: true,
    },

    filter_device_serial_number: {
      handler() {
        this.filterApplied = true;
      },
      deep: true,
    },
    filter_from_date: {
      handler() {
        this.filterApplied = true;
      },
      deep: true,
    },
    filter_to_date: {
      handler() {
        this.filterApplied = true;
      },
      deep: true,
    },
    filter_alarm_status: {
      handler() {
        this.filterApplied = true;
      },
      deep: true,
    },
  },
  methods: {
    getPriorityColor(status) {
      if (status == 1) {
        return "color:red";
      }
    },
    getbranchesList() {
      this.payloadOptions = {
        params: {
          company_id: this.$auth.user.company_id,

          branch_id: this.$auth.user.branch_id,
        },
      };

      this.$axios.get(`branches_list`, this.payloadOptions).then(({ data }) => {
        this.branchesList = data;
      });
    },
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
    getDepartments() {
      let options = {
        params: {
          per_page: 10,
          company_id: this.$auth.user.company_id,
          //department_ids: this.$auth.user.assignedDepartments,
        },
      };
      this.$axios.get(`department-list`, options).then(({ data }) => {
        this.departments = data;
        this.departments.unshift({ name: "All Departments", id: "" });
      });
    },
    // applyFilter() {
    //   this.getDataFromApi();
    //   this.from_menu_filter = false;
    //   this.to_menu_filter = false;
    // },
    applyFilters() {
      this.getDataFromApi();
      this.from_menu_filter = false;
      this.to_menu_filter = false;
    },
    toggleFilter() {
      // this.filters = {};
      this.isFilter = !this.isFilter;
    },
    clearFilters() {
      this.filters = {};

      this.isFilter = false;
      this.getDataFromApi();
    },
    firstLoad() {
      this.loading = true;

      this.payload.from_date = this.getDate();
      this.payload.to_date = this.getDate();
      this.payload.from_date_txt = this.getDate();
      this.payload.to_date_txt = this.getDate();
      this.getDeviceList();
      this.getDataFromApi();
    },
    caps(str) {
      if (str == "" || str == null) {
        return "---";
      } else {
        let res = str.toString();
        return res.replace(/\b\w/g, (c) => c.toUpperCase());
      }
    },

    getDeviceList() {
      let payload = {
        params: {
          company_id: this.$auth.user.company_id,
        },
      };
      // this.$axios.get(`/device_list_not_manual`, payload).then(({ data }) => {
      //   this.devices = data;
      // });
      this.$axios.get(`/devices_list_sensors`, payload).then(({ data }) => {
        this.devices = data;
      });
    },
    getDate() {
      const date = new Date();
      const year = date.getFullYear();
      const month = (date.getMonth() + 1).toString().padStart(2, "0");
      const day = date.getDate().toString().padStart(2, "0");
      return `${year}-${month}-${day}`;
    },
    can(per) {
      return this.$pagePermission.can(per, this);
    },

    getDataFromApi(url = this.endpoint, filter_column = "", filter_value = "") {
      if (this.filterApplied) {
        this.currentPage = 1;
        this.options.page = 1;
        this.filterApplied = false;
      }
      const { sortBy, sortDesc, page, itemsPerPage } = this.options;

      let sortedBy = sortBy ? sortBy[0] : "";
      let sortedDesc = sortDesc ? sortDesc[0] : "";
      this.currentPage = page == undefined ? 1 : page;

      console.log(
        "this.device_serial_number_with_sensor",
        this.device_serial_number_with_sensor
      );

      if (
        this.device_serial_number_with_sensor &&
        this.device_serial_number_with_sensor != "null|null"
      ) {
        const [serial_number, device_temperature_serial_address] =
          this.device_serial_number_with_sensor.split("|");

        this.filter_device_serial_number = serial_number;
        this.device_temperature_serial_address =
          device_temperature_serial_address == "null"
            ? null
            : device_temperature_serial_address;
      }
      this.payloadOptions = {
        params: {
          page: page,
          sortBy: sortedBy,
          sortDesc: sortedDesc,
          per_page: itemsPerPage,
          company_id: this.$auth.user.company_id,
          device_serial_number: this.filter_device_serial_number,
          device_temperature_serial_address:
            this.device_temperature_serial_address,

          from_date: this.filter_from_date,
          to_date: this.filter_to_date,
          filter_alarm_status: this.filter_alarm_status,
          only_temperature: true,
        },
      };

      this.loading = true;
      this.$axios.get(url, this.payloadOptions).then(({ data }) => {
        this.currentPage = page == undefined ? 1 : page;

        this.data = data.data;
        this.total = data.total;
        this.loading = false;
        this.totalRowsCount = data.total;
      });
    },
  },
};
</script>
