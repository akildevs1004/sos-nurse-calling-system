<template>
  <v-container>
    <!-- TABS HEADER -->
    <div class="text-center ma-2">
      <v-snackbar
        v-model="snackbar"
        top="top"
        :color="snackbarColor"
        elevation="24"
      >
        {{ snackbarResponse }}
      </v-snackbar>
    </div>
    <v-tabs v-model="activeTab" background-color="primary" dark>
      <v-tab v-for="(sensor, index) in alertConfigs" :key="'tab-' + index">
        Sensor {{ index + 1 }}
      </v-tab>
    </v-tabs>
    <v-form ref="form" v-model="formValid">
      <!-- TABS CONTENT -->
      <v-tabs-items v-model="activeTab">
        <v-tab-item
          v-for="(sensor, index) in alertConfigs"
          :key="'tab-item-' + index"
        >
          <v-card color="basil" elevation="2" outlined>
            <v-card-title class="py-1">
              <v-row class="mb-2">
                <v-col cols="4">
                  <v-text-field
                    style="max-width: 200px; margin-top: 10px"
                    v-model="sensor.sensor_name"
                    :disabled="!sensor.temperature.enabled"
                    outlined
                    dense
                    label="Sensor Name"
                    aria-label="Sensor Name"
                    hide-details
                    required
                    :name="`sensor_name_${sensor.sensor_address_id}`"
                    :rules="sensorNameRules"
                  />
                </v-col>
                <v-col>
                  <v-select
                    style="max-width: 200px; margin-top: 10px"
                    v-model="sensor.sensor_address_id"
                    dense
                    outlined
                    label="Sensor Address ID"
                    class="mr-4"
                    hide-details
                    :items="[1, 2, 3, 4, 5, 6, 7, 8, 9, 10]"
                  />
                </v-col>
              </v-row>

              <v-spacer />
              <v-icon
                v-if="alertConfigs.length > 1"
                color="red"
                class="mr-5"
                @click="removeAlertConfig(index)"
              >
                mdi-delete
              </v-icon>
              <v-icon
                v-if="index === alertConfigs.length - 1"
                color="primary"
                @click="addAlertConfig"
              >
                mdi-plus-circle
              </v-icon>
            </v-card-title>

            <v-card-text>
              <!-- Temperature Block -->
              <v-card elevation="2" outlined style="height: 200px">
                <v-card-title
                  dense
                  class="popup_background1111"
                  style="padding: 2px"
                >
                  <v-checkbox
                    color="primary"
                    style="margin-top: -3px"
                    :hide-details="true"
                    v-model="sensor.temperature.enabled"
                    label="Temperature Alert"
                  ></v-checkbox>

                  <v-spacer> </v-spacer>
                </v-card-title>
                <v-card-text>
                  <br />
                  <v-row>
                    <v-col cols="6">
                      <v-text-field
                        v-model="sensor.temperature.max"
                        :disabled="!sensor.temperature.enabled"
                        outlined
                        dense
                        type="number"
                        label="Max Temperature"
                        step="0.01"
                        hide-details
                      />
                    </v-col>
                    <v-col cols="6">
                      <v-text-field
                        v-model="sensor.temperature.min"
                        :disabled="!sensor.temperature.enabled"
                        outlined
                        dense
                        type="number"
                        label="Min Temperature"
                        step="0.01"
                        hide-details
                      />
                    </v-col>
                    <v-col cols="4">
                      <v-checkbox
                        v-model="sensor.temperature.sms"
                        :disabled="!sensor.temperature.enabled"
                        label="SMS"
                        hide-details
                      />
                    </v-col>
                    <v-col cols="4">
                      <v-checkbox
                        v-model="sensor.temperature.call"
                        :disabled="!sensor.temperature.enabled"
                        label="Call"
                        hide-details
                      />
                    </v-col>
                    <v-col cols="4">
                      <v-checkbox
                        v-model="sensor.temperature.whatsapp"
                        :disabled="!sensor.temperature.enabled"
                        label="WhatsApp"
                        hide-details
                      />
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
              <br />
              <v-card elevation="2" outlined style="height: 200px">
                <v-card-title
                  dense
                  class="popup_background1111"
                  style="padding: 2px"
                >
                  <v-checkbox
                    color="primary"
                    style="margin-top: -3px"
                    :hide-details="true"
                    v-model="sensor.humidity.enabled"
                    label="Enable Humidty"
                  ></v-checkbox>

                  <v-spacer> </v-spacer>
                </v-card-title>
                <v-card-text>
                  <v-row class="mt-4">
                    <v-col cols="6">
                      <v-text-field
                        v-model="sensor.humidity.max"
                        :disabled="!sensor.humidity.enabled"
                        outlined
                        dense
                        type="number"
                        label="Max Humidity"
                        step="0.01"
                        hide-details
                      />
                    </v-col>
                    <v-col cols="6">
                      <v-text-field
                        v-model="sensor.humidity.min"
                        :disabled="!sensor.humidity.enabled"
                        outlined
                        dense
                        type="number"
                        label="Min Humidity"
                        step="0.01"
                        hide-details
                      />
                    </v-col>
                    <v-col cols="4">
                      <v-checkbox
                        v-model="sensor.humidity.sms"
                        :disabled="!sensor.humidity.enabled"
                        label="SMS"
                        hide-details
                      />
                    </v-col>
                    <v-col cols="4">
                      <v-checkbox
                        v-model="sensor.humidity.call"
                        :disabled="!sensor.humidity.enabled"
                        label="Call"
                        hide-details
                      />
                    </v-col>
                    <v-col cols="4">
                      <v-checkbox
                        v-model="sensor.humidity.whatsapp"
                        :disabled="!sensor.humidity.enabled"
                        label="WhatsApp"
                        hide-details
                      />
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-card-text>
          </v-card>
        </v-tab-item>
      </v-tabs-items>
    </v-form>
    <!-- Save/Load Buttons -->
    <v-col cols="12" class="text-right mt-4">
      <v-btn :loading="loading" color="primary" @click="saveAlertsAsJSON"
        >Update
      </v-btn>
    </v-col>
  </v-container>
</template>

<script>
export default {
  props: ["editedItem", "temperature_alerts_config"],
  data() {
    return {
      formValid: false,
      sensorNameRules: [
        (v) => !!v || "Sensor name is required",
        (v) => (v && v.length <= 20) || "Max 20 characters",
      ],
      loading: false,
      snackbar: false,
      snackbarColor: "black",
      snackbarResponse: "",
      activeTab: 0,
      alertConfigs: [
        {
          sensor_address_id: "",
          sensor_name: "",

          temperature: {
            enabled: true,
            max: null,
            min: null,
            sms: false,
            call: false,
            whatsapp: false,
          },
          humidity: {
            enabled: true,
            max: null,
            min: null,
            sms: false,
            call: false,
            whatsapp: false,
          },
        },
      ],
    };
  },

  created() {
    if (this.temperature_alerts_config)
      this.alertConfigs = this.temperature_alerts_config;
  },
  methods: {
    addAlertConfig() {
      const newConfig = {
        sensor_address_id: "",
        temperature: {
          enabled: true,
          max: null,
          min: null,
          sms: false,
          call: false,
          whatsapp: false,
        },
        humidity: {
          enabled: true,
          max: null,
          min: null,
          sms: false,
          call: false,
          whatsapp: false,
        },
      };
      this.alertConfigs.push(newConfig);
      this.activeTab = this.alertConfigs.length - 1;
    },
    removeAlertConfig(index) {
      this.alertConfigs.splice(index, 1);
      if (this.activeTab >= this.alertConfigs.length) {
        this.activeTab = this.alertConfigs.length - 1;
      }
    },
    saveAlertsAsJSON() {
      console.log(this.$refs.form.validate());

      if (this.$refs.form.validate()) {
        const json = JSON.stringify(this.alertConfigs, null, 2);
        this.loading = true;
        // console.log("json", json);

        let options = {
          params: {
            company_id: this.$auth.user.company.id,
            serial_number: this.editedItem.serial_number,
            temperature_alerts_config: this.alertConfigs,
            device_id: this.editedItem.id,
          },
        };

        this.$axios
          .post(
            `update_device_temperature_settings_from_socket_arduino`,
            options.params
          )
          .then(({ data }) => {
            this.loading = false;

            this.snackbar = true;
            this.snackbarResponse = "Device Settings Updated successfully";
          });
        setTimeout(() => {
          this.loading = false;
        }, 1000 * 30);
      } else {
        // ❌ Form is invalid
        alert("Missing Values");
      }
    },

    loadAlertsFromFile(event) {
      const file = event.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const data = JSON.parse(e.target.result);
          if (Array.isArray(data)) {
            this.alertConfigs = data;
            this.activeTab = 0;
          } else {
            alert("Invalid file format.");
          }
        } catch (err) {
          alert("Error reading JSON: " + err.message);
        }
      };
      reader.readAsText(file);
    },
  },
};
</script>
