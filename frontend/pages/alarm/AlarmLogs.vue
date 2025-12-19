<template>
  <v-card outlined class="pa-4 mt-2" style="border-radius: 12px;">
    <v-card-title class="d-flex align-center">
      <div class="text-h6 font-weight-bold">SOS Alarm Reports</div>
      <v-spacer />

      <v-row class="align-center" dense>
        <!-- Refresh + Search -->
        <v-col style="margin:auto">
          <!-- <v-btn icon class="mr-2" @click="getDataFromApi" :disabled="loading" title="Refresh">
            <v-progress-circular v-if="loading" indeterminate size="18" width="2" />
            <v-icon v-else>mdi-reload</v-icon>
          </v-btn> -->

          <v-text-field style="padding-top: 7px; float: right; width: 250px; height:40px"
            class="employee-schedule-search-box" v-model="commonSearch" label="Room Number, Room Name ..."
            placeholder="Room Number, Room Name ..." dense outlined append-icon="mdi-magnify" clearable hide-details />
        </v-col>

        <!-- SOS Status -->
        <v-col style="max-width: 200px; padding-right: 0px; margin:auto">
          <v-select class="employee-schedule-search-box"
            style="padding-top: 7px; z-index: 999; min-width: 100%; width: 200px; height:40px;" dense outlined
            v-model="filterSOSStatus" :items="sosStatusItems" item-text="name" item-value="value" hide-details
            label="SOS Status" />
        </v-col>

        <!-- Date Range -->
        <v-col style="max-width: 230px; margin:auto;">
          <CustomFilter style="padding-top:10px;" @filter-attr="filterAttr" :default_date_from="date_from"
            :default_date_to="date_to" :defaultFilterType="1" :height="40" :width="200" />
        </v-col>

        <!-- Submit -->
        <v-col style="max-width: 100px; padding-top: 18px; margin:auto">
          <v-btn dense color="primary" @click="submitFilters" :loading="loading">
            Submit
          </v-btn>
        </v-col>

        <!-- Print menu (kept) -->
        <v-col style="max-width: 90px; padding-top: 18px; margin:auto;font-size:15px">
          <v-menu bottom right>
            <template v-slot:activator="{ on, attrs }">
              <span v-bind="attrs" v-on="on" style="cursor:pointer">
                <v-icon color="violet">mdi-printer-outline</v-icon>
                Print
              </span>
            </template>
            <v-list width="100" dense>
              <v-list-item @click="downloadOptions('print')">
                <v-list-item-title style="cursor: pointer">
                  <v-row>
                    <v-col cols="5">
                      <img style="padding-top: 5px" src="/icons/icon_print.png" class="iconsize" />
                    </v-col>
                    <v-col cols="7" style="padding-left: 0px; padding-top: 19px">
                      Print
                    </v-col>
                  </v-row>
                </v-list-item-title>
              </v-list-item>

              <v-list-item @click="downloadOptions('download')">
                <v-list-item-title style="cursor: pointer">
                  <v-row>
                    <v-col cols="5">
                      <img style="padding-top: 5px" src="/icons/icon_pdf.png" class="iconsize" />
                    </v-col>
                    <v-col cols="7" style="padding-left: 0px; padding-top: 19px">
                      PDF
                    </v-col>
                  </v-row>
                </v-list-item-title>
              </v-list-item>

              <v-list-item @click="downloadOptions('excel')">
                <v-list-item-title style="cursor: pointer">
                  <v-row>
                    <v-col cols="5">
                      <img style="padding-top: 5px" src="/icons/icon_excel.png" class="iconsize" />
                    </v-col>
                    <v-col cols="7" style="padding-left: 0px; padding-top: 19px">
                      EXCEL
                    </v-col>
                  </v-row>
                </v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </v-col>
      </v-row>
    </v-card-title>

    <v-divider />

    <v-data-table dense class="elevation-1" :headers="headers" :items="logs" :loading="loading" :options.sync="options"
      :server-items-length="totalRowsCount" :footer-props="{ itemsPerPageOptions: [20, 100, 500, 1000] }">
      <!-- S.No -->
      <template v-slot:item.sno="{ index }">
        {{ ((options.page || 1) - 1) * (options.itemsPerPage || 20) + index + 1 }}
      </template>

      <!-- Location -->
      <template v-slot:item.device_location="{ item }">
        <div class="font-weight-medium">
          {{ $utils.caps(item.room_name) }}

        </div>
        <div style="" class="secondvalue" v-if="item.device">{{ $utils.caps(item.device.location) }} ({{
          $utils.caps(item.room_id) }})</div>
      </template>

      <!-- Room Name -->
      <template v-slot:item.room_name="{ item }">
        <div class="font-weight-medium">{{ item.room_name || "-" }}</div>
      </template>

      <!-- Room Type -->
      <template v-slot:item.room_type="{ item }">
        <div v-if="item.room?.room_type">


          <v-icon size="10" :color="roomTypeColor(item.room.room_type)">
            {{ roomTypeIcon(item.room.room_type) }}
          </v-icon>
          <v-icon v-if="roomTypeIcon2(item.room.room_type)" size="10" :color="roomTypeColor(item.room.room_type)">
            {{ roomTypeIcon2(item.room.room_type) }}
          </v-icon>
          {{ $utils.caps(item.room.room_type.replace(/-pd$/i, '')) }}
        </div>
        <div v-else>---</div>
      </template>

      <!-- Room ID -->
      <template v-slot:item.room_id="{ item }">
        <v-chip small outlined>{{ item.room_id ?? "-" }}</v-chip>
      </template>

      <!-- Alarm Start -->
      <template v-slot:item.alarm_start_datetime="{ item }">
        {{ $dateFormat.formatTime(item.alarm_start_datetime) }}
        <div style="" class="secondvalue">{{ $dateFormat.formatDate(item.alarm_start_datetime) }}</div>
      </template>

      <!-- Alarm End -->
      <template v-slot:item.alarm_end_datetime="{ item }">


        {{ $dateFormat.formatTime(item.alarm_end_datetime) }}
        <div class="secondvalue">{{ $dateFormat.formatDate(item.alarm_end_datetime) }}</div>
      </template>

      <!-- Response (min) -->
      <template v-slot:item.response_in_minutes="{ item }">
        <v-chip small outlined :class="responseChipClass(item)">
          {{ $dateFormat.minutesToHHMM(item.response_in_minutes) }}
        </v-chip>
      </template>

      <!-- Acknowledged -->
      <template v-slot:item.responded_datetime="{ item }">

        {{ $dateFormat.formatTime(item.responded_datetime) }}
        <div style="" class="secondvalue">{{ $dateFormat.formatDate(item.responded_datetime) }}</div>
      </template>

      <!-- Status -->
      <template v-slot:item.ui_status="{ item }">
        <!-- <v-chip small :class="alarmStatusClass(item)">
          {{ alarmStatusLabel(item) }}
        </v-chip> -->

        <v-chip small class="sos-chip" :class="sosChipClass(item)">
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
import CustomFilter from "../../components/CustomFilter.vue";

export default {
  name: "SosAlarmReports",
  components: { CustomFilter },

  data() {
    return {
      loading: false,
      logs: [],
      totalRowsCount: 0,

      // Server table options (Vuetify)
      options: {
        page: 1,
        itemsPerPage: 20,
        sortBy: [],
        sortDesc: [],
      },

      headers: [
        { text: "#", value: "sno", sortable: false },
        { text: "Alarm Start", value: "alarm_start_datetime", sortable: false },
        { text: "Alarm End", value: "alarm_end_datetime", sortable: false },
        { text: "Location/Room", value: "device_location", sortable: false },
        // { text: "Room Name", value: "room_name", sortable: false },
        { text: "Room Type", value: "room_type", sortable: false },
        // { text: "Room ID", value: "room_id", sortable: false },

        { text: "Response (HH:MM)", value: "response_in_minutes", sortable: false },
        { text: "Status", value: "ui_status", sortable: false },
        { text: "Acknowledged", value: "responded_datetime", sortable: false },

      ],

      // Filters
      commonSearch: "",
      filterSOSStatus: "",
      date_from: null,
      date_to: null,

      sosStatusItems: [
        { name: "All SOS", value: "" },
        { name: "Pending", value: "true" },
        { name: "Resolved", value: "false" },
        // Your backend expects alarm_status="none" for acknowledged
        { name: "Acknowledged", value: "acknowledged" },
      ],

      slaMinutes: 1,
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
    submitFilters() {
      // reset to first page when applying new filters
      this.options.page = 1;
      this.getDataFromApi();
    },

    filterAttr(data) {
      this.date_from = data?.from || null;
      this.date_to = data?.to || null;
    },

    async getDataFromApi() {
      this.loading = true;
      try {
        const { page, itemsPerPage } = this.options;

        const { data } = await this.$axios.get("sos_logs_reports", {
          params: {
            company_id: this.$auth.user.company_id,
            page,
            perPage: itemsPerPage,
            pagination: true,

            common_search: this.commonSearch || null,
            date_from: this.date_from || null,
            date_to: this.date_to || null,
            alarm_status: this.filterSOSStatus || null,
          },
        });

        this.logs = data?.data || [];
        this.totalRowsCount = data?.total || 0;
      } catch (e) {
        console.error("SOS reports fetch failed:", e);
        this.logs = [];
        this.totalRowsCount = 0;
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
        case "toilet-pd":
          return "mdi-wheelchair";
        default:
          return "";
      }
    },

    roomTypeColor(type) {
      switch ((type || "").toLowerCase()) {
        case "toilet":
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

    alarmStatusLabel(row) {
      const isActive = row.sos_status === true;
      if (isActive) return row.responded_datetime ? "ACKNOWLEDGED" : "PENDING";
      return "RESOLVED";
    },

    alarmStatusClass(row) {
      const label = this.alarmStatusLabel(row);
      if (label === "PENDING") return "chip-critical";
      if (label === "ACKNOWLEDGED") return "chip-warning";
      return "chip-muted";
    },
    sosChipClass(row) {
      const status = this.alarmStatusLabel(row);

      if (status === "PENDING") return "sos-pending";
      if (status === "ACKNOWLEDGED") return "sos-ack";
      return "sos-resolved";
    },
    responseChipClass(row) {
      const v = row.response_in_minutes;
      if (v === null || v === undefined) return "chip-muted-outline";

      const n = Number(v);
      if (!Number.isFinite(n)) return "chip-muted-outline";
      return n <= this.slaMinutes ? "chip-success-outline" : "chip-warning-outline";
    },

    downloadOptions(option) {
      let filterSensorname = this.tab > 0 ? this.sensorItems[this.tab] : null;

      if (this.eventFilter) {
        filterSensorname = this.eventFilter;
      }

      let url = process.env.BACKEND_URL;
      if (option == "print") url += "/sos_logs_print_pdf";
      if (option == "excel") url += "/sos_logs_print_excel";
      if (option == "download")
        url += "/sos_logs_download_pdf";
      //if (option == "download") url += "/alarm_events_download_pdf";

      url += "?company_id=" + this.$auth.user.company_id;
      if (this.date_from)
        url += "&date_from=" + this.date_from;
      if (this.date_to)
        url += "&date_to=" + this.date_to;

      if (this.commonSearch) url += "&common_search=" + this.commonSearch;
      if (this.filterAlarmStatus)
        url += "&alarm_status=" + this.filterAlarmStatus;

      url += "&tab=" + this.tab;
      //  url += "&alarm_status=" + this.filterAlarmStatus;

      window.open(url, "_blank");
    },
  },
};
</script>

<style scoped>
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
