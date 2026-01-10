<template>
  <div style="width: 100%" v-if="can('dashboard_access') && can('dashboard_view')" class="page-wrapper">
    <div class="text-center">
      <v-snackbar v-model="snackbar" top outlined elevation="24">
        {{ response }}
      </v-snackbar>
    </div>
    <div class="flip-overlay" :class="{ flipped: flipped }" @click="flipped = !flipped"></div>

    <v-row style="margin-top: 0px">
      <v-col cols="12" sm="6" md="6" v-if="!isMobileView">
        <v-card v-if="!displayLiveData" height="400px" elevation="24" loading="false" outlined
          style="border-radius: 10px">
          <AlarmDashboardTemparatureHistoryChart2Black :nameChart="'AlarmDashboardTemparatureHistoryChart2Black'"
            :height="'300'" :device_serial_number="device_serial_number" :device_temperature_serial_address="device_temperature_serial_address
              " :key="keyChart2" :from_date="from_date" :theme="'black'"
            @switchBacktoLiveData="switchBacktoLiveData()" />
        </v-card>
        <v-card v-if="displayLiveData" class="dashboard-card" height="400px" elevation="24" loading="false" outlined
          style="border-radius: 10px">
          <AlarmDashboardLiveWeather @switchBacktoHistoryData="switchBacktoHistoryData()" />
        </v-card>
      </v-col>
      <v-col cols="12" sm="6" md="6">
        <v-card class="dashboard-card" :style="isMobileView
          ? 'height: auto;'
          : 'height: 400px; border-radius: 10px;'
          " elevation="24" loading="false" outlined style="border-radius: 10px">
          <v-row justify="end" style="display: none1; margin-top: -30px">
            <v-col></v-col>
            <v-col style="max-width: 40px" v-if="devicesList.length > 1">
              <v-icon size="30" color="white" v-if="!playSlider" @click="playSlider = !playSlider">mdi-play-box</v-icon>
              <v-icon size="30" color="green" v-else @click="playSlider = !playSlider">mdi-pause-box</v-icon>
            </v-col>
            <v-col style="max-width: 300px; padding: 0px">
              <v-select style="z-index: 9999" @change="ChangeDevice()" v-model="device_serial_number_with_sensor"
                :items="devicesList" dense small outlined hide-details label="Device" class="ma-2" :item-value="(item) =>
                  `${item.serial_number}|${item.temperature_serial_address ?? 'null'
                  }`
                  " :item-text="(item) =>
                    item.temperature_sensor_name
                      ? `${item.name} - ${item.temperature_sensor_name}`
                      : item.name
                    "></v-select>
            </v-col>
            <v-col style="max-width: 50px">
              <v-icon :color="deviceOnline <= 60 && isMQTTConnected ? 'green' : 'red'">mdi-web-box</v-icon>
            </v-col>
          </v-row>
          <v-row style="display: none1">
            <v-col cols="5">
              <v-row>
                <v-col cols="12" class="text-center"><span class="pl-5"> Temperature</span></v-col>
                <!-- <v-col cols="4" class="pull-right"
                    ><v-icon @click="getDataFromApi(1)" style="float: right"
                      >mdi mdi-reload</v-icon
                    >
                  </v-col>-->
              </v-row>

              <v-row>
                <v-col v-if="!isMobileView" cols="2" class="align-items-center justify-content-center pt-0">
                  <!-- <img
                    src="../../static/alarm-icons/temperature.png"
                    width="70px"
                /> -->
                </v-col>
                <v-col cols="10" class="pa-0" style="margin-top: -30px">
                  <TemperatureChartMobile v-if="isMobileView" id="1" style="width: 100px"
                    :name="'ArrowArcChartTemperature1'" :temperature="temperature_latest" :key="Sensorkey"
                    :temperature_date_time="temperature_date_time" :width="isMobileView ? 100 : 300" />
                  <TemperatureChart3 v-else :name="'ArrowArcChartTemperature1'" :temperature="temperature_latest"
                    :key="Sensorkey + '1'" :temperature_date_time="temperature_date_time" />
                  <!-- <ArrowArcChartTemperature
                    :name="'ArrowArcChartTemperature1'"
                    :height="'380'"
                    :temperature_latest="temperature_latest"
                    :temperature_date_time="temperature_date_time"
                    :key="key"
                  /> -->
                </v-col>
              </v-row>
            </v-col>
            <v-col cols="2" class="d-flex justify-center align-center d-none d-md-block">
              <v-divider inset vertical style="color: #cfcece"></v-divider>
            </v-col>

            <v-col cols="5">
              <v-divider v-if="isMobileView" inset horizontal style="color: #cfcece"></v-divider>
              <!-- <v-divider
                v-else
                inset
                vertical
                style="color: #cfcece"
              ></v-divider> -->

              <!-- <v-divider inset vertical style="color: #cfcece"></v-divider> -->
              <v-row>
                <v-col cols="12" class="text-center"><span class="pl-5"> Humidity</span></v-col>
                <!--<v-col cols="4" class="pull-right"
                    ><v-icon @click="getDataFromApi(1)" style="float: right"
                      >mdi mdi-reload</v-icon
                    >
                  </v-col>-->
              </v-row>

              <v-row>
                <v-col v-if="!isMobileView" cols="2" class="align-items-center justify-content-center pt-0">
                  <!-- <img
                    src="../../static/alarm-icons/humidity.png"
                    width="70px"
                /> -->
                </v-col>
                <v-col cols="10" class="pa-0" style="margin-top: -30px">
                  <HumidityChart3Mobile v-if="isMobileView" :name="'ArrowArcChart2Humidity'" :humidity="humidity_latest"
                    :key="Sensorkey + '2'" :humidity_date_time="humidity_date_time" />
                  <HumidityChart3 v-else :name="'ArrowArcChart2Humidity'" :humidity="humidity_latest" :key="Sensorkey"
                    :humidity_date_time="humidity_date_time" />
                  <!-- <ArrowArcChart2Humidity
                    :name="'ArrowArcChart2Humidity'"
                    :height="'380'"
                    :humidity_latest="humidity_latest"
                    :humidity_date_time="humidity_date_time"
                    :key="key"
                  /> -->
                  <!-- <AlarmDashboardHumidityChart1Black
                    :name="'AlarmDashboardHumidityChart1Black'"
                    :height="'220'"
                    :humidity_latest="humidity_latest"
                    :humidity_date_time="humidity_date_time"
                    :key="key"
                  /> -->
                </v-col>
              </v-row>
            </v-col>
          </v-row>
        </v-card></v-col>
    </v-row>
    <v-row>
      <v-col cols="12" sm="6" md="6">
        <AlarmDashboardFooterBlack :loading="loading" :device="device" :key="key"
          :relayStatus="relayStatus[device.serial_number]" @manualButtonTriggered="manualButtonTriggered()" />
      </v-col>
      <v-col cols="12" sm="6" md="6" v-if="!isMobileView">
        <v-card class="dashboard-card" height="380px" elevation="24" loading="false" outlined
          style="border-radius: 10px; height: 380px">
          <AlarmDashboardTemparatureChart2Black :name="'AlarmDashboardTemparatureChart2'" :height="'300'"
            :device_temperature_serial_address="device_temperature_serial_address
              " :key="'AlarmDashboardTemparatureChart2' + keyChart2" :device_serial_number="device_serial_number"
            :from_date="from_date" />
        </v-card></v-col>
    </v-row>
  </div>
  <!-- https://api.weatherapi.com/v1/current.json?key=6619ca39981a4e4a9c7153233250605&q=Dubai&aqi=no -->

  <NoAccess v-else />
</template>

<script>
import AlarmDashboardFooterBlack from "../../components/Alarm/Dashboard/AlarmDashboardFooterBlack.vue";
import AlarmDashboardHumidityChart1Black from "../../components/Alarm/Dashboard/AlarmDashboardHumidityChart1Black.vue";
import AlarmDashboardLiveWeather from "../../components/Alarm/Dashboard/AlarmDashboardLiveWeather.vue";
import ArrowArcChartTemperature from "../../components/Alarm/Dashboard/ArrowArcChartTemperature.vue";
import AlarmDashboardTemparatureChart2Black from "../../components/Alarm/Dashboard/AlarmDashboardTemparatureChart2Black.vue";
import AlarmDashboardTemparatureHistoryChart2Black from "../../components/Alarm/Dashboard/AlarmDashboardTemparatureHistoryChart2Black.vue";
import ArrowArcChart2 from "../../components/Alarm/Dashboard/ArrowArcChart2Sample.vue";
import ArrowArcChart2Humidity from "../../components/Alarm/Dashboard/ArrowArcChart2Humidity.vue";
import TemperatureChart3 from "../../components/Alarm/Dashboard/TemperatureChart3.vue";

import HumidityChart3 from "../../components/Alarm/Dashboard/HumidityChart3.vue";
import mqtt from "mqtt";
import TemperatureChartMobile from "../../components/Alarm/Dashboard/TemperatureChartMobile.vue";
import HumidityChart3Mobile from "../../components/Alarm/Dashboard/HumidityChart3Mobile.vue";

export default {
  // layout: "black",
  components: {
    AlarmDashboardTemparatureChart2Black,
    ArrowArcChartTemperature,
    AlarmDashboardHumidityChart1Black,
    AlarmDashboardLiveWeather,
    AlarmDashboardTemparatureHistoryChart2Black,
    ArrowArcChart2,
    ArrowArcChart2Humidity,
    TemperatureChart3,
    HumidityChart3,
    TemperatureChartMobile,
    HumidityChart3Mobile,
  },
  data() {
    return {
      Sensorkey: 1,
      mqttClient: null,
      configPayload: "",
      playSlider: true,
      snackbar: false,
      response: "",
      currentDeviceIndex: 0,
      autoCycleInterval: null,
      flipped: true,
      device_serial_number_with_sensor: null,
      device_temperature_serial_address: null,
      displayLiveData: false,
      temperature: "",
      weatherCondition: "",
      humidity: "",
      key: 1,
      temperature_latest: 25,
      temperature_date_time: "2025-05-06 10:25:10",
      humidity_latest: 50,
      humidity_date_time: "2025-05-06 10:25:10",
      device: null,
      from_date: "",
      from_menu: false,
      selectedDeviceIndex: 0,
      audioSrc: null,
      topMenu: 0,
      key: 1,
      keyChart2: 1,
      branchList: [],
      selectedBranchName: "All Branches",
      seelctedBranchId: "",
      branch_id: "",
      overlay: false,
      temperature_latest: 0,
      temperature_date_time: "---",
      temperature_min: 0,
      temperature_max: 0,
      temperature_min_date_time: 0,
      temperature_max_date_time: 0,
      temperature_hourly_data: {},
      fire_alarm_start_datetime: "---",
      device_serial_number: "",

      humidity_latest: 0,
      humidity_date_time: "---",
      humidity_min: 0,
      humidity_max: 0,
      humidity_min_date_time: 0,
      humidity_max_date_time: 0,
      device: {
        fire_alarm_status: 0,
        fire_alarm_start_datetime: 0,
        water_alarm_status: 0,
        water_alarm_start_datetime: 0,
        power_alarm_status: 0,
        power_alarm_start_datetime: 0,
        door_open_status: 0,
        door_open_start_datetime: 0,
        smoke_alarm_status: 0,
        smoke_alarm_start_datetime: 0,
      },
      devicesList: [],
      relayStatus: [],

      intervalObj: null,
      viewportHeight: 0,
      mqtt_alarm_timestamp: 0,
      loading: false,
      waitMQTTRelayUpdate: false,
      isMQTTConnected: false,
      MQTTRetryCount: 0,
      lastMQTTSendTime: 0,
      deviceOnline: 0, // will store the evaluated result
      isMobileView: false,
    };
  },
  beforeDestroy() {
    console.log("Cleaning up resources...");

    if (this.autoCycleInterval) {
      clearInterval(this.autoCycleInterval);
    }

    // 1. Clear interval (with null check)
    if (this.intervalObj) {
      clearInterval(this.intervalObj);
      this.intervalObj = null; // Prevent memory leaks
    }

    this.mqttClient.end(false, () => {
      console.log("🔌 MQTT Disconnected");

      this.isMQTTConnected = false;
    });
  },
  // watch: {
  //   from_date(val) {},
  // },

  watch: {
    "$vuetify.breakpoint.smAndDown"(val) {
      this.isMobileView = val;
      console.log("View changed:", val ? "Mobile" : "Desktop");
    },
  },
  computed: {
    // deviceOnlineStatus() {
    //   if (this.device_serial_number) {
    //     return this.$dateFormat.devicetimedifferenceInMin(
    //       this.getDeviceBySerialNumber(this.device_serial_number)
    //         .last_live_datetime,
    //       this.getDeviceBySerialNumber(this.device_serial_number).utc_time_zone
    //     ) < 1
    //       ? true
    //       : false;
    //   } else return 10;
    // },
  },
  async mounted() {
    console.log(this.$vuetify.breakpoint.smAndDown);

    this.isMobileView = this.$vuetify.breakpoint.smAndDown ? true : false;
    this.loading = true;
    this.connectMQTT();
    // setTimeout(() => {}, 1000 * 3);
    // if (window) {
    //   this.viewportHeight = window.innerHeight;
    //   window.addEventListener("resize", this.handleResize);
    // }
    // if (this.$auth.user.user_type == "employee") {
    //   this.$router.push(`/dashboard/employee`);
    // }

    if (this.$auth.user.branch_id == 0 && this.$auth.user.is_master == false) {
      alert("You do not have permission to access  ");
      //this.$router.push("/login");
      this.$axios.get(`/logout`).then(({ res }) => {
        this.$auth.logout();
        this.$router.push(`/login`);
      });

      this.$router.push(`/login`);
      return "";
    }
    this.loading = true;
    setTimeout(() => { }, 1000);
    // setInterval(() => {

    // }, 1000 * 5);
    setInterval(() => {
      this.keyChart2++;
    }, 1000 * 60 * 15);
    ///this.getDataFromApi(1);

    setInterval(async () => {
      if (
        this.$route.name == "alarm-dashboard" &&
        // this.devicesList.length == 1 &&
        !this.waitMQTTRelayUpdate
      ) {
        const now = Date.now();
        // if (!this.playSlider || this.devicesList?.length == 1)
        if (now - this.lastMQTTSendTime > 1000 * 10) {
          // 10 seconds)
          //////await this.sendMQTTConfigRequest(); //publish/send request Config Request to Device
          // this.loading = true;
          await this.getDataFromApi();
          this.key++;
          this.lastMQTTSendTime = now;

          this.checkDeviceOnlineStatus();
        }

        //if (now - this.lastMQTTSendTime > 1000 * 15)
      }
    }, 1000 * 10);

    // setTimeout(() => {
    //   setInterval(() => {
    //     if (this.$route.name == "alarm-dashboard")
    //       if (!this.isMQTTConnected) this.connectMQTT();
    //   }, 1000 * 5);
    // }, 1000 * 30);
  },

  async created() {
    // this.$root.layoutMethod(); // if layoutMethod is defined globally (rare)
    // // OR
    // if (this.$nuxt && this.$nuxt.layoutMethod) {
    //   this.$nuxt.layoutMethod(); // ✅ This works in Nuxt 2
    // }
    try {
      if (window) {
        const viewportHeight = window.innerHeight;
        // console.log("Visible content height:", viewportHeight, "px");

        const contentHeight = document.documentElement.clientHeight;
        //  // console.log(
        //     "Content height (excludes scrollbar):",
        //     contentHeight,
        //     "px"
        //   );
      }
    } catch (e) { }
    const today = new Date();

    this.from_date = today.toISOString().slice(0, 10);
    if (this.$auth.user.branch_id == 0 && this.$auth.user.is_master == false) {
      alert("You do not have permission to access this branch");
      //this.$router.push("/login");
      this.$axios.get(`/logout`).then(({ res }) => {
        this.$auth.logout();
        this.$router.push(`/login`);
      });

      this.$router.push(`/login`);
      return "";
    }

    try {
      await this.getDevicesListwithSensor();
    } catch (error) {
      console.error("Error fetching data:", error);
    }
    this.getDataFromApi();

    if (this.devicesList.length) {
      console.log("this.currentDeviceIndex", this.devicesList.length);
      await this.sendMQTTConfigRequest();
    }

    // setTimeout(() => {
    //   this.mqttClient.end(true, () => {
    //     console.log("🔌 MQTT Disconnected");
    //     this.isMQTTConnected = false;
    //   });
    // }, 1000 * 60);

    // if (this.MQTTRetryCount > 10) {
    // }

    setTimeout(() => {
      this.checkDeviceOnlineStatus();
    }, 1000 * 10);
  },

  methods: {
    async getDevicesListwithSensor() {
      // await this.$store.dispatch("fetchDropDowns", {
      //   key: "deviceList",
      //   endpoint: "device-list",
      //   refresh: true,
      // });
      // this.devicesList = this.$store.state.deviceList;

      let { data: devices } = await this.$axios.get(`devices_list_sensors`, {
        params: { company_id: this.$auth.user.company_id },
      });
      // console.log("devices", devices);

      this.devicesList = devices;

      this.devicesList = this.devicesList.filter(
        (item) => item.serial_number != null
      );

      if (this.devicesList && this.devicesList[0]) {
        this.device_serial_number_with_sensor = `${this.devicesList[0].serial_number
          }|${this.devicesList[0].temperature_serial_address ?? "null"}`;
        this.device_serial_number = this.devicesList[0].serial_number;
        this.device_temperature_serial_address =
          this.devicesList[0].temperature_serial_address;

        //this.getDataFromApi();
        if (this.devicesList.length > 1) this.startAutoDeviceCycle();
      }

      this.devicesList.forEach((element) => {
        this.relayStatus[element.serial_number] = {};

        for (let i = 0; i < 4; i++) {
          this.relayStatus[element.serial_number][`relay${i}`] = false;
        }
      });

      // await this.$store.dispatch("fetchDropDowns", {
      //   key: "employeeList",
      //   endpoint: "employee-list",
      //   refresh: true,
      // });
      this.branchList = await this.$store.dispatch("fetchDropDowns", {
        key: "branchList",
        endpoint: "branch-list",
        refresh: true,
      });
    },
    async checkDeviceOnlineStatus() {
      if (this.device_serial_number) {
        let device = await this.$axios.get(
          "device-by-serial-number/" + this.device_serial_number,
          {
            params: {
              company_id: this.$auth.user.company_id,
            },
          }
        );

        this.deviceOnline = this.$dateFormat.devicetimedifferenceInMin(
          device.data.last_live_datetime,
          device.data.utc_time_zone
        );

        console.log(
          "device",
          device.data.last_live_datetime,
          this.deviceOnline
        );
      }

      //this.deviceOnline = this.deviceOnline;
      // console.log("Device online:", this.deviceOnline);
    },
    // async getDeviceBySerialNumber(serialNumber) {
    //   // console.log("data", device.data);
    //   return device.data;

    //   // return this.devicesList.find(
    //   //   (item) => item.serial_number == serialNumber
    //   // );
    // },
    manualButtonTriggered() {
      //console.log("manualButtonTriggered");

      if (!this.waitMQTTRelayUpdate) {
        this.waitMQTTRelayUpdate = true;

        setTimeout(() => {
          this.waitMQTTRelayUpdate = false;
        }, 1000 * 8);
      }
    },
    connectMQTT() {
      if (
        this.MQTTRetryCount > 10 ||
        this.$route.name != "alarm-dashboard" ||
        this.isMQTTConnected
      )
        return false;
      this.loading = true;
      console.log("connecting to MQTT");

      const host = this.$store.state.env.MQTT_SOCKET_HOST;// process.env.MQTT_SOCKET_HOST; // "wss://mqtt.xtremeguard.org:8084"; // If TLS WebSocket is available

      const clientId = "vue-client-" + Math.random().toString(16).substr(2, 8);

      this.mqttClient = mqtt.connect(host, {
        clientId: clientId,
        clean: true,
        connectTimeout: 4000,
      });

      this.mqttClient.on("connect", () => {
        console.log("✅ MQTT Connected");

        // // Subscribe to a topic
        // const topic = `xtremesos/${this.editedItem.serial_number}/config`;
        // this.mqttClient.subscribe(topic, (err) => {
        //   if (err) console.error("❌ Subscribe failed:", err);
        //   else console.log(`📡 Subscribed to ${topic}`);
        // });

        // this.sendConfigRequest();

        let topic = `xtremesos/+/config`;

        console.log("this.devicesList.length", this.devicesList.length);

        if (this.devicesList.length == 1)
          topic = `xtremesos/${this.device_serial_number}/config`;

        this.mqttClient.subscribe(topic, (err) => {
          if (err) console.error("❌ Subscribe failed:", err);
          else console.log(`📡 Subscribed to ${topic}`);
        });
      });

      this.mqttClient.on("message", (topic, payload) => {
        // console.log("Message", payload.toString());
        //console.log("topic", topic);

        let message = JSON.parse(payload.toString());
        //console.log(message);

        if (this.device_serial_number == message.serialNumber) {
          this.isMQTTConnected = true;
          this.checkDeviceOnlineStatus();
          if (message.type == "alarm") {
            localStorage.setItem("alarm", true);

            this.sendMQTTConfigRequest(); //get immediate relay data
            this.getDataFromApi();
          }

          // console.log("this.waitMQTTRelayUpdate", this.waitMQTTRelayUpdate);
          else if (message.type == "config" && !this.waitMQTTRelayUpdate) {
            // this.$set(this, "deviceSettings", jsonconfig); // ensures reactivity
            // //this.deviceSettings = jsonconfig;

            let config = JSON.parse(message.config);
            this.relayStatus[this.device_serial_number] = {};
            if (config) {
              this.relayStatus[this.device_serial_number].relay0 =
                config.relay0;
              this.relayStatus[this.device_serial_number].relay1 =
                config.relay1;
              this.relayStatus[this.device_serial_number].relay2 =
                config.relay2;
              this.relayStatus[this.device_serial_number].relay3 =
                config.relay3;

              //console.log(this.relayStatus);

              // this.relayStatus.relay0 = data.config.relay0;
              // this.relayStatus.relay1 = data.config.relay1;
              // this.relayStatus.relay2 = data.config.relay2;
              // this.relayStatus.relay3 = data.config.relay3;
            } else {
              this.relayStatus[this.device_serial_number].relay0 = false;
              this.relayStatus[this.device_serial_number].relay1 = false;
              this.relayStatus[this.device_serial_number].relay2 = false;
              this.relayStatus[this.device_serial_number].relay3 = false;
            }
            this.key++;

            this.loading = false;
          }
          // setTimeout(() => {
          //   this.loading = false;
          // }, 1000 * 5);
        }
      });

      this.mqttClient.on("error", (err) => {
        console.error("MQTT Error:", err);
        setTimeout(() => {
          this.connectMQTT();
        }, 1000 * 5);
      });

      this.mqttClient.on("close", () => {
        this.checkDeviceOnlineStatus();
        console.log("❌ MQTT Disconnected");

        this.MQTTRetryCount++;
        {
          setTimeout(() => {
            this.connectMQTT();
          }, 1000 * 5);
        }
      });
    },

    async sendMQTTConfigRequest() {
      if (!this.device_serial_number) return false;

      if (this.mqttClient && this.mqttClient.connected) {
        console.log("✅ MQTT connection is active");
      } else {
        console.log("❌ MQTT connection is inactive or not established");
        // this.connectMQTT();

        // this.sendMQTTConfigRequest();
      }
      const topic = `xtremesos/${this.device_serial_number}/config/request`;
      const payload = "GET_CONFIG";

      this.mqttClient.publish(topic, payload, {}, (err) => {
        if (err) {
          console.error("❌ Publish failed:", err);
        } else {
          console.log(`📤 Published to ${topic}:`, payload);
        }
      });
    },
    can(per) {
      return this.$pagePermission.can(per, this);
    },
    switchBacktoLiveData() {
      this.displayLiveData = true;
    },
    switchBacktoHistoryData() {
      this.displayLiveData = false;
    },

    handleResize() {
      if (window) this.viewportHeight = window.innerHeight;
    },
    startAutoDeviceCycle() {
      console.log("this.devicesList.lengt", this.devicesList.length);
      //if (!this.devicesList.length) return;

      this.autoCycleInterval = setInterval(() => {
        if (this.playSlider) {
          this.currentDeviceIndex =
            (this.currentDeviceIndex + 1) % this.devicesList.length;

          console.log("currentDeviceIndex", this.currentDeviceIndex);
          const item = this.devicesList[this.currentDeviceIndex];
          this.device_serial_number = item.serial_number;
          this.device_serial_number_with_sensor = `${item.serial_number}|${item.temperature_serial_address ?? "null"
            }`;
          this.flipped = !this.flipped;

          setTimeout(() => {
            this.flipped = !this.flipped;
          }, 1000 * 1);
          this.ChangeDevice(); // trigger data update
          let sensorName =
            item.temperature_sensor_name != null
              ? " - " + item.temperature_sensor_name
              : "";
          this.response = "Loading Temperature from " + item.name + sensorName;
          this.snackbar = true;
        }
      }, 1000 * 30); // 30 seconds
    },

    ChangeDevice() {
      this.loading = true;
      const [serial_number, device_temperature_serial_address] =
        this.device_serial_number_with_sensor.split("|");

      this.device_serial_number = serial_number;
      this.device_temperature_serial_address =
        device_temperature_serial_address == "null"
          ? null
          : device_temperature_serial_address;

      this.key++;
      this.keyChart2++;

      this.getDataFromApi();

      this.sendMQTTConfigRequest();
      //console.log(this.device_serial_number, " this.device_serial_number");
    },
    async getDataFromApi() {
      // if (reset == 1) {
      //   this.keyChart2++;
      // }
      try {
        if (
          this.device_serial_number == "" ||
          this.device_serial_number == null
        )
          return false;
        // if (this.$store.state.alarm_temparature_latest && reset == 0) {
        //   this.data = this.$store.state.alarm_temparature_latest;
        // } else

        {
          this.$axios
            .get("alarm_dashboard_get_temparature_latest", {
              params: {
                company_id: this.$auth.user.company_id,

                device_serial_number: this.device_serial_number,
                device_temperature_serial_address:
                  this.device_temperature_serial_address,
              },
            })
            .then(({ data }) => {
              this.data = data;
              this.device = data.device;
              this.loading = false;
              this.$store.commit(
                "AlarmDashboard/alarm_temparature_latest",
                data
              );

              let previousTemperature = this.temperature_latest;
              let previousHumidity = this.humidity_latest;

              this.temperature_latest = data.temperature_latest;
              this.temperature_date_time = this.$dateFormat.format4(
                data.temperature_date_time
              );

              this.temperature_min = data.temperature_min + "&deg;C";
              this.temperature_max = data.temperature_max + "&deg;C";
              this.temperature_min_date_time = this.$dateFormat.format6(
                data.temperature_min_date_time
              );
              this.temperature_max_date_time = this.$dateFormat.format6(
                data.temperature_max_date_time
              );
              // this.temperature_hourly_data = data.houry_data;
              if (data.fire_alarm_start_datetime)
                this.fire_alarm_start_datetime = this.$dateFormat.format4(
                  data.fire_alarm_start_datetime
                );

              //humidity
              this.humidity_latest = data.humidity_latest;
              this.humidity_date_time = this.$dateFormat.format4(
                data.humidity_date_time
              );

              this.humidity_min = data.humidity_min + "%";
              this.humidity_max = data.humidity_max + "%";
              this.humidity_min_date_time = this.$dateFormat.format6(
                data.humidity_min_date_time
              );
              this.humidity_max_date_time = this.$dateFormat.format6(
                data.humidity_max_date_time
              );

              this.key = this.key + 1;

              if (
                this.temperature_latest != previousTemperature ||
                this.humidity_latest != previousHumidity
              ) {
                this.Sensorkey++;
              }
            });
        }
      } catch (e) { }
    },
  },
};
</script>
<style scoped>
.page-wrapper {
  position: relative;

  /* overflow: hidden; */
}

.main-content {
  z-index: 1;
  position: relative;
  padding: 60px;
  text-align: center;
  font-size: 20px;
}

/* Flip overlay that covers the full body */
.flip-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(63, 81, 181, 0.2);
  /* Indigo overlay */
  pointer-events: none;
  z-index: 999;
  transform: rotateY(0deg);
  transform-style: preserve-3d;
  backface-visibility: hidden;
  transition: transform 0.6s ease;
}

.flip-overlay.flipped {
  transform: rotateY(180deg);
}
</style>
