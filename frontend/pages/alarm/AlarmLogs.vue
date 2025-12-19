<template>
  <v-card outlined class="pa-4 mt-2  " style="border-radius: 12px;;">
    <v-card-title class="d-flex align-center">
      <div>
        <div class="text-h6 font-weight-bold">SOS Alarm Reports</div>
      </div>

      <v-spacer />
      <v-row>
        <v-col style="margin: auto">
          <v-icon loading="true" @click="getDataFromApi()" class="mt-2 pull-right ">mdi-reload</v-icon>

          <v-text-field style="padding-top: 7px; float: right; width: 250px;height:40px" height="20"
            class="employee-schedule-search-box" v-model="commonSearch" label="Room Number, Room Name ..."
            placeholder="Room Number, Room Name ..." dense outlined type="text" append-icon="mdi-magnify" clearable
            hide-details></v-text-field></v-col>

        <v-col style="max-width: 200px; padding-right: 0px; margin: auto">


          <v-select small class="employee-schedule-search-box" style="
                        padding-top: 7px;
                        z-index: 999;
                        min-width: 100%;
                        width: 200px;height:40px;
                      " height="20" outlined v-model="filterSOSStatus" dense
            :items="[{ 'name': 'All SOS', 'value': '' }, { 'name': 'Pending', 'value': 'true' }, { 'name': 'Resolved', 'value': 'false' }, { 'name': 'Acknowledged', 'value': 'acknowledged' }]"
            item-text="name" item-value="value" hide-details></v-select>
        </v-col>
        <v-col style="max-width: 230px; margin: auto">
          <CustomFilter style="float: left; padding-top: 5px; z-index: 999" @filter-attr="filterAttr"
            :default_date_from="date_from" :default_date_to="date_to" :defaultFilterType="1" :height="'40px'" />
        </v-col>
        <v-col style="max-width: 100px; padding-top: 18px; margin: auto">
          <v-btn desne color="primary" @click="getDataFromApi()">Submit</v-btn>
        </v-col>
        <v-col style="max-width: 90px; padding-top: 18px; margin: auto"> <v-menu bottom right>
            <template v-slot:activator="{ on, attrs }">
              <span v-bind="attrs" v-on="on">
                <v-icon dark-2 icon color="violet">mdi-printer-outline</v-icon>
                Print
              </span>
            </template>
            <v-list width="100" dense>
              <v-list-item @click="downloadOptions(`print`)">
                <v-list-item-title style="cursor: pointer">
                  <v-row>
                    <v-col cols="5"><img style="padding-top: 5px" src="/icons/icon_print.png"
                        class="iconsize" /></v-col>
                    <v-col cols="7" style="padding-left: 0px; padding-top: 19px">
                      Print
                    </v-col>
                  </v-row>
                </v-list-item-title>
              </v-list-item>
              <v-list-item @click="downloadOptions('download')">
                <v-list-item-title style="cursor: pointer">
                  <v-row>
                    <v-col cols="5"><img style="padding-top: 5px" src="/icons/icon_pdf.png" class="iconsize" /></v-col>
                    <v-col cols="7" style="padding-left: 0px; padding-top: 19px">
                      PDF
                    </v-col>
                  </v-row>
                </v-list-item-title>
              </v-list-item>

              <v-list-item @click="downloadOptions('excel')">
                <v-list-item-title style="cursor: pointer">
                  <v-row>
                    <v-col cols="5"><img style="padding-top: 5px" src="/icons/icon_excel.png"
                        class="iconsize" /></v-col>
                    <v-col cols="7" style="padding-left: 0px; padding-top: 19px">
                      EXCEL
                    </v-col>
                  </v-row>
                </v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu></v-col>

      </v-row>

    </v-card-title>

    <v-divider />

    <v-data-table dense :headers="headers" :items="logs" :loading="loading" :options.sync="options" :footer-props="{
      itemsPerPageOptions: [20, 100, 500, 1000],
    }" class="elevation-1" :server-items-length="totalRowsCount">


      <template v-slot:item.sno="{ item, index }">
        {{
          currentPage
            ? (currentPage - 1) * itemsPerPage +
            (cumulativeIndex + index)
            : ""
        }}
      </template>

      <!-- Room Type Icon -->
      <template v-slot:item.room_type="{ item }">
        <div v-if="item.room?.room_type">{{ $utils.caps(item.room.room_type) }}
          <br /><v-icon size="20" :color="roomTypeColor(item.room.room_type)">
            {{ roomTypeIcon(item.room.room_type) }}


          </v-icon><v-icon size="20" :color="roomTypeColor(item.room.room_type)">
            {{ roomTypeIcon2(item.room.room_type) }}


          </v-icon>
        </div>
        <div v-else>---</div>


      </template>

      <!-- Location -->
      <template v-slot:item.device_location="{ item }">
        <div class="font-weight-medium" v-if="item.device">
          {{ item.device.location || '-' }}
        </div>
      </template>

      <!-- Room -->
      <template v-slot:item.room_name="{ item }">
        <div class="font-weight-medium">{{ item.room_name || '-' }}</div>
      </template>

      <template v-slot:item.room_id="{ item }">
        <v-chip small outlined>{{ item.room_id ?? '-' }}</v-chip>
      </template>

      <!-- Dates -->
      <template v-slot:item.alarm_start_datetime="{ item }">
        <span>{{ fmt(item.alarm_start_datetime) }}</span>
      </template>

      <template v-slot:item.alarm_end_datetime="{ item }">
        <span>{{ fmt(item.alarm_end_datetime) }}</span>
      </template>

      <template v-slot:item.responded_datetime="{ item }">
        <span>{{ fmt(item.responded_datetime) }}</span>
      </template>

      <!-- Response minutes -->
      <template v-slot:item.response_in_minutes="{ item }">
        <v-chip small outlined :class="responseChipClass(item)">
          {{ $dateFormat.minutesToHHMM(item.response_in_minutes) }}
        </v-chip>
      </template>

      <!-- Status -->
      <template v-slot:item.ui_status="{ item }">
        <v-chip small :class="alarmStatusClass(item)">
          {{ alarmStatusLabel(item) }}
        </v-chip>
      </template>

      <template v-slot:no-data>
        <div class="pa-6 grey--text">No records found.</div>
      </template>
    </v-data-table>
  </v-card>
</template>

<script>
import CustomFilter from '../../components/CustomFilter.vue';

export default {
  name: "SosAlarmReports",

  components: { CustomFilter },

  data() {
    return {
      options: { perPage: 20 },

      slaMinutes: 1,
      loading: false,
      logs: [],

      search: "",
      from: null,
      to: null,
      menuFrom: false,
      menuTo: false,

      statusFilter: "ALL",
      statusItems: [
        { text: "All", value: "ALL" },
        { text: "Pending", value: "PENDING" },
        { text: "Acknowledged", value: "ACKNOWLEDGED" },
        { text: "Resolved", value: "RESOLVED" },
      ],

      // Pagination tracking for correct S.No
      page: 1,
      itemsPerPage: 20,
      totalRowsCount: 0,
      page: 1,
      perPage: 0,
      currentPage: 1,
      cumulativeIndex: 1,
      totalTableRowsCount: 0,
      options: {},

      headers: [
        { text: "#", value: "sno", sortable: false, },
        { text: "Location", value: "device_location", sortable: false },
        { text: "Room Name", value: "room_name", sortable: false },
        { text: "Room Type", value: "room_type", sortable: false, },


        { text: "Room ID", value: "room_id", sortable: false },
        { text: "Alarm Start", value: "alarm_start_datetime", sortable: false },
        { text: "Alarm End", value: "alarm_end_datetime", sortable: false },

        { text: "Response (min)", value: "response_in_minutes", sortable: false },
        { text: "Acknowledged", value: "responded_datetime", sortable: false },
        { text: "Status", value: "ui_status", sortable: false },
      ],

      commonSearch: "",
      filterSOSStatus: '',
      date_to: null,
      date_from: null,

    };
  },

  mounted() {
    this.getDataFromApi();
  },
  watch: {
    options: {
      handler() {
        this.getDataFromApi();
      },
      deep: true,
    },
  },
  methods: {
    filterAttr(data) {
      this.date_from = data.from;
      this.date_to = data.to;

      //this.getDataFromApi(0);
    },
    async getDataFromApi() {
      this.loading = true;
      try {




        let { sortBy, sortDesc, page, itemsPerPage } = this.options;
        const sortedBy = Array.isArray(sortBy) ? sortBy[0] : "";
        const sortedDesc = Array.isArray(sortDesc) ? sortDesc[0] : "";





        this.currentPage = page;
        this.itemsPerPage = itemsPerPage;


        let options = {
          params: {
            company_id: this.$auth.user.company_id,
            page,
            perPage: itemsPerPage,
            pagination: true,
            common_search: this.commonSearch != "" ? this.commonSearch : null,

          },
        };

        this.$axios.get("sos_logs_reports", options).then(({ data }) => {
          this.logs = data.data;
          this.totalRowsCount = data.total;


          this.totalRowsCount = data.total;
        });




      } catch (e) {
        console.error("SOS reports fetch failed:", e);
        this.logs = [];
      } finally {
        this.loading = false;
      }
    },


    fmt(v) {
      if (!v) return "-";
      const d = new Date(v);
      if (isNaN(d.getTime())) return String(v);
      return d.toLocaleString();
    },

    displayMinutes(v) {
      if (v === null || v === undefined || v === "") return "-";
      const n = Number(v);
      if (!Number.isFinite(n)) return String(v);
      return n.toFixed(0);
    },

    roomTypeIcon(type) {
      switch ((type || "").toLowerCase()) {
        case "toilet":
          return "mdi-toilet";
        case "room":
          return "mdi-bed";
        case "room-pd":
          return "mdi-bed";
        case "toilet-pd":
          return "mdi-toilet";
        default:
          return "mdi-bed";
      }
    },
    roomTypeIcon2(type) {
      switch ((type || "").toLowerCase()) {

        case "room-pd":
          return "mdi-wheelchair";
        case "toilet-pd":
          return "mdi-wheelchair";

      }
    },
    roomTypeColor(type) {
      switch ((type || "").toLowerCase()) {
        case "toilet":
          return "yellow";
        case "toilet-pd":
          return "yellow";
        case "room":
          return "blue";
        case "room-pd":
          return "red";
        default:
          return "blue";
      }
    },
    downloadOptions(option) {
      // let filterSensorname = this.tab > 0 ? this.sensorItems[this.tab] : null;

      // if (this.eventFilter) {
      //   filterSensorname = this.eventFilter;
      // }

      // let url = process.env.BACKEND_URL;
      // if (option == "print") url += "/parking_camera_logs_print_pdf";
      // if (option == "excel") url += "/parking_camera_logs_export_excel";
      // if (option == "download")
      //   url += "/parking_camera_logs_download_pdf";
      // //if (option == "download") url += "/alarm_events_download_pdf";

      // url += "?company_id=" + this.$auth.user.company_id;
      // url += "&date_from=" + this.date_from;
      // url += "&date_to=" + this.date_to;
      // if (this.commonSearch) url += "&common_search=" + this.commonSearch;
      // if (this.filterAlarmStatus)
      //   url += "&alarm_status=" + this.filterAlarmStatus;
      // if (filterSensorname != "null" && filterSensorname)
      //   url += "&filterSensorname=" + filterSensorname;
      // if (this.filterResponseInMinutes)
      //   url += "&filterResponseInMinutes=" + this.filterResponseInMinutes;
      // url += "&tab=" + this.tab;
      // //  url += "&alarm_status=" + this.filterAlarmStatus;

      // window.open(url, "_blank");
    },
    alarmStatusLabel(row) {
      const isActive = row.sos_status === true;
      if (isActive) {
        return row.responded_datetime ? "ACKNOWLEDGED" : "PENDING";
      }
      return "RESOLVED";
    },

    alarmStatusClass(row) {
      const label = this.alarmStatusLabel(row);
      if (label === "PENDING") return "chip-critical";
      if (label === "ACKNOWLEDGED") return "chip-warning";
      return "chip-muted";
    },

    responseChipClass(row) {
      const v = row.response_in_minutes;
      if (v === null || v === undefined) return "chip-muted-outline";

      const n = Number(v);
      if (!Number.isFinite(n)) return "chip-muted-outline";
      return n <= this.slaMinutes ? "chip-success-outline" : "chip-warning-outline";
    },

    resetFilters() {
      this.search = "";
      this.from = null;
      this.to = null;
      this.statusFilter = "ALL";
      this.page = 1;
    },
  },
};
</script>

<style scoped>
/* Status chips (solid) */
.chip-critical {
  background-color: #ef4444 !important;
  color: #fff !important;
}

.chip-warning {
  background-color: #f97316 !important;
  color: #fff !important;
}

.chip-muted {
  background-color: #9ca3af !important;
  color: #fff !important;
}

/* Response chips (outlined) */
.chip-success-outline {
  border-color: #22c55e !important;
  color: #22c55e !important;
}

.chip-warning-outline {
  border-color: #f97316 !important;
  color: #f97316 !important;
}

.chip-muted-outline {
  opacity: 0.75;
}
</style>
