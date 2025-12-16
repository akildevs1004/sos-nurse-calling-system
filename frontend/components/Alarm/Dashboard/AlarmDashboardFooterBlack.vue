<template>
  <div style="width: 100%" v-if="can('dashboard_access') && can('dashboard_view')">
    <v-row :key="key">
      <v-col cols="3" sm="6" md="3" style="padding-left: 10px; padding-right: 5px">
        <v-card :style="$vuetify.breakpoint.smAndDown
          ? 'padding:5px!important; border-radius: 20px!important; text-align: center;font-size:14px'
          : 'border-radius: 20px; text-align: center'
          " :class="device.fire_alarm_status == 0
            ? 'dashboard-card-small'
            : 'dashboard-card-small-pink'
            " :height="$vuetify.breakpoint.smAndDown ? '160px' : '180px'" elevation="24" loading="false" outlined>
          <div class="d-flex flex-column align-center justify-center fill-height">
            <img v-if="device.fire_alarm_status || device.smoke_alarm_status"
              src="../../../static/blue-dashboard-icons/fire_alarm.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
                " /><img v-else src="../../../static/blue-dashboard-icons/fire.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
                  " />

            <div>Smoke/Fire</div>

            <div v-if="!$vuetify.breakpoint.smAndDown" :style="getPriorityColor(device.fire_alarm_status)">
              <div style="font-size: 12px">
                {{
                  device.fire_alarm_start_datetime == null
                    ? "---"
                    : $dateFormat.format4(device.fire_alarm_start_datetime)
                }}
              </div>
            </div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="3" sm="6" md="3" style="padding-left: 10px; padding-right: 5px">
        <v-card :style="$vuetify.breakpoint.smAndDown
          ? 'padding:5px!important; border-radius: 20px!important; text-align: center;font-size:14px'
          : 'border-radius: 20px; text-align: center'
          " :class="device.water_alarm_status == 0
            ? 'dashboard-card-small'
            : 'dashboard-card-small-pink'
            " :height="$vuetify.breakpoint.smAndDown ? '160px' : '180px'" elevation="24" loading="false" outlined>
          <div style="margin: auto">
            <!-- <img
              src="../../../static/alarm-icons/water-leakage.png"
              :style="
                $vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
              "
            /> -->
            <img v-if="device.water_alarm_status" src="../../../static/blue-dashboard-icons/water_alarm.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
              " /><img v-else src="../../../static/blue-dashboard-icons/water.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
                " />
            <div>Water</div>
            <div style="font-size: 12px" v-if="!$vuetify.breakpoint.smAndDown">
              <div :style="getPriorityColor(device.water_alarm_status)">
                {{
                  device.water_alarm_start_datetime == null
                    ? "---"
                    : $dateFormat.format4(device.water_alarm_start_datetime)
                }}
              </div>
            </div>
          </div>
        </v-card> </v-col><v-col cols="3" sm="6" md="3" style="padding-left: 10px; padding-right: 5px">
        <v-card :style="$vuetify.breakpoint.smAndDown
          ? 'padding:5px!important; border-radius: 20px!important; text-align: center;font-size:14px'
          : 'border-radius: 20px; text-align: center'
          " :class="device.power_alarm_status == 0
            ? 'dashboard-card-small'
            : 'dashboard-card-small-pink'
            " :height="$vuetify.breakpoint.smAndDown ? '160px' : '180px'" elevation="24" outlined>
          <div style="margin: auto">
            <img v-if="device.power_alarm_status" src="../../../static/blue-dashboard-icons/power_alarm.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
              " /><img v-else src="../../../static/blue-dashboard-icons/power.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
                " />
            <div>Power</div>
            <div style="font-size: 12px" v-if="!$vuetify.breakpoint.smAndDown">
              <div :style="getPriorityColor(device.power_alarm_status)">
                {{
                  device.power_alarm_start_datetime == null
                    ? "---"
                    : $dateFormat.format4(device.power_alarm_start_datetime)
                }}
              </div>
            </div>
          </div>
        </v-card> </v-col><v-col cols="3" sm="6" md="3" style="padding-left: 10px; padding-right: 5px">
        <v-card :class="device.door_open_status == 0
          ? 'dashboard-card-small'
          : 'dashboard-card-small-pink'
          " :height="$vuetify.breakpoint.smAndDown ? '160px' : '180px'" elevation="24" loading="false" outlined :style="$vuetify.breakpoint.smAndDown
            ? 'padding:5px!important; border-radius: 20px!important; text-align: center;font-size:14px'
            : 'border-radius: 20px; text-align: center'
            ">
          <div style="margin: auto">
            <img v-if="device.door_open_status" src="../../../static/blue-dashboard-icons/door_alarm.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
              " /><img v-else src="../../../static/blue-dashboard-icons/door.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
                " />
            <div>Door</div>
            <div style="font-size: 12px" v-if="!$vuetify.breakpoint.smAndDown">
              <div :style="getPriorityColor(device.door_open_status)">
                {{
                  device.door_open_start_datetime == null
                    ? "---"
                    : $dateFormat.format4(device.door_open_start_datetime)
                }}
              </div>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>
    <!-- <div
      v-if="$vuetify.breakpoint.smAndDown"
      style="
        color: #cfcece;
        border-bottom: 1px solid #ddd;
        margin-top: 10px;
        margin-bottom: 10px;
      "
    ></div> -->
    <v-row>
      <v-col cols="3" sm="6" md="3" style="padding-left: 10px; padding-right: 5px">
        <v-card :class="relayStatus && relayStatus.relay0
          ? 'border3 dashboard-card-small-pink'
          : 'border3 dashboard-card-small'
          " :height="$vuetify.breakpoint.smAndDown ? '160px' : '180px'" elevation="24" :loading="loading" outlined
          :style="'cursor:' +
            (loading ? 'progress' : 'pointer') +
            $vuetify.breakpoint.smAndDown
            ? 'padding:5px!important; border-radius: 20px!important; text-align: center;font-size:14px'
            : 'border-radius: 20px; text-align: center'
            " @click="relayCommand('relay0', relayStatus.relay0)">
          <div style="margin: auto">
            <img src="../../../static/blue-dashboard-icons/ac.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
              " />
            <div>A/C {{ relayStatus?.relay0 ? "On" : "Off" }}</div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="3" sm="6" md="3" style="padding-left: 10px; padding-right: 5px">
        <v-card :class="relayStatus && relayStatus.relay1
          ? 'border3 dashboard-card-small-pink'
          : 'border3 dashboard-card-small'
          " :height="$vuetify.breakpoint.smAndDown ? '160px' : '180px'" elevation="24" :loading="loading" outlined
          @click="relayCommand('relay1', relayStatus.relay1)" :style="'cursor:' +
            (loading ? 'progress' : 'pointer') +
            $vuetify.breakpoint.smAndDown
            ? 'padding:5px!important; border-radius: 20px!important; text-align: center;font-size:14px'
            : 'border-radius: 20px; text-align: center'
            ">
          <div style="margin: auto">
            <img src="../../../static/blue-dashboard-icons/fan.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
              " />
            <div>Fan {{ relayStatus?.relay1 ? "On" : "Off" }}</div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="3" sm="6" md="3" style="padding-left: 10px; padding-right: 5px">
        <!-- {{ relayStatus.relay2 }} -->
        <v-card :class="relayStatus && relayStatus.relay2
          ? 'border3 dashboard-card-small-pink'
          : 'border3 dashboard-card-small'
          " :height="$vuetify.breakpoint.smAndDown ? '160px' : '180px'" elevation="24" :loading="loading" outlined
          @click="relayCommand('relay2', relayStatus.relay2)" :style="'cursor:' +
            (loading ? 'progress' : 'pointer') +
            $vuetify.breakpoint.smAndDown
            ? 'padding:5px!important; border-radius: 20px!important; text-align: center;font-size:14px'
            : 'border-radius: 20px; text-align: center'
            ">
          <div style="margin: auto">
            <img src="../../../static/blue-dashboard-icons/lamp.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
              " />
            <div>Lamp {{ relayStatus?.relay2 ? "On" : "Off" }}</div>
          </div>
        </v-card>
      </v-col>

      <v-col cols="3" sm="6" md="3" style="padding-left: 10px; padding-right: 5px">
        <v-card :class="relayStatus && relayStatus.relay3
          ? 'border3 dashboard-card-small-pink'
          : 'border3 dashboard-card-small'
          " :height="$vuetify.breakpoint.smAndDown ? '160px' : '180px'" elevation="24" :loading="loading" outlined
          @click="relayCommand('relay3', relayStatus.relay3)" :style="'cursor:' +
            (loading ? 'progress' : 'pointer') +
            $vuetify.breakpoint.smAndDown
            ? 'padding:5px!important; border-radius: 20px!important; text-align: center;font-size:14px'
            : 'border-radius: 20px; text-align: center'
            ">
          <div style="margin: auto">
            <img src="../../../static/blue-dashboard-icons/siren.png" :style="$vuetify.breakpoint.smAndDown ? 'width:60px' : 'width:80px'
              " />
            <div>Siren {{ relayStatus?.relay3 ? "On" : "Off" }}</div>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </div>

  <NoAccess v-else />
</template>

<script>
// import mqtt from "mqtt";

export default {
  props: ["device", "relayStatus", "loading"],
  data() {
    return {
      mqqtt_response_status: "",
      mqttClient: null,
      configPayload: "",
      // relayStatus: {},
      deviceSettings: null,
      branchList: [],
      selectedBranchName: "All Branches",
      seelctedBranchId: "",
      branch_id: "",
      overlay: false,
      intervalObj: null,
      // loading: true,
      key: 1,
    };
  },
  beforeDestroy() {
    // 1. Clear interval (with null check)
    if (this.intervalObj) {
      clearInterval(this.intervalObj);
      this.intervalObj = null; // Prevent memory leaks
    }
  },
  // watch: {
  //   branch_id(branch_id) {
  //     return branch_id > 0 ? branch_id : null;
  //   },
  // },
  mounted() {
    // // if (this.$auth.user.user_type == "employee") {
    // //   this.$router.push(`/dashboard/employee`);
    // // }
    // if (this.$auth.user.branch_id == 0 && this.$auth.user.is_master == false) {
    //   alert("You do not have permission to access this branch");
    //   //this.$router.push("/login");
    //   this.$axios.get(`/logout`).then(({ res }) => {
    //     this.$auth.logout();
    //     this.$router.push(`/login`);
    //   });
    //   this.$router.push(`/login`);
    //   return "";
    // }
    // setInterval(() => {
    //   if (this.$route.name == "alarm-dashboard") this.getDeviceSettings();
    // }, 1000 * 15);
    //////////this.connectMQTT();
  },
  async created() {
    // setTimeout(() => {
    /////////this.getDeviceSettings();
    // }, 1000 * 15);
    // setTimeout(() => {
    //   this.loading = false;
    //   console.log("loading", this.loading);
    // }, 1000 * 5);
  },
  watch: {
    relayStatus: {
      deep: true,
      handler(val) {
        console.log("relayStatus updated:", val);
      },
    },
    // overlay(val) {
    //   val &&
    //     setTimeout(() => {
    //       this.overlay = false;
    //     }, 3000);
    // },
  },
  methods: {
    /* connectMQTT() {
      console.log("connecting to MQTT");
      // this.loading = true;
      this.mqqtt_response_status = "Connecting to MQTT....";
      //const host = "wss://broker.hivemq.com:8884/mqtt"; // For secure WebSocket
      // const host = "ws://165.22.222.17:9001"; // For secure WebSocket
      // const host = "wss://mqtt.xtremeguard.org:9002"; // For secure WebSocket
      // const host = "tcp://mqtt.xtremeguard.org:1883"; // For secure WebSocket

      // const host = "wss://mqtt.xtremeguard.org:8084"; // If TLS WebSocket is available
      const host = process.env.MQTT_SOCKET_HOST; //

      const clientId = "vue-client-" + Math.random().toString(16).substr(2, 8);

      this.mqttClient = mqtt.connect(host, {
        clientId: clientId,
        clean: true,
        connectTimeout: 4000,
      });

      this.mqttClient.on("connect", () => {
        this.isConnected = true;
        console.log("✅ MQTT Connected");
        this.mqqtt_response_status = "Device Connected....";

        // Subscribe to a topic
        const topic = `xtremevision/${this.device.serial_number}/config`;
        this.mqttClient.subscribe(topic, (err) => {
          if (err) {
            this.mqqtt_response_status = "Device Connection Failed....";

            console.error("❌ Subscribe failed:", err);
          } else {
            this.mqqtt_response_status = "Loading.........";

            console.log(`📡 Subscribed to ${topic}`);
          }
        });

        // this.sendConfigRequest();
      });

      this.mqttClient.on("message", (topic, payload) => {
        this.mqqtt_response_status = "Device Loading message";

        if (topic === `xtremevision/${this.device.serial_number}/config`) {
          // console.log(payload.toString());

          let jsonconfig = JSON.parse(payload.toString());
          if (jsonconfig.type == "config") {
            // this.$set(this, "deviceSettings", jsonconfig); // ensures reactivity
            // //this.deviceSettings = jsonconfig;

            let config = JSON.parse(jsonconfig.config);

            if (config) {
              this.$set(this.relayStatus, "relay0", config.relay0);
              this.$set(this.relayStatus, "relay1", config.relay1);
              this.$set(this.relayStatus, "relay2", config.relay2);
              this.$set(this.relayStatus, "relay3", config.relay3);

              // this.relayStatus.relay0 = data.config.relay0;
              // this.relayStatus.relay1 = data.config.relay1;
              // this.relayStatus.relay2 = data.config.relay2;
              // this.relayStatus.relay3 = data.config.relay3;
            }
          }

          // this.message = payload.toString();
        }
      });

      this.mqttClient.on("error", (err) => {
        console.error("MQTT Error:", err);
      });

      this.mqttClient.on("close", () => {
        this.isConnected = false;
        console.log("❌ MQTT Disconnected");
        this.mqqtt_response_status = "Disconnected...";
      });
    },

    sendConfigRequest() {
      if (!this.device.serial_number) return false;
      if (this.mqttClient && this.mqttClient.connected) {
        console.log("✅ MQTT connection is active");
      } else {
        console.log("❌ MQTT connection is inactive or not established");
        this.connectMQTT();
      }
      const topic = `xtremevision/${this.device.serial_number}/config/request`;
      const payload = "GET_CONFIG";

      this.mqttClient.publish(topic, payload, {}, (err) => {
        if (err) {
          console.error("❌ Publish failed:", err);
        } else {
          console.log(`📤 Published to ${topic}:`, payload);
        }
      });
    },


    getDeviceSettings() {
      this.sendConfigRequest();

      return false;
      if (this.loading) return;
      this.message = "loading....";

      let options = {
        params: {
          company_id: this.$auth.user.company.id,
          serial_number: this.device.serial_number,
        },
      };

      this.$axios
        .get(`get_device_settings_from_socket_arduino`, options)
        .then(({ data }) => {
          if (!data.error) {
            if (data.config) {
              this.$set(this.relayStatus, "relay0", data.config.relay0);
              this.$set(this.relayStatus, "relay1", data.config.relay1);
              this.$set(this.relayStatus, "relay2", data.config.relay2);
              this.$set(this.relayStatus, "relay3", data.config.relay3);

              // this.relayStatus.relay0 = data.config.relay0;
              // this.relayStatus.relay1 = data.config.relay1;
              // this.relayStatus.relay2 = data.config.relay2;
              // this.relayStatus.relay3 = data.config.relay3;
            }
          } else this.message = data.error;

          this.loading = false;
        });
    },
    */
    relayCommand(cmd, status) {
      // console.log(this.relayStatus, cmd, status);
      if (status == true) this.$set(this.relayStatus, cmd, false);

      if (status == false) this.$set(this.relayStatus, cmd, true);

      this.key++;
      // console.log(this.relayStatus, cmd, status);

      // return false;

      // if (this.loading) return false;
      // this.loading = true;
      this.message = "loading....";

      let options = {
        params: {
          company_id: this.$auth.user.company.id,
          serial_number: this.device.serial_number,
          cmd: cmd,
          action: !status,
        },
      };

      // this.deviceSettings.deviceSettings;

      this.$axios
        .post(`command_call_device_to_arduino`, options.params)
        .then(({ data }) => {
          // if (cmd == "relay0") this.relayStatus.relay0 = status;
          // if (cmd == "relay1") this.relayStatus.relay1 = status;
          // if (cmd == "relay2") this.relayStatus.relay2 = status;
          // if (cmd == "relay3") this.relayStatus.relay3 = status;
          // if (!data.error) this.deviceSettings = data;
          // else this.message = data.error;
          // setTimeout(() => {
          //   this.loading = false;
          // }, 1000 * 15);
          //this.$emit("getConfigFromDevice");
          this.$emit("manualButtonTriggered");
        });
    },
    getPriorityColor(status) {
      return "";
      if (status == 1) {
        return "color:red;font-weight:500";
      } else {
        return " font-weight:500";
      }
    },

    can(per) {
      return this.$pagePermission.can(per, this);
    },
    openalert(data) {
      alert("");
    },
    filterBranch(branch) {
      this.$emit("openalert", "");

      // if (branch) {
      //   this.selectedBranchName = branch.branch_name;
      //   this.seelctedBranchId = branch.id;
      //   this.branch_id = branch.id;
      // } else {
      //   this.selectedBranchName = "All Branches";
      //   this.seelctedBranchId = "";
      //   this.branch_id = "";
      // }
    },
  },
};
</script>
