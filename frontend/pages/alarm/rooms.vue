<template>
  <div v-if="can(`automation_contnet_access`)">
    <div class="text-center ma-5">
      <v-snackbar v-model="snackbar" top="top" color="secondary" elevation="24">
        {{ response }}
      </v-snackbar>
    </div>
    <v-dialog v-model="DialogDeviceSensors" width="900px" style="background-color: #fff !important">
      <v-card elevation="2">
        <v-card-title dense class="popup_background">
          <span>Device Senosrs</span>
          <v-spacer></v-spacer>
          <v-icon @click="DialogDeviceSensors = false" outlined>
            mdi mdi-close-circle
          </v-icon>
        </v-card-title>
        <v-card-text>
          <v-container>
            <SensorsList v-if="DialogDeviceSensors && editedItem" :key="key" :editedItem="editedItem"
              :device_id="editedItem.id" :device_serial_number="deviceSerialNumber"
              @emitCloseEvent="DialogDeviceSettings = false" />
          </v-container>
        </v-card-text>
      </v-card>
    </v-dialog>
    <v-dialog v-model="DialogDeviceSettings" width="900px">
      <v-card elevation="2">
        <v-card-title dense class="popup_background" dark>
          <span>Device Config Settings</span>
          <v-spacer></v-spacer>
          <v-icon @click="DialogDeviceSettings = false" outlined>
            mdi mdi-close-circle
          </v-icon>
        </v-card-title>
        <v-card-text style="padding: 0px; margin: 0px">
          <v-container>
            <DeviceSettings v-if="DialogDeviceSettings" :key="key" :editedItem="editedItem"
              :device_serial_number="deviceSerialNumber" @emitCloseEvent="DialogDeviceSettings = false" />
          </v-container>
        </v-card-text>
      </v-card>
    </v-dialog>

    <v-row>
      <!-- <v-col xs="12" sm="12" md="3" cols="12">
        <v-select class="form-control" @change="getDataFromApi(`device`)" v-model="pagination.per_page"
          :items="[10, 25, 50, 100]" placeholder="Per Page Records" solo hide-details flat></v-select>
      </v-col> -->
      <!-- <v-col xs="12" sm="12" md="3" cols="12">
        <v-text-field class="form-control py-0 custom-text-box floating shadow-none" placeholder="Search..." solo flat
          @input="searchIt" v-model="search" hide-details></v-text-field>
      </v-col> -->
    </v-row>
    <!-- <Back color="primary" /> -->
    <v-navigation-drawer style="z-index: 99999" v-model="editDialog" bottom temporary right fixed>
      <v-toolbar class="popup_background" dense>
        {{ this.editedIndex == -1 ? "New " : "Edit " }} Device
        <v-spacer></v-spacer>

        <v-icon @click="editDialog = false" outlined dark>
          mdi mdi-close-circle
        </v-icon>
      </v-toolbar>
      <v-form ref="form" v-model="valid" lazy-validation>
        <v-row class="ma-1">
          <v-col md="12">
            <v-text-field :hide-details="!payload.name" v-model="payload.name" placeholder="Device Name" outlined dense
              label="Device Name *"></v-text-field>
            <span v-if="errors && errors.name" class="error--text pa-0 ma-0">{{ errors.name[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-text-field class="pb-0" :hide-details="!payload.short_name" v-model="payload.short_name"
              placeholder="Short Name" outlined dense label="Short Name *"></v-text-field>
            <span v-if="errors && errors.short_name" class="error--text pa-0 ma-0">{{ errors.short_name[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-autocomplete class="pb-0" :hide-details="!payload.branch_id" v-model="payload.branch_id"
              placeholder="Branch Name" outlined dense label="Branch Name *" :items="branches" item-value="id"
              item-text="branch_name"></v-autocomplete>
            <span v-if="errors && errors.branch_id" class="error--text pa-0 ma-0">{{ errors.branch_id[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-text-field class="pb-0" :hide-details="!payload.location" v-model="payload.location"
              placeholder="Device location" outlined dense label="Device location *"></v-text-field>
            <span v-if="errors && errors.location" class="error--text">{{ errors.location[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-autocomplete class="pb-0" :hide-details="!payload.utc_time_zone" v-model="payload.utc_time_zone"
              placeholder="Time Zone" outlined dense label="Time Zone(Ex:UTC+) *" :items="getTimezones()"
              item-value="key" item-text="text"></v-autocomplete>
            <span v-if="errors && errors.utc_time_zone" class="error--text">{{ errors.utc_time_zone[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-text-field class="pb-0" :hide-details="!payload.model_number" v-model="payload.model_number"
              placeholder="Model Number" outlined dense label="Model Number *"></v-text-field>
            <span v-if="errors && errors.model_number" class="error--text">{{ errors.model_number[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-text-field class="pb-0" :hide-details="!payload.serial_number" v-model="payload.serial_number"
              placeholder="Serial Number" outlined dense label="Serial Number *"></v-text-field>
            <span v-if="errors && errors.serial_number" class="error--text">{{ errors.serial_number[0] }}
            </span>
          </v-col>
          <!-- <v-col md="12">
            <v-autocomplete
              class="pb-0"
              :hide-details="!payload.temperature_threshold"
              v-model="payload.temperature_threshold"
              placeholder="Temperature Threshold"
              outlined
              dense
              label="Temperature Threshold"
              :items="getTemperatureList()"
            ></v-autocomplete>
            <span
              v-if="errors && errors.temperature_threshold"
              class="error--text"
              >{{ errors.temperature_threshold[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-autocomplete
              class="pb-0"
              :hide-details="!payload.status_id"
              v-model="payload.status_id"
              placeholder="Status"
              outlined
              dense
              label="Device Status *"
              :items="device_statusses"
              item-value="id"
              item-text="name"
            ></v-autocomplete>
            <span v-if="errors && errors.status_id" class="error--text"
              >{{ errors.status_id[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-row>
              <v-col cols="9"> Smoke Detecter </v-col>
              <v-col cols="3">
                <v-switch
                  v-model="payload.smoke_enabled"
                  color="green"
                  class="mt-0 ml-2"
                ></v-switch>
              </v-col>
            </v-row>
            <span v-if="errors && errors.smoke_enabled" class="error--text"
              >{{ errors.smoke_enabled[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-row>
              <v-col cols="9"> Water Leakage Detecter </v-col>
              <v-col cols="3">
                <v-switch
                  v-model="payload.water_enabled"
                  color="green"
                  class="mt-0 ml-2"
                ></v-switch>
              </v-col>
            </v-row>
            <span v-if="errors && errors.water_enabled" class="error--text"
              >{{ errors.water_enabled[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-row>
              <v-col cols="9"> AC Power Detecter </v-col>
              <v-col cols="3">
                <v-switch
                  v-model="payload.acpower_enabled"
                  color="green"
                  class="mt-0 ml-2"
                ></v-switch>
              </v-col>
            </v-row>
            <span v-if="errors && errors.acpower_enabled" class="error--text"
              >{{ errors.acpower_enabled[0] }}
            </span>
          </v-col>
          <v-col md="12">
            <v-row>
              <v-col cols="9"> Door Open Detecter </v-col>
              <v-col cols="3">
                <v-switch
                  v-model="payload.door_enabled"
                  color="green"
                  class="mt-0 ml-2"
                ></v-switch>
              </v-col>
            </v-row>
            <span v-if="errors && errors.door_enabled" class="error--text"
              >{{ errors.door_enabled[0] }}
            </span>
          </v-col>  -->

          <!-- <v-col md="12">
            <v-autocomplete
              class="pb-0"
              :hide-details="!payload.camera_save_images"
              v-model="payload.camera_save_images"
              placeholder="Camera Unregistred Images"
              outlined
              dense
              label="Camera Unregistred Images"
              :items="[
                { id: false, name: 'No' },
                { id: true, name: 'Yes' },
              ]"
              item-value="id"
              item-text="name"
            ></v-autocomplete>
            <span v-if="errors && errors.camera_save_images" class="error--text"
              >{{ errors.camera_save_images[0] }}
            </span>
          </v-col> -->
        </v-row>
      </v-form>
      <v-row v-if="deviceResponse">
        <v-col>
          <div style="color: red; font-size: 14px" class="pl-1">
            {{ deviceResponse }}
          </div>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="12">
          <div class="text-right">
            <v-btn small :loading="loading" color="primary" @click="store_device">
              Submit
            </v-btn>
          </div>
        </v-col>
      </v-row>
    </v-navigation-drawer>

    <v-card class="mb-0 mt-2" elevation="0">
      <v-toolbar class="rounded-md" dense flat>
        <v-toolbar-title><span> Rooms </span></v-toolbar-title>

        <span>
          <v-btn dense class="ma-0 px-0" x-small :ripple="false" text title="Reload">
            <!-- <v-icon class="ml-2" @click="getDataFromApi()" dark
              >mdi mdi-reload</v-icon
            > -->
          </v-btn>
        </span>

        <span v-if="isCompany" style="width: 200px">
          <v-select @change="getDataFromApi()" class="pt-10 px-2" v-model="filters[`branch_id`]"
            :items="[{ id: ``, branch_name: `Select All` }, ...branchesList]" dense placeholder="Select Branch" outlined
            item-value="id" item-text="branch_name">
          </v-select>
        </span>
        <!-- </template>
<span>Reload</span>
</v-tooltip> -->

        <!-- <v-tooltip top color="primary">
          <template v-slot:activator="{ on, attrs }"> -->
        <!-- <v-btn
          x-small
          :ripple="false"
          text
          title="Filter"
          @click="toggleFilter()"
        >
          <v-icon dark>mdi-filter</v-icon>
        </v-btn> -->
        <!-- </template>
          <span>Filter</span>
        </v-tooltip> -->

        <v-spacer></v-spacer>

        <span>
          <v-btn x-small :ripple="false" text title="Sync Devices" @click="startHeartbeatWatcher()">
            <v-icon dark white>mdi-cached</v-icon>
          </v-btn>
        </span>
        <span v-if="!$vuetify.breakpoint.smAndDown">
          <v-btn v-if="can(`device_create`)" x-small :ripple="false" text title="Add Device" @click="addItem()">
            <v-icon dark white>mdi-plus-circle</v-icon>
          </v-btn>
        </span>
      </v-toolbar>

      <v-snackbar v-model="snack" :timeout="3000" :color="snackColor">
        {{ snackText }}

        <template v-slot:action="{ attrs }">
          <v-btn v-bind="attrs" text @click="snack = false"> Close </v-btn>
        </template>
      </v-snackbar>

      <v-data-table dense :headers="headers" :items="data" model-value="data.id" :loading="loading" :footer-props="{
        itemsPerPageOptions: [50, 100, 500, 1000],
      }" class="elevation-1 pt-5" :options.sync="options" :server-items-length="totalRowsCount" :height="tableHeight">
        <template v-slot:item.sno="{ item, index }">
          {{ ++index }}
        </template>
        <template v-slot:item.name="{ item }">
          {{ caps(item.name) }}
          <div class="secondary-value">{{ item.short_name }}</div>
        </template>

        <template v-slot:item.location="{ item }">
          {{ caps(item.location) }}
        </template>
        <template v-slot:item.temperature_threshold="{ item }">
          {{
            item.temperature_threshold
              ? item.temperature_threshold + "°C"
              : "---"
          }}
        </template>

        <template v-slot:item.device_id="{ item }">
          {{ item.serial_number }}
          <div class="secondary-value">{{ item.model_number }}</div>
        </template>
        <template v-slot:item.smoke_alarm_status="{ item }">
          <v-icon :color="item.smoke_alarm_status == 0 ? '' : 'error'">mdi mdi-bell
          </v-icon>
          <div class="secondary-value" v-if="item.smoke_alarm_status == 1">
            {{ $dateFormat.format4(item.smoke_alarm_start_datetime) }}
          </div>
        </template>

        <template v-slot:item.water_alarm_status="{ item }">
          <v-icon :color="item.water_alarm_status == 0 ? '' : 'error'">mdi mdi-bell
          </v-icon>
          <div class="secondary-value" v-if="item.water_alarm_status == 1">
            {{ $dateFormat.format4(item.water_alarm_start_datetime) }}
          </div>
        </template>
        <template v-slot:item.power_alarm_status="{ item }">
          <v-icon :color="item.power_alarm_status == 0 ? '' : 'error'">mdi mdi-bell
          </v-icon>
          <div class="secondary-value" v-if="item.power_alarm_status == 1">
            {{ $dateFormat.format4(item.power_alarm_start_datetime) }}
          </div>
        </template>

        <template v-slot:item.device_wifi="{ item }">
          {{ item.wifiipaddress || "---" }}
          <div>
            {{ item.wifissid }}
          </div>
        </template>

        <template v-slot:item.door_open_status="{ item }">
          <v-icon :color="item.door_open_status == 0 ? '' : 'error'">mdi mdi-bell
          </v-icon>
          <div class="secondary-value" v-if="item.door_open_status == 1">
            {{ $dateFormat.format4(item.door_open_start_datetime) }}
          </div>
        </template>
        <template v-slot:item.smoke_enabled="{ item }">
          <v-switch disabled v-model="item.smoke_enabled" class="mt-0 ml-2" color="green"></v-switch>
        </template>
        <template v-slot:item.water_enabled="{ item }">
          <v-switch disabled v-model="item.water_enabled" color="green" class="mt-0 ml-2"></v-switch>
        </template>
        <template v-slot:item.acpower_enabled="{ item }">
          <v-switch disabled v-model="item.acpower_enabled" color="green" class="mt-0 ml-2"></v-switch>
        </template>
        <template v-slot:item.door_enabled="{ item }">
          <v-switch disabled v-model="item.door_enabled" color="green" class="mt-0 ml-2"></v-switch>
        </template>
        <template v-slot:item.status_id="{ item }">


          <!-- <img title="Online" v-if="item.status_id == 1" src="/icons/device_status_open.png" style="width: 30px" />
          <img title="Offline" v-else src="/icons/device_status_close.png" style="width: 30px" /> -->
          <img title="Online" v-if="item.status == 1" src="/icons/device_status_open.png" style="width: 30px" />
          <img title="Offline" v-else src="/icons/device_status_close.png" style="width: 30px" />
        </template>

        <template v-slot:item.options="{ item }">
          <v-menu bottom left>
            <template v-slot:activator="{ on, attrs }">
              <div class="text-right">
                <v-btn dark-2 icon v-bind="attrs" v-on="on">
                  <v-icon>mdi-dots-vertical</v-icon>
                </v-btn>
              </div>
            </template>
            <v-list dense>
              <!-- <v-list-item v-if="can(`device_edit`)" @click="SensorsList(item)">
                <v-list-item-title style="cursor: pointer">
                  <v-icon small> mdi-leak </v-icon>
                  Sensors
                </v-list-item-title>
              </v-list-item> -->
              <v-list-item v-if="!$vuetify.breakpoint.smAndDown && can(`device_edit`)" @click="editItem(item)">
                <v-list-item-title style="cursor: pointer">
                  <v-icon small> mdi-pencil </v-icon>
                  Edit
                </v-list-item-title>
              </v-list-item>

              <v-list-item @click="showDeviceSettings(item)">
                <v-list-item-title style="cursor: pointer">
                  <v-icon small> mdi-cog </v-icon>
                  Settings
                </v-list-item-title>
              </v-list-item>
              <v-list-item v-if="!$vuetify.breakpoint.smAndDown && can(`device_delete`)" @click="deleteItem(item)">
                <v-list-item-title style="cursor: pointer">
                  <v-icon color="error" small> mdi-delete </v-icon>
                  Delete
                </v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </template>
      </v-data-table>
    </v-card>
  </div>
</template>
<script>import mqtt from "mqtt";
// import Back from "../../components/Snippets/Back.vue";
import timeZones from "../../defaults/utc_time_zones.json";
import DeviceAccessSettings from "../../components/DeviceAccessSettings.vue";
import DeviceSettings from "../../components/Alarm/DeviceSettings.vue";
import SensorsList from "../../components/Device/sensorsList.vue";
export default {
  components: { DeviceAccessSettings, DeviceSettings, SensorsList },

  data: () => ({
    DialogDeviceSensors: false,
    tableHeight: 600,
    deviceSerialNumber: "",

    deviceCAMVIISettings: {},
    DialogDeviceMegviiSettings: false,
    valid: false,
    rules: [(value) => (value || "").length <= 10 || "Max 10 characters"],
    device_model: [
      (v) => !!v || "Device Name  is required",
      (value) => (value || "").length <= 20 || "Max 20 characters",
    ],

    menu_password: [
      (v) => !!v || "Password is required",
      (value) => (value || "").length <= 8 || "Max 8 characters",
    ],
    sdk_message: "",
    DialogDeviceSettings: false,
    deviceSettings: { maker: {} },
    to_menu_filter: false,
    to_menu_filter: "",
    to_date_filter: "",
    visitorUploadedDevicesInfo: [],
    loadingDeviceData: false,
    visitor_status_list: [],
    inputFindDeviceUserId: "",
    popupDeviceId: null,
    uploadedUserInfoDialog: false,
    dialogAccessSettings: false,
    popup_device_id: "",
    editDialog: false,
    showFilters: false,
    filters: {
      branch_id: "",
    },
    isFilter: false,
    totalRowsCount: 0,
    datatable_search_textbox: "",
    filter_employeeid: "",
    snack: false,
    snackColor: "",
    snackText: "",
    timeZones: timeZones,
    payload: {
      name: "",
      device_type: "",
      device_id: "",
      model_number: "",
      status_id: "",
      company_id: "",
      location: "",
      short_name: "",
      ip: "",
      port: "",
      camera_save_images: false,
      smoke_enabled: 0,
      water_enabled: 0,

      acpower_enabled: 0,

      door_enabled: 0,
    },
    Model: "Device",
    pagination: {
      current: 1,
      total: 0,
      per_page: 100,
    },
    options: {},
    endpoint: "device",
    search: "",
    snackbar: false,
    dialog: false,
    data: [],
    loading: false,
    total: 0,
    deviceResponse: "",
    headers: [
      {
        text: "Sno",
        align: "left",
        sortable: false,
        value: "sno",
        filterable: false,
      },
      {
        text: "Name",
        align: "left",
        sortable: false,
        value: "name",
        filterable: false,
      },
      // {
      //   text: "Short Name",
      //   align: "left",
      //   sortable: false,
      //   value: "short_name",
      //   filterable: false,
      // },
      // {
      //   text: "Branch",
      //   align: "left",
      //   sortable: false,
      //   value: "branch",
      //   filterable: false,
      // },

      {
        text: "Location",
        align: "left",
        sortable: false,
        value: "location",
        filterable: false,
      },
      // {
      //   text: "Threshold",
      //   align: "center",
      //   sortable: false,
      //   value: "temperature_threshold",
      //   filterable: false,
      // },
      // {
      //   text: "Time zone",
      //   align: "left",
      //   sortable: false,
      //   value: "utc_time_zone",
      //   filterable: false,
      // },
      // {
      //   text: "Model Number",
      //   align: "left",
      //   sortable: false,
      //   value: "model_number",
      //   filterable: false,
      // },

      {
        text: "Device Serial Number",
        align: "left",
        sortable: false,
        value: "device_id",
        filterable: false,
      },

      // {
      //   text: "Device IP",
      //   align: "left",
      //   sortable: false,
      //   value: "device_wifi",
      //   filterable: false,
      // },

      // {
      //   text: "Smoke Alarm ",
      //   align: "center",
      //   sortable: false,
      //   value: "smoke_alarm_status",
      //   filterable: false,
      // },
      // {
      //   text: "Water Leakage Alarm",
      //   align: "center",
      //   sortable: false,
      //   value: "water_alarm_status",
      //   filterable: false,
      // },
      // {
      //   text: "AC Power Alarm ",
      //   align: "center",

      //   sortable: false,
      //   value: "power_alarm_status",
      //   filterable: false,
      // },

      // {
      //   text: "Door Open Alarm",
      //   align: "center",
      //   sortable: false,
      //   value: "door_open_status",
      //   filterable: false,
      // },

      // {
      //   text: "Smoke  ",
      //   align: "center",
      //   sortable: false,
      //   value: "smoke_enabled",
      //   filterable: false,
      // },
      // {
      //   text: "Water leakage",
      //   align: "center",
      //   sortable: false,
      //   value: "water_enabled",
      //   filterable: false,
      // },
      // {
      //   text: "AC Power",
      //   align: "center",
      //   sortable: false,
      //   value: "acpower_enabled",
      //   filterable: false,
      // },
      // {
      //   text: "Door",
      //   align: "center",
      //   sortable: false,
      //   value: "door_enabled",
      //   filterable: false,
      // },

      {
        text: "Device Status",
        align: "center",
        sortable: false,
        value: "status_id",
        filterable: false,
        filterSpecial: false,
      },
      {
        text: "Options",
        align: "center",
        sortable: false,
        value: "options",
        filterable: false,
        filterSpecial: false,
      },
    ],
    editedIndex: -1,
    response: "",
    errors: [],

    device_statusses: [],
    branches: [],
    branchesList: [],
    isCompany: true,
    timeZoneOptions: [],
    editedItem: null,
    key: 1,

    heartbeatTimeoutMs: 1000 * 20,  // offline if no heartbeat in 60s
    _hbSubscribed: false,
    _hbTimer: null,
    _onMqttMessage: null,
    _indexBySerial: Object.create(null),
    mqttClient: null,
  }),

  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New device" : "Edit device";
    },
  },

  watch: {
    options: {
      handler() {
        this.getDataFromApi();
      },
      deep: true,
    },
    dialog(val) {
      val || this.close();
      this.errors = [];
      this.search = "";
    },
  },
  mounted() {

    this.connectMQTT();
    /*
    setTimeout(() => {
      this.updateDevicesHealth();
    }, 1000 * 5);*/

    // setInterval(() => {
    //   if (this.$route.name == "alarm-rooms") {
    //     this.getDataFromApi();
    //   }
    // }, 1000 * 60);
    this.tableHeight = window.innerHeight - 230;

    try {
      if (window)
        window.addEventListener("resize", () => {
          this.tableHeight = window.innerHeight - 230;
        });
    } catch (e) { }
  },
  async created() {
    this.loading = true;

    if (this.$auth.user.branch_id) {
      this.filters[branch_id] = this.$auth.user.branch_id;
      this.isCompany = false;
      return;
    }

    // let branch_header = [
    //   {
    //     text: "Branch",
    //     align: "left",
    //     sortable: true,
    //     key: "branch_id", //sorting
    //     value: "company_branch.branch_name", //edit purpose

    //     filterable: true,
    //     filterSpecial: true,
    //   },
    // ];
    // this.headers.splice(1, 0, ...branch_header);

    try {
      const { data } = await this.$axios.get(`branches_list`, {
        params: {
          per_page: 100,
          company_id: this.$auth.user.company_id,
        },
      });
      this.branchesList = data;
    } catch (error) {
      // Handle the error
      console.error("Error fetching branch list", error);
    }

    this.getDataFromApi();
    this.getBranches();
    this.getDeviceStatus();
  },

  methods: {
    SensorsList(item) {
      this.errors = [];
      this.payload = {};
      this.editedItem = item;
      this.editedIndex = item.id;

      this.DialogDeviceSensors = true;
    },
    UpdateAlarmStatus(item, status) {
      if (status == 0) {
        if (confirm("Are you sure you want to TURN OFF the Alarm")) {
          let options = {
            params: {
              company_id: this.$auth.user.company_id,
              serial_number: item.serial_number,
              status: status,
            },
          };
          this.loading = true;
          this.$axios
            .post(`/update-device-alarm-status`, options.params)
            .then(({ data }) => {
              this.getDataFromApi();
              if (!data.status) {
                if (data.message == "undefined") {
                  this.response = "Try again. No connection available";
                } else this.response = "Try again. " + data.message;
                this.snackbar = true;

                return;
              } else {
                setTimeout(() => {
                  this.loading = false;
                  this.response = data.message;
                  this.snackbar = true;
                }, 2000);

                return;
              }
            })
            .catch((e) => console.log(e));
        }
      }
    },
    showDeviceMegviiSettings(item) {
      this.errors = [];
      this.payload = {};
      this.editedIndex = item.id;

      this.editedItem = item;
      this.loadingDeviceData = true;

      this.getDeviceCAMVIISettginsFromSDK(item.device_id);
      this.DialogDeviceMegviiSettings = true;
    },
    showDeviceCameraSettings(device) {
      return false;
    },

    updateDeviceCAMMIISettings() {
      if (confirm("Are you want to Update Device settings  ?")) {
        {
          this.deviceCAMVIISettings.voice_volume = Math.ceil(
            this.deviceCAMVIISettings.voice_volume
          );
          let options = {
            params: {
              company_id: this.$auth.user.company_id,
              deviceSettings: this.deviceCAMVIISettings,
            },
          };
          this.loading = true;
          this.$axios
            .post(`/update-device-camvii-sdk-settings`, options.params)
            .then(({ data }) => {
              if (!data.status) {
                if (data.message == "undefined") {
                  this.response = "Try again. No connection available";
                } else this.response = "Try again. " + data.message;
                this.snackbar = true;

                return;
              } else {
                setTimeout(() => {
                  this.loading = false;
                  this.response = data.message;
                  this.snackbar = true;
                }, 2000);

                return;
              }
            })
            .catch((e) => console.log(e));
        }
      }
    },
    updateDeviceSettings() {
      if (confirm("Are you want to Update Device settings  ?")) {
        if (
          this.$refs.form.validate() &&
          this.deviceSettings.menuPassword != ""
          // &&          this.deviceSettings.name != ""
        ) {
          let options = {
            params: {
              company_id: this.$auth.user.company_id,
              deviceSettings: this.deviceSettings,
            },
          };
          this.loading = true;
          this.$axios
            .post(`/update-device-sdk-settings`, options.params)
            .then(({ data }) => {
              if (!data.status) {
                if (data.message == "undefined") {
                  this.response = "Try again. No connection available";
                } else this.response = "Try again. " + data.message;
                this.snackbar = true;

                return;
              } else {
                setTimeout(() => {
                  this.loading = false;
                  this.response = data.message;
                  this.snackbar = true;
                }, 2000);

                return;
              }
            })
            .catch((e) => console.log(e));
        }
      }
    },
    findUser(item) {
      this.popupDeviceId = item.device_id;
      this.uploadedUserInfoDialog = true;
    },

    getDeviceCAMVIISettginsFromSDK(device_id) {
      if (device_id != "") {
        this.sdk_message = "";
        this.deviceSettings = {};
        this.deviceSettings.device_id = device_id;
        this.loadingDeviceData = true;
        let counter = 1;

        let options = {
          params: {
            company_id: this.$auth.user.company_id,
            device_id: device_id,
          },
        };
        this.loadingDeviceData = true;
        this.$axios
          .get(`get-device-camvii-settings-from-sdk`, options)
          .then(({ data }) => {
            this.loadingDeviceData = false;

            this.sdk_message = data.SDKresponseData.message;
            if (!data.SDKresponseData.data) {
              this.response = "Try again. " + data.message;
              this.snackbar = true;

              return;
            } else {
              this.deviceCAMVIISettings = data.SDKresponseData.data;
              this.deviceCAMVIISettings.device_id = device_id;

              return;
            }
          });
      }
    },
    getDeviceSettginsFromSDK(device_id) {
      if (device_id != "") {
        this.sdk_message = "";
        this.deviceSettings = {};
        this.deviceSettings.device_id = device_id;
        this.loadingDeviceData = true;
        let counter = 1;

        let options = {
          params: {
            company_id: this.$auth.user.company_id,
            device_id: device_id,
          },
        };
        this.loadingDeviceData = true;
        this.$axios
          .get(`get-device-settings-from-sdk`, options)
          .then(({ data }) => {
            this.loadingDeviceData = false;

            this.sdk_message = data.SDKresponseData.message;
            if (!data.SDKresponseData.data) {
              this.response = "Try again. " + data.message;
              this.snackbar = true;

              return;
            } else {
              this.deviceSettings = data.SDKresponseData.data;
              this.deviceSettings.device_id = device_id;

              this.deviceSettings.time = this.deviceSettings.time.replace(
                "T",
                " "
              );

              return;
            }
          });
      }
    },
    getUserInfoFromDevice() {
      if (this.inputFindDeviceUserId != "") {
        this.visitorUploadedDevicesInfo = [];
        this.loadingDeviceData = true;
        let counter = 1;

        let options = {
          params: {
            company_id: this.$auth.user.company_id,

            system_user_id: this.inputFindDeviceUserId,

            device_id: this.popupDeviceId,
          },
        };
        this.loadingDeviceData = true;
        this.$axios
          .get(`get-device-person-details`, options)
          .then(({ data }) => {
            this.loadingDeviceData = false;
            counter++;
            if (!data.deviceName) {
              this.response = data.message;
              this.snackbar = true;

              return;
            } else {
              data.system_user_id = this.inputFindDeviceUserId;
              data.device_id = this.popupDeviceId;

              this.visitorUploadedDevicesInfo.push(data);

              return;
            }
          });
      }
    },
    getTimezones() {
      return Object.keys(this.timeZones).map((key) => ({
        offset: this.timeZones[key].offset,
        time_zone: this.timeZones[key].time_zone,
        key: key,
        text: key + " - " + this.timeZones[key].offset,
      }));
    },
    getBranches() {
      this.$axios
        .get(`branch`, { params: { company_id: this.$auth.user.company_id } })
        .then(({ data }) => {
          this.branches = data.data;
        });
    },
    getDeviceStatus() {
      this.$axios.get(`device_status`).then(({ data }) => {
        this.device_statusses = data.data;
      });
    },
    datatable_save() { },
    datatable_cancel() {
      this.datatable_search_textbox = "";
    },
    datatable_open() {
      this.datatable_search_textbox = "";
    },
    datatable_close() {
      this.loading = false;
    },
    async sync_date_time(item) {
      if (
        confirm(
          "Are you want to change the Device Time to " +
          item.utc_time_zone +
          "?"
        )
      ) {
        try {
          const dt = new Date();
          const year = dt.getFullYear();
          const month = String(dt.getMonth() + 1).padStart(2, "0");
          const day = String(dt.getDate()).padStart(2, "0");
          const hours = String(dt.getHours()).padStart(2, "0");
          const minutes = String(dt.getMinutes()).padStart(2, "0");
          const seconds = String(dt.getSeconds()).padStart(2, "0");

          const apiUrl = `sync_device_date_time/${item.device_id}/${this.$auth.user.company_id}`;
          const sync_able_date_time = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
          const { data } = await this.$axios.get(apiUrl, {
            params: { sync_able_date_time },
          });

          this.snackbar = true;
          this.response = data.message;
        } catch (error) {
          this.snackbar = true;
          this.response = error;
          console.error("Error syncing date and time:", error);
        }
      }
    },
    closepopup() {
      this.snackbar = true;
      this.response = "Device Time details are updated successfully";
      this.dialogAccessSettings = false;
    },
    getUTC_CurentTime(targetTimezone) {
      const localDate = new Date();
      const options = {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        timeZone: targetTimezone,
        hour12: false,
      };
      const formattedDateTime = localDate.toLocaleDateString("en-US", options);

      let dt = new Date(formattedDateTime);

      let year = dt.getFullYear();
      let month = dt.getMonth() + 1;
      let day = dt.getDate();

      let hours = dt.getHours();
      hours = hours < 10 ? "0" + hours : hours;

      let minutes = dt.getMinutes();
      minutes = minutes < 10 ? "0" + minutes : minutes;

      let seconds = dt.getSeconds();
      seconds = seconds < 10 ? "0" + seconds : seconds;

      let sync_able_date_time = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

      return sync_able_date_time;
    },
    open_door(device_id) {
      let options = {
        params: { device_id },
      };
      confirm("Are you sure want to open the Door?") &&
        this.$axios.get(`open_door`, options).then(({ data }) => {
          this.snackbar = true;
          this.response = data.message;
          // this.getDataFromApi();
        });
    },
    open_door_always(device_id) {
      this.popup_device_id = device_id;
      this.dialogAccessSettings = true;

      /////////// this.$router.push(`/device/time_settings/${device_id}`);
      // let options = {
      //   params: { device_id },
      // };
      // this.$axios.get(`open_door_always`, options).then(({ data }) => {
      //   this.snackbar = true;
      //   this.response = data.message;
      //   this.getDataFromApi();
      // });
    },
    close_door(device_id) {
      let options = {
        params: { device_id },
      };
      confirm("Are you sure want to close the Door?") &&
        this.$axios.get(`close_door`, options).then(({ data }) => {
          this.snackbar = true;
          this.response = data.message;
          this.getDataFromApi();
        });
    },
    can(per) {
      return this.$pagePermission.can(per, this);
    },
    caps(str) {
      if (str == "" || str == null) {
        return "---";
      } else {
        let res = str.toString();
        return res.replace(/\b\w/g, (c) => c.toUpperCase());
      }
    },
    onPageChange() {
      this.getDataFromApi();
    },
    applyFilters() {
      this.getDataFromApi();
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
    getTemperatureList() {
      let Array = [];
      for (let index = 0; index < 100; index++) {
        Array.push(index);
      }

      return Array;
    },
    /*async getDataFromApi(
      url = this.endpoint,
      filter_column = "",
      filter_value = ""
    ) {
      // this.loading = true;
      // const data = await this.$store.dispatch("fetchData", {
      //   key: "devices",
      //   options: this.options,
      //   refresh: true,
      //   endpoint: this.endpoint,
      //   filters: this.filters,
      // });
      // this.data = data.data;
      // this.totalRowsCount = data.total;
      // this.loading = false;

      // return;

      if (url == "") url = this.endpoint;
      this.loading = true;
      let { sortBy, sortDesc, page, itemsPerPage } = this.options;

      let sortedBy = sortBy ? sortBy[0] : "";
      let sortedDesc = sortDesc ? sortDesc[0] : "";
      let options = {
        params: {
          page: page,
          sortBy: sortedBy,
          sortDesc: sortedDesc,
          per_page: itemsPerPage,
          branch_id: this.branch_id,
          company_id: this.$auth.user.company_id,
          ...this.filters,
        },
      };
      if (filter_column != "") {
        if (filter_column == "serach_status_name") {
          options.params[filter_column] =
            filter_value.toLowerCase() == "online"
              ? "active"
              : filter_value.toLowerCase() == "offline"
                ? "inactive"
                : "";
        } else options.params[filter_column] = filter_value;
      }
      await this.$axios.get(`${url}?page=${page}`, options).then(({ data }) => {
        if (filter_column != "" && data.data.length == 0) {
          this.snack = true;
          this.snackColor = "error";
          this.snackText = "No Results Found";
          this.loading = false;
          return false;
        }
        this.totalRowsCount = data.total;
        this.data = data.data;
        this.pagination.current = data.current_page;
        this.pagination.total = data.last_page;
        this.loading = false;





      });
    },*/
    connectMQTT() {

      this.loading = true;
      console.log("connecting to MQTT Alarm Page");

      const host = process.env.MQTT_SOCKET_HOST; // "wss://mqtt.xtremeguard.org:8084"; // If TLS WebSocket is available

      const clientId = "vue-client-" + Math.random().toString(16).substr(2, 8);

      this.mqttClient = mqtt.connect(host, {
        clientId: clientId,
        clean: true,
        connectTimeout: 4000,
      });

      this.mqttClient.on("connect", () => {
        console.log("✅ MQTT Connected");

        this.startHeartbeatWatcher();

        // // Subscribe to a topic
        // const topic = `xtremesos/${this.editedItem.serial_number}/config`;
        // this.mqttClient.subscribe(topic, (err) => {
        //   if (err) console.error("❌ Subscribe failed:", err);
        //   else console.log(`📡 Subscribed to ${topic}`);
        // });

        // this.sendConfigRequest();

        // let topic = `xtremesos/+/config`;

        // console.log("this.devicesList.length", this.devicesList.length);

        // if (this.devicesList.length == 1)
        //   topic = `xtremesos/${this.device_serial_number}/config`;

        // this.mqttClient.subscribe(topic, (err) => {
        //   if (err) console.error("❌ Subscribe failed:", err);
        //   else console.log(`📡 Subscribed to ${topic}`);
        // });
      });

      // this.mqttClient.on("message", (topic, payload) => {
      //   // console.log("Message", payload.toString());
      //   //console.log("topic", topic);

      //   let message = JSON.parse(payload.toString());
      //   //console.log(message);

      //   if (this.device_serial_number == message.serialNumber) {
      //     this.isMQTTConnected = true;
      //     this.checkDeviceOnlineStatus();
      //     if (message.type == "alarm") {
      //       localStorage.setItem("alarm", true);

      //       this.sendMQTTConfigRequest(); //get immediate relay data
      //       this.getDataFromApi();
      //     }

      //     // console.log("this.waitMQTTRelayUpdate", this.waitMQTTRelayUpdate);
      //     else if (message.type == "config" && !this.waitMQTTRelayUpdate) {
      //       // this.$set(this, "deviceSettings", jsonconfig); // ensures reactivity
      //       // //this.deviceSettings = jsonconfig;

      //       let config = JSON.parse(message.config);
      //       this.relayStatus[this.device_serial_number] = {};
      //       if (config) {
      //         this.relayStatus[this.device_serial_number].relay0 =
      //           config.relay0;
      //         this.relayStatus[this.device_serial_number].relay1 =
      //           config.relay1;
      //         this.relayStatus[this.device_serial_number].relay2 =
      //           config.relay2;
      //         this.relayStatus[this.device_serial_number].relay3 =
      //           config.relay3;

      //         //console.log(this.relayStatus);

      //         // this.relayStatus.relay0 = data.config.relay0;
      //         // this.relayStatus.relay1 = data.config.relay1;
      //         // this.relayStatus.relay2 = data.config.relay2;
      //         // this.relayStatus.relay3 = data.config.relay3;
      //       } else {
      //         this.relayStatus[this.device_serial_number].relay0 = false;
      //         this.relayStatus[this.device_serial_number].relay1 = false;
      //         this.relayStatus[this.device_serial_number].relay2 = false;
      //         this.relayStatus[this.device_serial_number].relay3 = false;
      //       }
      //       this.key++;

      //       this.loading = false;
      //     }
      //     // setTimeout(() => {
      //     //   this.loading = false;
      //     // }, 1000 * 5);
      //   }
      // });

      this.mqttClient.on("error", (err) => {
        console.error("MQTT Error:", err);
        setTimeout(() => {
          this.connectMQTT();
        }, 1000 * 5);
      });

      this.mqttClient.on("close", () => {
        // this.checkDeviceOnlineStatus();
        console.log("❌ MQTT Disconnected");

        this.MQTTRetryCount++;
        {
          setTimeout(() => {
            this.connectMQTT();
          }, 1000 * 5);
        }
      });
    },

    async getDataFromApi(

      url = this.endpoint,
      filter_column = "",
      filter_value = ""
    ) {
      if (url === "") url = this.endpoint;
      this.loading = true;

      const { sortBy, sortDesc, page, itemsPerPage } = this.options || {};
      const sortedBy = sortBy ? sortBy[0] : "";
      const sortedDesc = sortDesc ? sortDesc[0] : "";

      const options = {
        params: {
          page,
          sortBy: sortedBy,
          sortDesc: sortedDesc,
          per_page: itemsPerPage,
          branch_id: this.branch_id,
          company_id: this.$auth.user.company_id,
          ...this.filters,
        },
      };

      if (filter_column !== "") {
        if (filter_column === "serach_status_name") {
          options.params[filter_column] =
            filter_value.toLowerCase() === "online"
              ? "active"
              : filter_value.toLowerCase() === "offline"
                ? "inactive"
                : "";
        } else {
          options.params[filter_column] = filter_value;
        }
      }

      await this.$axios.get(`${url}?page=${page}`, options).then(({ data }) => {
        if (filter_column !== "" && data.data.length === 0) {
          this.snack = true;
          this.snackColor = "error";
          this.snackText = "No Results Found";
          this.loading = false;
          return false;
        }

        this.totalRowsCount = data.total;

        // Normalize serial + initialize online status fields
        this.data = (data.data || []).map((row) => {
          const serial =
            row.serial ||
            row.serial_number ||
            row.device_serial ||
            row.sn ||
            "";
          return {
            ...row,
            serial,
            status: 2,         // 1 = online, 2 = offline (default)
            lastSeen: null,    // timestamp (ms) of last heartbeat
          };
        });

        // Build quick index for fast updates by serial (for current page only)
        this._indexBySerial = Object.create(null);
        for (let i = 0; i < this.data.length; i++) {
          const s = this.data[i].serial;
          if (s) this._indexBySerial[s] = i;
        }

        this.pagination.current = data.current_page;
        this.pagination.total = data.last_page;

        // Start (or keep) the heartbeat watcher




      });

      await this.startHeartbeatWatcher();

      this.loading = false;
    },

    async startHeartbeatWatcher() {
      if (!this.mqttClient) {
        console.warn("MQTT client not available");
        return;
      }

      this.loading = true;

      // Subscribe once globally to all heartbeats: "/<SERIAL>/heartbeat"
      const hbTopic = "xtremesos/+/heartbeat";
      if (!this._hbSubscribed) {
        this.mqttClient.subscribe(hbTopic, (err) => {
          if (err) {
            console.error("❌ Subscribe failed:", err);
          } else {
            console.log(`📡 Subscribed to ${hbTopic}`);
            this._hbSubscribed = true;
          }
        });
      }

      // Ensure a single message handler is attached
      if (this._onMqttMessage) {
        this.mqttClient.removeListener("message", this._onMqttMessage);
      }

      this._onMqttMessage = (topic, message) => {

        try {

          console.log
            (`MQTT message on ${topic}: ${message.toString()}`);
          // Expect "/SERIAL/heartbeat"
          const m = topic.match(/^xtremesos\/([^/]+)\/heartbeat$/);

          if (!m) return;
          const serial = m[1];

          // Only update if the serial exists in current page
          const idx = this._indexBySerial[serial];
          if (idx == null) return;

          // Parse payload (optional)
          let payload = null;
          try {
            payload = JSON.parse(message.toString());
          } catch (_) { }

          const row = this.data[idx];
          const updated = {
            ...row,
            status: 1,               // mark online
            lastSeen: Date.now(),
            lastPayload: payload || null,
          };

          // Vue reactivity
          this.$set(this.data, idx, updated);
        } catch (e) { }
      };


      this.mqttClient.on("message", this._onMqttMessage);

      // Periodic sweep: mark stale devices offline (for current page)
      if (this._hbTimer) clearInterval(this._hbTimer);
      this._hbTimer = setInterval(() => {
        const now = Date.now();
        for (let i = 0; i < this.data.length; i++) {
          const d = this.data[i];
          const stale = !d.lastSeen || now - d.lastSeen > this.heartbeatTimeoutMs;
          const desired = stale ? 2 : 1;
          if (d.status !== desired) {
            this.$set(this.data, i, { ...d, status: desired });
          }
        }
      }, 1000 * 60); // check every 10s

      this.loading = false;

    },


    async updateDevicesHealth() {
      let options = {
        params: {
          company_id: this.$auth.user.company_id,
        },
      };

      await this.$axios
        .get("/check_device_health", options)
        .then(({ data }) => {
          // this.snackbar = true;
          // this.response = data;
          this.getDataFromApi();
        });

      await this.startHeartbeatWatcher();
      this.loading = false
    },

    searchIt(e) {
      if (e.length == 0) {
        this.getDataFromApi();
      } else if (e.length > 2) {
        this.getDataFromApi(`${this.endpoint}/search/${e}`);
      }
    },

    editItem(item) {
      this.errors = [];
      this.payload = {};
      this.editedIndex = item.id;

      this.payload = Object.assign({}, item);

      this.popupDeviceId = item.device_id;

      this.editDialog = true;

      this.deviceResponse = "";
    },
    showDeviceSettings(item) {
      this.key = this.key + 1;
      this.errors = [];
      this.payload = {};
      this.editedIndex = item.id;

      this.editedItem = item;
      this.loadingDeviceData = true;

      //this.getDeviceSettginsFromSDK(item.device_id);
      this.DialogDeviceSettings = true;
    },
    addItem() {
      this.payload = {};
      this.errors = [];
      if (!this.isCompany) {
        this.payload.branch_id = this.branch_id;
      }

      this.editedIndex = -1;
      this.editDialog = true;

      this.deviceResponse = "";
    },
    store_device() {
      let id = this.editedIndex;
      //let company_id = console.log(this.payload);
      let payload = this.payload;

      console.log("this.payload", this.payload);

      this.payload.company_id = this.$auth.user.company_id;
      if (this.editedIndex == -1) this.payload.status_id = 2;
      payload.ip = "0.0.0.0";
      payload.device_id = this.payload.serial_number;

      payload.port = "0000";

      delete payload.status;
      delete payload.company;
      delete payload.company_branch;

      this.loading = true;
      if (this.editedIndex == -1) {
        this.$axios
          .post(`/device`, payload)
          .then(({ data }) => {
            this.loading = false;
            if (!data.status) {
              this.errors = [];
              if (data.errors) this.errors = data.errors;

              this.snackbar = true;
              this.deviceResponse = data.message;
              this.response = data.message;
            } else if (data.status == "device_api_error") {
              this.errors = [];
              this.snackbar = true;
              this.deviceResponse =
                "Check the Device information. There are errors.";

              this.response = "Check the Device information. There are errors.";
            } else {
              this.snackbar = true;
              this.deviceResponse = "Device details are  Created successfully";
              this.response = "Device details are  Created successfully";
              this.getDataFromApi();
              this.editDialog = false;
            }
          })
          .catch((e) => console.log(e));
      } else {
        this.$axios
          .put(`/device/${id}`, payload)
          .then(({ data }) => {
            this.loading = false;
            if (!data.status) {
              this.errors = data.errors;
            } else if (data.status == "device_api_error") {
              this.errors = [];
              this.snackbar = true;
              this.deviceResponse =
                "Check the Device information. There are errors.";
              this.response = "Check the Device information. There are errors.";
            } else {
              this.snackbar = true;
              this.deviceResponse = "Device details are  updated successfully";
              this.response = "Device details are updated successfully";
              this.getDataFromApi();
              this.editDialog = false;
            }
          })
          .catch((e) => console.log(e));
      }
    },

    getFunctionIcon(item) {
      if (item.function == "auto") {
        return "/icons/function_in_out.png?t=2";
      } else if (item.function == "In") {
        return "/icons/function_in.png";
      } else if (item.function == "Out") {
        return "/icons/function_out.png";
      }
    },
    getDeviceIcon(item) {
      if (item.device_type == "all") {
        return "/icons/device_type_all.png";
      } else if (item.device_type == "Access Control") {
        return "/icons/device_type_attendance.png";
      } else if (item.device_type == "Attendance") {
        return "/icons/device_type_access_control.png";
      }
    },
    getDeviceStatusIcon(item) {
      if (item.status.name == "active") {
        return "/icons/device_status_open.png";
      } else {
        return "/icons/device_status_close.png";
      }
    },

    deleteItem(item) {
      confirm(
        "Are you sure you wish to delete , to mitigate any inconvenience in future."
      ) &&
        this.$axios
          .delete(this.endpoint + "/" + item.id)
          .then(({ data }) => {
            if (!data.status) {
              this.errors = data.errors;
            } else {
              this.snackbar = data.status;
              this.response = data.message;
              this.getDataFromApi();
            }
          })
          .catch((err) => console.log(err));
    },
  },
};
</script>
