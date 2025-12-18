<template>
  <v-card outlined>
    <v-card-title class="d-flex align-center">
      SOS Devices
      <v-spacer />
      <v-text-field v-model="search" dense outlined clearable hide-details label="Search" style="max-width: 320px" />
    </v-card-title>

    <v-data-table :headers="headers" :items="devices" :search="search" item-key="id" dense class="elevation-0">
      <template v-slot:item.codes="{ item }">
        <div class="d-flex align-center">
          <v-icon small color="green" class="mr-1">mdi-toggle-switch</v-icon>
          <span class="mr-3">{{ item.onCode }}</span>
          <v-icon small color="red" class="mr-1">mdi-toggle-switch-off</v-icon>
          <span>{{ item.offCode }}</span>
        </div>
      </template>
      <template v-slot:item.roomType="{ item }">
        {{ $utils.caps(item.roomType) }}
      </template>
      <template v-slot:item.status="{ item }">
        <v-chip small :color="statusColor(item.status)" dark class="px-2" style="width: 72px">
          <v-icon left small>{{ statusIcon(item.status) }}</v-icon>
          {{ item.status }}
        </v-chip>
      </template>

      <template v-slot:item.actions="{ item }">
        <v-btn icon @click="openEdit(item)" :title="`Edit ${item.name}`">
          <v-icon small color="primary">mdi-pencil</v-icon>
        </v-btn>
      </template>
    </v-data-table>

    <v-divider />

    <v-card-actions class="py-2">
      <div class="caption">
        MQTT:
        <span :style="{ color: isConnected ? 'green' : 'red' }">
          {{ isConnected ? "Connected" : "Disconnected" }}
        </span>
        <span class="ml-2" v-if="mqttStatus">— {{ mqttStatus }}</span>
      </div>

      <v-spacer />

      <!-- <v-btn small outlined @click="sendGetConfig()" :disabled="!isConnected">
        <v-icon left small>mdi-refresh</v-icon>
        Get Config
      </v-btn> -->
    </v-card-actions>

    <!-- Edit dialog -->
    <v-dialog v-model="editDialog" max-width="520">
      <v-card>
        <v-card-title class="d-flex align-center" style="color:#FFF">
          Edit Room
          <v-spacer />
          <v-btn icon @click="editDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>

        <v-card-text>
          <v-alert v-if="editError" type="error" dense class="mb-3">{{ editError }}</v-alert>

          <v-text-field v-model="editForm.name" label="Room (Name)" outlined dense clearable />
          <v-text-field v-model.number="editForm.roomId" label="Room ID" outlined dense type="number" clearable />
          <v-select v-model="editForm.roomType" abel="Room ID" outlined dense :items="[
            { value: 'room', text: 'Room' },
            { value: 'toilet', text: 'Toilet' },
            { value: 'toilet-pd', text: 'Toilet For Disabled' },

          ]" item-value="value" item-text="text"></v-select>
        </v-card-text>

        <v-divider />
        <v-card-actions>
          <v-spacer />
          <v-btn text @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" :disabled="!canSave" :loading="saving" @click="saveEditFullConfig()">
            Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script>
import mqtt from "mqtt";

export default {
  name: "SosDevicesTable",

  props: {
    // must include serial_number
    editedItem: { type: Object, required: true },

    // 1) initial load from prop
    prosos_devices: { type: Array, default: () => [] },
  },

  data() {
    return {
      search: "",
      devices: [],
      latestConfig: null,
      hasMqttConfig: false,

      headers: [
        { text: "ID", value: "id", width: 70, sortable: false },
        { text: "Room", value: "name", sortable: false },
        { text: "Room ID", value: "roomId", sortable: false },
        { text: "Room Type", value: "roomType", sortable: false },

        // { text: "Codes", value: "codes", sortable: false },
        { text: "SOS", value: "status", sortable: false },
        { text: "Actions", value: "actions", sortable: false, align: "end" },
      ],

      // MQTT
      mqttClient: null,
      isConnected: false,
      mqttStatus: "",

      // topics
      topicConfig: "", // subscribe: xtremesos/<serial>/config
      topicReq: "", // publish:   xtremesos/<serial>/config/request

      // Edit
      editDialog: false,
      editForm: { id: null, name: "", roomId: null, roomType: null },
      editOriginal: null,
      saving: false,
      editError: "",
    };
  },

  computed: {
    canSave() {
      if (!this.editForm.id) return false;
      if (!String(this.editForm.name || "").trim()) return false;
      if (!String(this.editForm.roomType || "").trim()) return false;

      if (this.editForm.roomId === null || this.editForm.roomId === "" || isNaN(Number(this.editForm.roomId))) return false;

      if (!this.editOriginal) return true;
      return (
        String(this.editForm.name).trim() !== String(this.editOriginal.name || "").trim() ||
        String(this.editForm.roomType).trim() !== String(this.editOriginal.roomType || "").trim() ||

        Number(this.editForm.roomId) !== Number(this.editOriginal.roomId)
      );
    },
  },

  created() {
    // 1) show initial devices immediately
    this.applyDevicesFromProp(this.prosos_devices);

    // 2) connect + subscribe to xtremesos/<serial>/config
    this.connectMQTT();
  },

  beforeDestroy() {
    this.disconnectMQTT();
  },

  watch: {
    // keep reflecting initial prop until mqtt config arrives
    prosos_devices: {
      deep: true,
      handler(val) {
        if (!this.hasMqttConfig) this.applyDevicesFromProp(val);
      },
    },

    // if serial changes, reconnect + resubscribe
    "editedItem.serial_number"(n, o) {
      if (!n || n === o) return;
      this.disconnectMQTT();
      this.hasMqttConfig = false;
      this.latestConfig = null;
      this.applyDevicesFromProp(this.prosos_devices);
      this.connectMQTT();
    },
  },

  methods: {
    // ---------------- UI helpers ----------------
    statusColor(status) {
      return status === "OFF" ? "green" : "red";
    },
    statusIcon(status) {
      return status === "OFF" ? "mdi-alert-circle" : "mdi-check-circle";
    },

    // ---------------- normalize ----------------
    normalizeDevice(d) {
      const roomId = d.roomId ?? d.room_id ?? null;
      return {
        ...d,
        roomId: roomId === null ? null : Number(roomId),
      };
    },

    applyDevicesFromProp(list) {
      this.devices = Array.isArray(list) ? list.map((d) => this.normalizeDevice({ ...d })) : [];

      // keep a minimal config so EDIT works even before MQTT arrives
      if (!this.latestConfig) {
        this.latestConfig = { sos_devices: this.devices.map((d) => ({ ...d })) };
      }

      if (!this.mqttStatus) this.mqttStatus = `Loaded from prop (${this.devices.length} rooms)`;
    },

    // ---------------- MQTT ----------------
    getMqttHost() {
      return process.env.VUE_APP_MQTT_SOCKET_HOST || process.env.MQTT_SOCKET_HOST || "";
    },

    buildTopics() {
      const serial = this.editedItem?.serial_number;
      if (!serial) return;

      // USER REQUIRED: subscribe xtremesos/XTSOS251000/config (for that serial)
      this.topicConfig = `xtremesos/${serial}/config`;
      this.topicReq = `xtremesos/${serial}/config/request`;
    },

    connectMQTT() {
      const host = this.getMqttHost();
      const serial = this.editedItem?.serial_number;

      if (!host || !serial) {
        this.mqttStatus = "Missing MQTT host or serial_number";
        return;
      }

      this.buildTopics();

      // prevent duplicates
      if (this.mqttClient) return;

      const clientId = "vue-sos-" + Math.random().toString(16).slice(2);

      this.mqttStatus = "Connecting...";
      this.mqttClient = mqtt.connect(host, {
        clientId,
        clean: true,
        connectTimeout: 6000,
        reconnectPeriod: 2000, // mqtt.js auto reconnect
      });

      this.mqttClient.on("connect", () => {
        this.isConnected = true;
        this.mqttStatus = "Connected";

        // Subscribe exact topic: xtremesos/<serial>/config
        this.mqttClient.subscribe(this.topicConfig, (err) => {
          if (err) {
            console.error("Subscribe failed:", err);
            this.mqttStatus = "Subscribe failed";
            return;
          }
          this.mqttStatus = `Subscribed: ${this.topicConfig}`;
          this.sendGetConfig();
        });
      });

      this.mqttClient.on("message", (topic, payload) => {


        console.log("topic SOS", topic, this.topicConfig);

        if (topic !== this.topicConfig) return;
        this.applyConfigFromMqtt(payload.toString());
      });

      this.mqttClient.on("reconnect", () => {
        this.mqttStatus = "Reconnecting...";
      });

      this.mqttClient.on("close", () => {
        this.isConnected = false;
        this.mqttStatus = "Disconnected";
      });

      this.mqttClient.on("error", (err) => {
        console.error("MQTT error:", err);
        this.mqttStatus = "MQTT error";
      });
    },

    disconnectMQTT() {
      if (!this.mqttClient) return;
      try {
        this.mqttClient.end(true);
      } catch (e) { }
      this.mqttClient = null;
      this.isConnected = false;
    },

    // publish GET_CONFIG to xtremesos/<serial>/config/request
    sendGetConfig() {
      if (!this.mqttClient || !this.isConnected) return;
      if (!this.topicReq) this.buildTopics();
      if (!this.topicReq) return;

      // IMPORTANT: plain string GET_CONFIG (no JSON.stringify on string)
      this.mqttClient.publish(this.topicReq, "GET_CONFIG", { qos: 0, retain: false });
      this.mqttStatus = "GET_CONFIG sent";
    },

    // receive config from xtremesos/<serial>/config and update ALL sos_devices fields
    applyConfigFromMqtt(raw) {
      let wrapper;
      try {
        wrapper = JSON.parse(raw);
      } catch (e) {
        console.error("Invalid wrapper JSON:", e);
        return;
      }

      let cfg;
      try {
        cfg = typeof wrapper.config === "string" ? JSON.parse(wrapper.config) : wrapper.config;
      } catch (e) {
        console.error("Invalid wrapper.config JSON:", e);
        return;
      }

      this.latestConfig = cfg || {};
      const list = Array.isArray(cfg?.sos_devices) ? cfg.sos_devices : [];

      console.log("topic SOS", cfg.sos_devices[0].status);


      // update table with full objects (status/lastSeen/codes/bits/proto/etc)
      this.devices = list.map((d) => this.normalizeDevice({ ...d }));
      this.hasMqttConfig = true;

      this.mqttStatus = `Config loaded (${this.devices.length} rooms)`;
    },

    // ---------------- Edit + UPDATE_CONFIG ----------------
    openEdit(item) {
      this.editError = "";
      this.editOriginal = { ...item };
      this.editForm = { id: item.id, name: item.name || "", roomType: item.roomType || "", roomId: item.roomId ?? "" };
      this.editDialog = true;
    },

    saveEditFullConfig() {
      this.editError = "";
      this.saving = true;

      try {
        if (!this.mqttClient || !this.isConnected) throw new Error("MQTT not connected");
        if (!this.latestConfig) this.latestConfig = { sos_devices: this.devices.map((d) => ({ ...d })) };

        const cfg = JSON.parse(JSON.stringify(this.latestConfig));
        if (!Array.isArray(cfg.sos_devices)) cfg.sos_devices = [];

        const id = Number(this.editForm.id);
        const newName = String(this.editForm.name || "").trim();
        const newRoomType = String(this.editForm.roomType || "").trim();

        const newRoomId = Number(this.editForm.roomId);

        const idx = cfg.sos_devices.findIndex((d) => Number(d.id) === id);
        if (idx === -1) throw new Error(`Device id ${id} not found`);

        // Only update name + roomId, keep everything else
        cfg.sos_devices[idx] = {
          ...cfg.sos_devices[idx],
          name: newName,
          roomType: newRoomType,

          roomId: newRoomId,
          // If your firmware uses room_id instead of roomId, replace above line with:
          // room_id: newRoomId,
        };

        // update UI immediately
        this.latestConfig = cfg;
        this.devices = cfg.sos_devices.map((d) => this.normalizeDevice({ ...d }));

        const payload = {
          action: "UPDATE_CONFIG",
          serialNumber: this.editedItem.serial_number,
          // Most compatible: send full config as string
          config: { sos_devices: cfg.sos_devices },
        };

        this.mqttClient.publish(this.topicReq, JSON.stringify(payload), { qos: 0, retain: false });
        this.mqttStatus = "UPDATE_CONFIG sent";
        this.editDialog = false;
      } catch (e) {
        console.error(e);
        this.editError = e?.message || "Failed to publish UPDATE_CONFIG.";
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
