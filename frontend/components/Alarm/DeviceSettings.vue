<template>
  <div v-if="can('devices_permissions_access') && can('devices_view')">
    <div class="text-center ma-2">
      <v-snackbar v-model="snackbar" top="top" :color="snackbarColor" elevation="24">
        {{ snackbarResponse }}
      </v-snackbar>
    </div>

    <v-icon title="Reload Device Data from SDK" class="pull-right" style="float: right" @click="connectMQTT()"
      dark>mdi-sync</v-icon><br />
    <div>{{ mqqtt_response_status }}</div>
    <v-row v-if="deviceSettings.config == null">
      <v-col style="color: red">{{ message }}</v-col>
    </v-row>

    <v-row v-else>
      <v-col cols="12">
        <v-tabs v-model="tab" :show-arrows="$vuetify.breakpoint.smAndDown" :key="key">
          <v-tab> Device Info </v-tab>
          <v-tab> Communication </v-tab>
          <v-tab>Alarms</v-tab>
          <!-- <v-tab>Phone Numbers</v-tab>
          <v-tab>Sensor Alerts</v-tab> -->
          <v-tab>SOS List</v-tab>

          <v-tabs-items v-model="tab" :key="key">
            <v-tab-item>
              <v-card color="basil">
                <v-card-text>
                  <v-row>
                    <!-- <v-col cols="12"> <h3>Network Settings</h3></v-col> -->

                    <v-col cols="12" sm="12" md="6">
                      <v-row>
                        <v-col cols="12">
                          <v-select disabled v-model="deviceSettings.config.wifi_or_ethernet" outlined dense small
                            :hide-details="true" label="Connection Type (Wifi/Ethernet)" :items="[
                              { text: 'Wifi', value: '1' },
                              { text: 'Ethernet', value: '0' },
                            ]" item-value="value" item-text="text"></v-select>
                        </v-col>
                        <v-col cols="12">
                          <v-text-field disabled v-model="deviceSettings.config.wifi_ssid" outlined dense small
                            :hide-details="true" label="Wifi SSID Name"></v-text-field>
                        </v-col>
                        <v-col cols="12">
                          <v-text-field disabled v-model="deviceSettings.config.wifi_password" outlined dense small
                            :hide-details="true" label="Wifi Password"></v-text-field>
                        </v-col>
                        <v-col cols="12">
                          <v-text-field disabled v-model="deviceSettings.config.wifi_ip" outlined dense small
                            :hide-details="true" label="Wifi IP Address"></v-text-field>
                        </v-col>
                      </v-row>
                    </v-col>

                    <v-col cols="12" sm="12" md="6">
                      <v-row>
                        <v-col cols="12">
                          <v-text-field disabled v-model="deviceSettings.config.eth_ip" outlined dense small number
                            :hide-details="true" label="Eithernet IP Address"></v-text-field>
                        </v-col>
                        <v-col cols="12">
                          <v-text-field disabled v-model="deviceSettings.config.eth_gateway" outlined dense small number
                            :hide-details="true" label="Ethernet Gateway"></v-text-field>
                        </v-col>
                        <v-col cols="12">
                          <v-text-field disabled v-model="deviceSettings.config.eth_subnet" outlined dense small number
                            :hide-details="true" label="EtherNet Subnet"></v-text-field>
                        </v-col>
                        <v-col>
                          <v-text-field disabled v-model="deviceSettings.config.device_serial_number" outlined dense
                            small number :hide-details="true" label="Device Serial Number"></v-text-field>
                        </v-col>
                      </v-row>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-tab-item>
            <v-tab-item>
              <v-card elevation="2" style="height: 250px" outlined>
                <v-card-title dense class="popup_background1111" style="padding: 2px">
                  <v-checkbox disabled color="primary" style="margin-top: -3px" @click="doorAlerts()"
                    :hide-details="true" v-model="deviceSettings.config.mqtt_communication" label="MQTT"></v-checkbox>
                </v-card-title>

                <v-card-text>
                  <br />
                  <v-row>
                    <v-col cols="12" sm="12" md="6">
                      <v-text-field disabled v-model="deviceSettings.config.mqtt_server" outlined dense small number
                        :hide-details="true" label="MQTT Server"></v-text-field>
                    </v-col>
                  </v-row>
                  <v-row>
                    <v-col cols="12" sm="12" md="6">
                      <v-text-field disabled v-model="deviceSettings.config.mqtt_port" outlined dense small number
                        :hide-details="true" label="MQTT Port"></v-text-field>
                    </v-col> </v-row><v-row>
                    <v-col cols="12" sm="12" md="6">
                      <v-text-field disabled v-model="deviceSettings.config.mqtt_clientId" outlined dense small number
                        :hide-details="true" label="MQTT Client Id"></v-text-field>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>

              <!-- <v-card elevation="2" style="height: 200px" outlined>
                <v-card-title dense class="popup_background1111" style="padding: 2px">
                  <v-checkbox color="primary" style="margin-top: -3px" @click="doorAlerts()" :hide-details="true"
                    v-model="deviceSettings.config.socket_communication" label="Server/Socket"></v-checkbox>
                </v-card-title>

                <v-card-text>
                  <br />
                  <v-row>
                    <v-col cols="12" sm="12" md="6">
                      <v-text-field :disabled="!deviceSettings.config.socket_communication"
                        v-model="deviceSettings.config.server_ip" outlined dense small number :hide-details="true"
                        label="Server IP"></v-text-field>
                    </v-col>
                  </v-row>
                  <v-row>
                    <v-col cols="12" sm="12" md="6">
                      <v-text-field :disabled="!deviceSettings.config.socket_communication"
                        v-model="deviceSettings.config.server_port" outlined dense small number :hide-details="true"
                        label="Server Port"></v-text-field>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
              <v-card elevation="2" style="height: 150px" outlined>
                <v-card-title dense class="popup_background1111" style="padding: 2px">
                  <v-checkbox color="primary" style="margin-top: -3px" @click="doorAlerts()" :hide-details="true"
                    v-model="deviceSettings.config.http_communication" label="HTTP Notification"></v-checkbox>
                </v-card-title>

                <v-card-text>
                  <br />
                  <v-row>
                    <v-col cols="12" sm="12" md="6">
                      <v-text-field :disabled="!deviceSettings.config.http_communication"
                        v-model="deviceSettings.config.http_link" outlined dense small number :hide-details="true"
                        label="HTTP URL"></v-text-field>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card> -->

              <v-card-actions class="mt-5" v-if="!viewMode">
                <!-- <v-btn @click="newItemDialog = false" dark filled color="red"
        >Cancel</v-btn
      > -->
                <v-spacer></v-spacer>
                <span v-if="errorValidateMessage != ''" style="color: red; padding-right: 50px">Error: {{
                  errorValidateMessage }}</span>
                <v-btn :loading="loading" @click="save()" dark filled color="primary">Update</v-btn>
              </v-card-actions>
            </v-tab-item>

            <v-tab-item>
              <v-row>
                <v-col cols="12">
                  <v-card elevation="2" outlined style="height: 400px">
                    <v-card-text>
                      <v-row>
                        <v-col cols="12" sm="12" md="6">
                          <v-row>
                            <v-col cols="12">
                              <v-select v-model="deviceSettings.config.heartbeat" outlined dense small
                                :hide-details="true" label="Device Heartbeat" :items="heartBeatData" item-value="value"
                                item-text="label"></v-select>
                            </v-col>

                            <v-col cols="12">
                              <v-select v-model="deviceSettings.config.reset_settings_duration
                                " outlined dense small :hide-details="true" label="Reset Settins Duration Count"
                                :items="secondsCountFrom10to60" item-value="value" item-text="label"></v-select>
                            </v-col>
                            <!-- <v-col cols="12">
                              <v-select v-model="deviceSettings.config
                                .temperature_read_interval
                                " outlined dense small :hide-details="true" label="Temperature Read Interval"
                                :items="secondsCountFrom10to60" item-value="value" item-text="label"></v-select>
                            </v-col> -->

                            <v-col cols="12">
                              <v-checkbox :hide-details="true" v-model="deviceSettings.config.siren_checkbox"
                                :label="`Siren Sound `"></v-checkbox>
                            </v-col>
                            <v-col cols="12">
                              <v-select :disabled="!deviceSettings.config.siren_checkbox
                                " v-model="deviceSettings.config.max_siren_play" outlined dense small
                                :hide-details="true" label="Siren Play Duration" :items="heartBeatData"
                                item-value="value" item-text="label"></v-select> </v-col><v-col cols="12">
                              <v-select :disabled="!deviceSettings.config.siren_checkbox
                                " v-model="deviceSettings.config.max_siren_pause" outlined dense small
                                :hide-details="true" label="Siren Pause Duration" :items="doorContactMinutesData"
                                item-value="value" item-text="label"></v-select>
                            </v-col>
                          </v-row>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" style="display: none;">
                  <v-row>
                    <v-col cols="12">
                      <v-card elevation="2" outlined style="height: 100px">
                        <v-card-title dense class="popup_background1111" style="padding: 2px">
                          <v-checkbox color="primary" style="margin-top: -3px" @click="powerAlerts()"
                            :hide-details="true" v-model="deviceSettings.config.power_checkbox"
                            label="Power Alarm"></v-checkbox>

                          <v-icon color="red">mdi-lightning-bolt-outline</v-icon>

                          <v-spacer> </v-spacer>
                        </v-card-title>
                        <v-card-text>
                          <v-row>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.power_checkbox
                                " :hide-details="true" v-model="deviceSettings.config.power_alert_sms"
                                :label="`SMS Alert`"></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.power_checkbox
                                " cols="4" :hide-details="true" v-model="deviceSettings.config.power_alert_call"
                                :label="`Call Alert`"></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.power_checkbox
                                " cols="4" :hide-details="true" v-model="deviceSettings.config.power_alert_whatsapp
                                  " :label="`Whatsapp Alert`"></v-checkbox>
                            </v-col>
                          </v-row>
                        </v-card-text>
                      </v-card>
                    </v-col>
                    <v-col cols="12">
                      <v-card elevation="2" outlined style="height: 100px">
                        <v-card-title dense class="popup_background1111" style="padding: 2px">
                          <v-checkbox color="primary" style="margin-top: -3px" @click="waterAlerts()"
                            :hide-details="true" v-model="deviceSettings.config.water_checkbox"
                            label="Water Alarm"></v-checkbox>

                          <v-icon color="blue">mdi-water</v-icon>

                          <v-spacer> </v-spacer>
                        </v-card-title>
                        <v-card-text>
                          <v-row>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.water_checkbox
                                " :hide-details="true" v-model="deviceSettings.config.water_alert_sms"
                                :label="`SMS Alert`"></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.water_checkbox
                                " cols="4" :hide-details="true" v-model="deviceSettings.config.water_alert_call"
                                :label="`Call Alert`"></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.water_checkbox
                                " cols="4" :hide-details="true" v-model="deviceSettings.config.water_alert_whatsapp
                                  " :label="`Whatsapp Alert`"></v-checkbox>
                            </v-col>
                          </v-row>
                        </v-card-text>
                      </v-card>
                    </v-col>
                    <v-col cols="12">
                      <v-card elevation="2" outlined style="height: 100px">
                        <v-card-title dense class="popup_background1111" style="padding: 2px">
                          <v-checkbox color="primary" style="margin-top: -3px" @click="fireAlerts()"
                            :hide-details="true" v-model="deviceSettings.config.fire_checkbox"
                            label="Fire Alarm"></v-checkbox>

                          <v-icon style="margin-left: 10px" color="red">mdi-fire</v-icon>

                          <v-spacer> </v-spacer>
                        </v-card-title>
                        <v-card-text>
                          <v-row>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.fire_checkbox" :hide-details="true"
                                v-model="deviceSettings.config.fire_alert_sms" :label="`SMS Alert`"></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.fire_checkbox" cols="4" :hide-details="true"
                                v-model="deviceSettings.config.fire_alert_call" :label="`Call Alert`"></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.fire_checkbox" cols="4" :hide-details="true"
                                v-model="deviceSettings.config.fire_alert_whatsapp
                                  " :label="`Whatsapp Alert`"></v-checkbox>
                            </v-col>
                          </v-row>
                        </v-card-text>
                      </v-card>
                    </v-col>

                    <!-- <v-col cols="12">
                      <v-card elevation="2" style="height: 200px" outlined>
                        <v-card-title
                          dense
                          class="popup_background1111"
                          style="padding: 2px"
                        >
                          <v-checkbox
                            color="primary"
                            style="margin-top: -3px"
                            @click="tempAlerts()"
                            :hide-details="true"
                            v-model="deviceSettings.config.temp_checkbox"
                            label="Temperature Alarm"
                          ></v-checkbox>

                          <v-icon style="margin-left: 10px" color="red"
                            >mdi-thermometer-plus</v-icon
                          >
                        </v-card-title>

                        <v-card-text>
                          <br />
                          <v-row>
                            <v-col cols="6">
                              <v-text-field
                                :disabled="!deviceSettings.config.temp_checkbox"
                                v-model="deviceSettings.config.max_temperature"
                                outlined
                                dense
                                small
                                type="number"
                                :hide-details="true"
                                label="Max Temperature Threshold"
                                step="0.01"
                              ></v-text-field> </v-col
                            ><v-col cols="6">
                              <v-text-field
                                :disabled="!deviceSettings.config.temp_checkbox"
                                v-model="deviceSettings.config.min_temperature"
                                outlined
                                dense
                                small
                                type="number"
                                :hide-details="true"
                                step="0.01"
                                label="Min Temperature Threshold"
                              ></v-text-field>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox
                                :disabled="!deviceSettings.config.temp_checkbox"
                                :hide-details="true"
                                v-model="deviceSettings.config.temp_alert_sms"
                                :label="`SMS Alert`"
                              ></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox
                                :disabled="!deviceSettings.config.temp_checkbox"
                                cols="4"
                                :hide-details="true"
                                v-model="deviceSettings.config.temp_alert_call"
                                :label="`Call Alert`"
                              ></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox
                                :disabled="!deviceSettings.config.temp_checkbox"
                                cols="4"
                                :hide-details="true"
                                v-model="
                                  deviceSettings.config.temp_alert_whatsapp
                                "
                                :label="`Whatsapp Alert`"
                              ></v-checkbox>
                            </v-col>
                          </v-row>
                        </v-card-text>
                      </v-card>
                    </v-col> -->
                    <!-- <v-col cols="12">
                      <v-card elevation="2" style="height: 200px" outlined>
                        <v-card-title
                          dense
                          class="popup_background1111"
                          style="padding: 2px"
                        >
                          <v-checkbox
                            color="primary"
                            style="margin-top: -3px"
                            @click="humidityAlerts()"
                            :hide-details="true"
                            v-model="deviceSettings.config.humidity_checkbox"
                            label="Humidity Alarm"
                          ></v-checkbox>

                          <v-icon style="margin-left: 10px" color="blue"
                            >mdi-thermometer-water</v-icon
                          >
                        </v-card-title>

                        <v-card-text>
                          <br />
                          <v-row>
                            <v-col cols="6">
                              <v-text-field
                                :disabled="
                                  !deviceSettings.config.humidity_checkbox
                                "
                                v-model="deviceSettings.config.max_humidity"
                                outlined
                                dense
                                small
                                type="number"
                                :hide-details="true"
                                label="Max Humidity Threshold"
                                step="0.01"
                              ></v-text-field>
                            </v-col>
                            <v-col cols="6"></v-col>
                            <v-col cols="4">
                              <v-checkbox
                                :disabled="
                                  !deviceSettings.config.humidity_checkbox
                                "
                                :hide-details="true"
                                v-model="
                                  deviceSettings.config.humidity_alert_sms
                                "
                                :label="`SMS Alert`"
                              ></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox
                                :disabled="
                                  !deviceSettings.config.humidity_checkbox
                                "
                                cols="4"
                                :hide-details="true"
                                v-model="
                                  deviceSettings.config.humidity_alert_call
                                "
                                :label="`Call Alert`"
                              ></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox
                                :disabled="
                                  !deviceSettings.config.humidity_checkbox
                                "
                                cols="4"
                                :hide-details="true"
                                v-model="
                                  deviceSettings.config.humidity_alert_whatsapp
                                "
                                :label="`Whatsapp Alert`"
                              ></v-checkbox>
                            </v-col>
                          </v-row>
                        </v-card-text>
                      </v-card>
                    </v-col> -->
                    <v-col cols="12">
                      <v-card elevation="2" style="height: 200px" outlined>
                        <v-card-title dense class="popup_background1111" style="padding: 2px">
                          <v-checkbox color="primary" style="margin-top: -3px" @click="doorAlerts()"
                            :hide-details="true" v-model="deviceSettings.config.door_checkbox"
                            label="Door Alarm"></v-checkbox>

                          <v-icon style="margin-left: 10px" color="red">mdi-door-sliding-open</v-icon>
                        </v-card-title>

                        <v-card-text>
                          <br />
                          <v-row>
                            <v-col cols="6">
                              <v-select :disabled="!deviceSettings.config.door_checkbox
                                " v-model="deviceSettings.config.max_doorcontact" outlined dense small
                                :hide-details="true" label="Max Waiting Door Alarm" :items="doorContactMinutesData"
                                item-value="value" item-text="label"></v-select>
                            </v-col>
                            <v-col cols="6"></v-col>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.door_checkbox
                                " :hide-details="true" v-model="deviceSettings.config.door_alert_sms"
                                :label="`SMS Alert`"></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.door_checkbox
                                " cols="4" :hide-details="true" v-model="deviceSettings.config.door_alert_call"
                                :label="`Call Alert`"></v-checkbox>
                            </v-col>
                            <v-col cols="4">
                              <v-checkbox :disabled="!deviceSettings.config.door_checkbox
                                " cols="4" :hide-details="true" v-model="deviceSettings.config.door_alert_whatsapp
                                  " :label="`Whatsapp Alert`"></v-checkbox>
                            </v-col>
                          </v-row>
                        </v-card-text>
                      </v-card>
                    </v-col>
                  </v-row>
                </v-col>
              </v-row>
              <v-card-actions class="mt-5" v-if="!viewMode">
                <!-- <v-btn @click="newItemDialog = false" dark filled color="red"
        >Cancel</v-btn
      > -->
                <v-spacer></v-spacer>
                <span v-if="errorValidateMessage != ''" style="color: red; padding-right: 50px">Error: {{
                  errorValidateMessage }}</span>
                <v-btn :loading="loading" @click="save()" dark filled color="primary">Update</v-btn>
              </v-card-actions>
            </v-tab-item>
            <!-- <v-tab-item>
              <v-card elevation="2" outlined>
                <v-card-text>
                  <v-row>
                    <v-col cols="3">
                      <v-label>Phone Number 1</v-label>
                    </v-col>
                    <v-col cols="9">
                      <v-text-field style="width: 300px" v-model="deviceSettings.config.phone_number1" outlined dense
                        small :hide-details="true" label="Phone Number1"></v-text-field>
                    </v-col>
                  </v-row>
                  <v-row>
                    <v-col cols="3">
                      <v-label>Phone Number 2</v-label>
                    </v-col>
                    <v-col cols="9">
                      <v-text-field style="width: 300px" v-model="deviceSettings.config.phone_number2" outlined dense
                        small :hide-details="true" label="Phone Number2"></v-text-field>
                    </v-col>
                  </v-row>
                  <v-row>
                    <v-col cols="3">
                      <v-label>Phone Number 3</v-label>
                    </v-col>
                    <v-col cols="9">
                      <v-text-field style="width: 300px" v-model="deviceSettings.config.phone_number3" outlined dense
                        small :hide-details="true" label="Phone Number3"></v-text-field>
                    </v-col>
                  </v-row>
                  <v-row>
                    <v-col cols="3">
                      <v-label>Phone Number 4</v-label>
                    </v-col>
                    <v-col cols="9">
                      <v-text-field style="width: 300px" v-model="deviceSettings.config.phone_number4" outlined dense
                        small :hide-details="true" label="Phone Number4"></v-text-field>
                    </v-col>
                  </v-row>
                  <v-row>
                    <v-col cols="3">
                      <v-label>Phone Number 5</v-label>
                    </v-col>
                    <v-col cols="9">
                      <v-text-field style="width: 300px" v-model="deviceSettings.config.phone_number5" outlined dense
                        small :hide-details="true" label="Phone Number5"></v-text-field>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
              <v-card-actions class="mt-5" v-if="!viewMode">

                <v-spacer></v-spacer>
                <span v-if="errorValidateMessage != ''" style="color: red; padding-right: 50px">Error: {{
                  errorValidateMessage }}</span>
                <v-btn :loading="loading" @click="save()" dark filled color="primary">Update</v-btn>
              </v-card-actions>
            </v-tab-item> -->

            <!-- <v-tab-item>
              <DeviceTemperatureAlerts :editedItem="editedItem" :temperature_alerts_config="deviceSettings.config.temperature_alerts_config
                " />
            </v-tab-item> -->
            <v-tab-item :key="key">



              <DeviceSOSDevices :editedItem="editedItem" :key="key" :sos_devices="sos_devices
                " />
            </v-tab-item>
          </v-tabs-items>
        </v-tabs>
      </v-col>
    </v-row>
  </div>

  <NoAccess v-else />
</template>
<script>
import DeviceSOSDevices from "./DeviceSOSDevices.vue";
import mqtt from "mqtt";

export default {
  components: { DeviceSOSDevices },
  props: ["addNew", "editedItem"],
  data: () => ({
    sos_devices: null,
    mqttClient: null,
    configPayload: "",
    mqqtt_response_status: "",
    //datatable varables
    tab: 0,
    errorValidateMessage: "",
    message: "",
    page: 1,
    perPage: 0,
    currentPage: 1,
    cumulativeIndex: 1,
    totalTableRowsCount: 0,
    options: {},
    filters: {},
    isFilter: false,
    data: [],
    loading: false,
    deviceSettings: { config: null },
    key: 1,
    headers_table: [
      {
        text: "#",
        value: "sno",
        align: "left",
        sortable: false,
        filterable: false,
      },
      {
        text: "Serial Number",
        value: "serial_number",
        align: "left",
        sortable: true,
        filterable: true,
      },
      {
        text: "Device Name",
        value: "name",
        key: "name",
        align: "left",
        sortable: true,
        filterable: true,
        filterSpecial: true,
      },
      {
        text: "Roo No",
        value: "room.room_no",
        align: "left",
        sortable: true,
        key: "room_id",
        filterable: true,
        filterSpecial: true,
      },

      {
        text: "Status",
        value: "latest_status",
        align: "left",
        sortable: true,
        key: "room_id",
        filterable: true,
        filterSpecial: true,
      },

      {
        text: "Status Time",
        value: "latest_status_time",
        align: "left",
        sortable: true,
        key: "room_id",
        filterable: true,
        filterSpecial: true,
      },

      { text: "Options", value: "options", align: "left", sortable: false },
    ],
    roomList: [],

    endpoint: "devices",

    newItemDialog: false,

    //add edit item details

    editedItemIndex: -1,
    roomTypesForSelectOptions: [],
    errors: {},
    snackbar: false,
    snackbarColor: "black",
    snackbarResponse: "",
    viewMode: false,
    floors: [
      1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
    ],
    heartBeatData: [],
    secondsCountFrom10to60: [],

    timeOptionsData: [],

    doorContactMinutesData: [],

    Document: {
      items: [{ title: "", file: "" }],
    },
  }),
  mounted() {
    this.connectMQTT();
  },



  beforeDestroy() {
    if (this.mqttClient) this.mqttClient.end();
  },
  async created() {
    this.generateTimeOptions();
    this.generateTimeOptionsHeartBeat();
    this.connectMQTT();
    // await this.getConfigDataFromAPI();
  },
  methods: {
    connectMQTT() {
      // this.key++;
      console.log("Device settingsconnecting to MQTT");
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
        const topic = `xtremesos/${this.editedItem.serial_number}/config`;
        this.mqttClient.subscribe(topic, (err) => {
          if (err) {
            this.mqqtt_response_status = "Device Connection Failed....";

            console.error("❌ Subscribe failed:", err);
          } else {
            this.mqqtt_response_status =
              "Loading Data from Device settings.........";

            console.log(`📡 Subscribed to ${topic}`);
          }
        });

        this.sendConfigRequest();
      });

      this.mqttClient.on("message", (topic, payload) => {
        console.log("message", payload);

        this.mqqtt_response_status = "Device Loading message";

        if (topic === `xtremesos/${this.editedItem.serial_number}/config`) {
          let jsonconfig = JSON.parse(payload.toString());
          console.log("jsonconfig.type", jsonconfig.type);

          if (jsonconfig.type == "config") {
            this.$set(this, "deviceSettings", jsonconfig); // ensures reactivity
            //this.deviceSettings = jsonconfig;

            let config = JSON.parse(jsonconfig.config);

            this.deviceSettings.config = config;

            this.sos_devices = config.sos_devices;
            // this.key++;

            this.deviceSettings.config.heartbeat = parseInt(
              this.deviceSettings.config.heartbeat
            );
            this.deviceSettings.config.reset_settings_duration = parseInt(
              this.deviceSettings.config.reset_settings_duration
            );
            // this.deviceSettings.config.min_temperature = parseFloat(
            //   this.deviceSettings.config.min_temperature
            // );
            // this.deviceSettings.config.max_temperature = parseFloat(
            //   this.deviceSettings.config.max_temperature
            // );
            // this.deviceSettings.config.max_humidity = parseFloat(
            //   this.deviceSettings.config.max_humidity
            // );
            // this.deviceSettings.config.max_doorcontact = parseInt(
            //   this.deviceSettings.config.max_doorcontact
            // );
            // this.deviceSettings.config.max_temperature_sensor_count = parseInt(
            //   this.deviceSettings.config.max_temperature_sensor_count
            // );
            this.deviceSettings.config.max_siren_pause = parseInt(
              this.deviceSettings.config.max_siren_pause
            );
            // this.deviceSettings.config.temperature_read_interval = parseInt(
            //   this.deviceSettings.config.temperature_read_interval
            // );
            // this.deviceSettings.config.temperature_difference = parseFloat(
            //   this.deviceSettings.config.temperature_difference
            // );
            this.deviceSettings.config.max_siren_play = parseInt(
              this.deviceSettings.config.max_siren_play
            );

            // console.log(this.deviceSettings.config);

            this.mqqtt_response_status = "";
          }
          // this.key++;
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
      if (this.mqttClient && this.mqttClient.connected) {
        console.log("✅ MQTT connection is active");
      } else {
        console.log("❌ MQTT connection is inactive or not established");
        // this.connectMQTT();
      }
      let isConfigReceived = false;
      const topic = `xtremesos/${this.editedItem.serial_number}/config/request`;
      const payload = "GET_CONFIG";

      this.mqqtt_response_status =
        "loading from Configuration Data from Device....";

      this.mqttClient.publish(topic, payload, {}, (err) => {
        if (err) {
          console.error("❌ Publish failed:", err);
        } else {
          console.log(`📤 Published to ${topic}:`, payload);
        }
      });

      setTimeout(() => {
        console.log("Testing");
        if (!this.deviceSettings.config) this.getConfigDataFromAPI();
      }, 1000 * 10);
    },

    can(per) {
      let u = this.$auth.user;
      return (
        (u && u.permissions.some((e) => e == per || per == "/")) || u.is_master
      );
    },
    addDocumentInfo() {
      this.Document.items.push({
        title: "",
        file: "",
      });
    },
    save_document_info() {
      let options = {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      };
      let payload = new FormData();

      this.Document.items.forEach((e) => {
        payload.append(`items[][key]`, e.title);
        payload.append(`items[][value]`, e.file || {});
      });

      payload.append(`company_id`, this.$auth?.user?.company?.id);

      this.$axios
        .post(`document`, payload, options)
        .then(({ data }) => {
          this.loading = false;

          if (!data.status) {
            this.errors = data.errors;
          } else {
          }
        })
        .catch((e) => console.log(e));
    },
    removeItem(index) {
      this.Document.items.splice(index, 1);
    },
    powerAlerts() {
      if (!this.deviceSettings.config.power_checkbox) {
        this.deviceSettings.config.power_alert_sms =
          this.deviceSettings.config.power_checkbox;
        this.deviceSettings.config.power_alert_call =
          this.deviceSettings.config.power_checkbox;
        this.deviceSettings.config.power_alert_whatsapp =
          this.deviceSettings.config.power_checkbox;
      }
    },

    waterAlerts() {
      if (!this.deviceSettings.config.water_checkbox) {
        this.deviceSettings.config.water_alert_sms =
          this.deviceSettings.config.water_checkbox;
        this.deviceSettings.config.water_alert_call =
          this.deviceSettings.config.water_checkbox;
        this.deviceSettings.config.water_alert_whatsapp =
          this.deviceSettings.config.water_checkbox;
      }
    },
    fireAlerts() {
      if (!this.deviceSettings.config.fire_checkbox) {
        this.deviceSettings.config.fire_alert_sms =
          this.deviceSettings.config.fire_checkbox;
        this.deviceSettings.config.fire_alert_call =
          this.deviceSettings.config.fire_checkbox;
        this.deviceSettings.config.fire_alert_whatsapp =
          this.deviceSettings.config.fire_checkbox;
      }
    },
    tempAlerts() {
      if (!this.deviceSettings.config.temp_checkbox) {
        this.deviceSettings.config.temp_alert_sms =
          this.deviceSettings.config.temp_checkbox;
        this.deviceSettings.config.temp_alert_call =
          this.deviceSettings.config.temp_checkbox;
        this.deviceSettings.config.temp_alert_whatsapp =
          this.deviceSettings.config.temp_checkbox;
      }
    },
    humidityAlerts() {
      if (!this.deviceSettings.config.humidity_checkbox) {
        this.deviceSettings.config.humidity_alert_sms =
          this.deviceSettings.config.humidity_checkbox;
        this.deviceSettings.config.humidity_alert_call =
          this.deviceSettings.config.humidity_checkbox;
        this.deviceSettings.config.humidity_alert_whatsapp =
          this.deviceSettings.config.humidity_checkbox;
      }
    },
    doorAlerts() {
      if (!this.deviceSettings.config.door_checkbox) {
        this.deviceSettings.config.door_alert_sms =
          this.deviceSettings.config.door_checkbox;
        this.deviceSettings.config.door_alert_call =
          this.deviceSettings.config.door_checkbox;
        this.deviceSettings.config.door_alert_whatsapp =
          this.deviceSettings.config.door_checkbox;
      }
    },
    generateTimeOptionsHeartBeat() {
      let options = [];
      const increments = [5, 10, 15, 30]; // Time increments in seconds and minutes

      // Generate options for seconds (5s to 55s)
      for (let sec = 1; sec <= 20; sec += 1) {
        options.push({
          value: sec,
          label: `${sec} seconds`,
        });
      }

      this.heartBeatData = options;
      options = [];
      // Generate options for seconds (5s to 55s)
      for (let sec = 10; sec <= 60; sec += 1) {
        options.push({
          value: sec,
          label: `${sec} seconds`,
        });
      }
      this.secondsCountFrom10to60 = options;

      options = [];
      // Generate options for seconds (5s to 55s)
      for (let sec = 1; sec <= 60; sec += 1) {
        options.push({
          value: sec,
          label: `${sec} Minute(s)`,
        });
      }
      this.doorContactMinutesData = options;
    },
    generateTimeOptions() {
      const options = [];
      const increments = [5, 10, 15, 30]; // Time increments in seconds and minutes

      // Generate options for seconds (5s to 55s)
      for (let sec = 5; sec < 60; sec += 5) {
        options.push({
          id: "heartbeat",
          value: sec,
          label: `${sec} seconds`,
        });
      }

      // Generate options for minutes (1 min to 60 min)
      for (let min = 1; min <= 60; min += 1) {
        if (min == 0)
          options.push({
            id: "heartbeat",
            value: (min + 1) * 60,
            label: `${min + 1} minute${min + 1 > 1 ? "s" : ""}`,
          });
        else
          options.push({
            id: "heartbeat",
            value: min * 60,
            label: `${min} minute${min > 1 ? "s" : ""}`,
          });
      }

      this.timeOptionsData = options;
    },

    async getConfigDataFromAPI() {
      // this.connectMQTT();

      await this.getDataFromApi();

      // await this.getConfigFromcache();

      setTimeout(() => {
        this.loading = false;
      }, 1000 * 10);
    },
    async getDataFromApi() {
      this.mqqtt_response_status = "loading from Cloud....";

      this.deviceSettings = { config: null };

      let options = {
        params: {
          company_id: this.$auth.user.company.id,
          serial_number: this.editedItem.serial_number,
        },
      };

      // const { data } = await this.$axios.get(
      //   `get_device_settings_from_socket_arduino`,
      //   options
      // );

      await this.$axios
        .get(`get_device_settings_from_socket_arduino`, options)
        .then(({ data }) => {
          this.loading = false;

          // console.log(data.error);

          if (!data.error) this.deviceSettings = data;
          else this.mqqtt_response_status = data.error;
        });
    },

    async getConfigFromcache() {
      let options = {
        params: {
          company_id: this.$auth.user.company.id,
          serial_number: this.editedItem.serial_number,
        },
      };

      await this.$axios
        .get(`get_device_settings_from_cache`, options)
        .then(({ data }) => {
          if (!data.error) this.deviceSettings = data;
          else this.mqqtt_response_status = data.error;

          this.loading = false;
        });
    },
    isValidNumber(value) {
      const pattern = /^$|^\d{10,13}$/;
      return pattern.test(value);
    },
    save() {
      this.loading = true;
      this.errorValidateMessage = "";

      // if (this.deviceSettings.config.temp_checkbox) {
      //   let maxTemp = parseFloat(this.deviceSettings.config.max_temperature);
      //   if (isNaN(maxTemp)) {
      //     this.errorValidateMessage = "Temperature Max value is invalid";
      //   }

      //   let minTemp = parseFloat(this.deviceSettings.config.min_temperature);
      //   if (isNaN(minTemp) && this.errorValidateMessage == "") {
      //     this.errorValidateMessage = "Temperature Min value is invalid";
      //   }
      // } else if (this.deviceSettings.config.humidity_checkbox) {
      //   let maxHumidity = parseFloat(this.deviceSettings.config.max_humidity);
      //   if (isNaN(maxHumidity)) {
      //     this.errorValidateMessage = "Humidity Max value is invalid";
      //   }
      // } else

      if (this.deviceSettings.config.door_checkbox) {
        let maxDoorContact = parseFloat(
          this.deviceSettings.config.max_doorcontact
        );
        if (isNaN(maxDoorContact)) {
          this.errorValidateMessage = "Door Contact waiting value is invalid";
        }
      } else if (this.deviceSettings.config.siren_checkbox) {
        let maxSirenPlay = parseFloat(
          this.deviceSettings.config.max_siren_play
        );
        if (isNaN(maxSirenPlay) && this.errorValidateMessage == "") {
          this.errorValidateMessage = "Siren Play time value is invalid";
        }

        let maxSirenPause = parseFloat(
          this.deviceSettings.config.max_siren_pause
        );
        if (isNaN(maxSirenPause) && this.errorValidateMessage == "") {
          this.errorValidateMessage = "Siren Pause time value is invalid";
        }
      }
      if (
        this.deviceSettings.config.phone_number1 &&
        !this.isValidNumber(this.deviceSettings.config.phone_number1)
      ) {
        this.errorValidateMessage = "Phone Number 1 is invalid";
      } else if (
        this.deviceSettings.config.phone_number2 &&
        !this.isValidNumber(this.deviceSettings.config.phone_number2)
      ) {
        this.errorValidateMessage = "Phone Number 2 is invalid";
      } else if (
        this.deviceSettings.config.phone_number3 &&
        !this.isValidNumber(this.deviceSettings.config.phone_number3)
      ) {
        this.errorValidateMessage = "Phone Number 3 is invalid";
      } else if (
        this.deviceSettings.config.phone_number4 &&
        !this.isValidNumber(this.deviceSettings.config.phone_number4)
      ) {
        this.errorValidateMessage = "Phone Number 4 is invalid";
      } else if (
        this.deviceSettings.config.phone_number5 &&
        !this.isValidNumber(this.deviceSettings.config.phone_number5)
      ) {
        this.errorValidateMessage = "Phone Number 5 is invalid";
      }

      if (this.errorValidateMessage != "") return false;

      let options = {
        params: {
          company_id: this.$auth.user.company.id,
          serial_number: this.editedItem.serial_number,
          config: this.deviceSettings.config,
        },
      };

      this.$axios
        .post(`update_device_settings_from_socket_arduino`, options.params)
        .then(({ data }) => {
          this.snackbar = true;
          this.snackbarResponse = "Device Settings Updated successfully";

          this.loading = false;

          setTimeout(() => {
            this.$emit("emitCloseEvent");
          }, 1000 * 2);
        });

      setTimeout(() => {
        this.loading = false;
      }, 1000 * 30);
    },
  },
};
</script>
