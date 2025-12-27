<!-- components/Security/SecurityRoomsPopup.vue -->
<template>
  <v-dialog v-model="dialogProxy" max-width="900px" persistent>
    <v-card>
      <v-card-title class="popup_background">
        <span>Assigned SOS Rooms</span>
        <v-spacer></v-spacer>
        <v-icon outlined @click="close()">mdi mdi-close-circle</v-icon>
      </v-card-title>

      <v-card-text>
        <!-- Add room -->
        <v-row dense>
          <v-col cols="12" md="6" class="mt-3">
            <v-autocomplete v-model="selectedRoomToAdd" :items="roomsAll" item-text="room_name" item-value="id"
              label="Add SOS Room" dense outlined clearable hide-details :loading="loadingRooms">
              <template v-slot:item="{ item }">
                <div class="d-flex align-center justify-space-between" style="width:100%">
                  <div>{{ item.room_name }}</div>
                  <v-chip x-small v-if="isAssigned(item.id)" class="ml-2">Assigned</v-chip>
                </div>
              </template>
            </v-autocomplete>
          </v-col>

          <v-col cols="12" md="3" class="d-flex align-center">
            <v-btn block class="primary" style="margin-top:8px" @click="addRoom()">
              Add
            </v-btn>
          </v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <!-- Assigned list header -->
        <div class="d-flex align-center mb-2" style="font-size: 14px;">
          <strong class="mr-2">Assigned:</strong> {{ assigned.length }}
          <v-spacer></v-spacer>

          <v-btn x-small text :disabled="loadingAssigned || !securityId" @click="fetchAssigned()">
            <v-icon small class="mr-1">mdi-reload</v-icon> Reload
          </v-btn>
        </div>

        <!-- Assigned rooms table -->
        <v-data-table dense :headers="assignedHeaders" :items="assigned" :loading="loadingAssigned" class="elevation-0"
          :items-per-page="10" :footer-props="{ itemsPerPageOptions: [5, 10, 25, 50] }"
          no-data-text="No rooms assigned.">
          <template v-slot:item.sno="{ item, index }">
            <span>{{ +index }}</span>
          </template>


          <template v-slot:item.room_name="{ item }">
            <span>{{ item.room_name }}</span>
          </template>

          <template v-slot:item.id="{ item }">
            <span>{{ item.room_id }}</span>
          </template>

          <template v-slot:item.actions="{ item }">
            <v-tooltip top>
              <template v-slot:activator="{ on, attrs }">
                <v-btn icon v-bind="attrs" v-on="on" @click="removeRoom(item)"
                  :disabled="loadingAssigned || !securityId">
                  <v-icon color="error">mdi-delete</v-icon>
                </v-btn>
              </template>
              <span>Remove</span>
            </v-tooltip>
          </template>
        </v-data-table>
      </v-card-text>

      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn text @click="close()">Close</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
export default {
  name: "SecurityRoomsPopup",
  props: {
    value: { type: Boolean, default: false }, // v-model
    security: { type: Object, default: null }, // selected security row object
    companyId: { type: [Number, String], default: null }, // optional
    roomTextKey: { type: String, default: "room_name" }, // if your column differs
  },
  data: () => ({
    roomsAll: [],
    assigned: [],
    selectedRoomToAdd: null,
    loadingRooms: false,
    loadingAssigned: false,

    assignedHeaders: [
      { text: "#", value: "sno", width: 80 },

      { text: "Room Name", value: "room_name" },
      { text: "Room Id", value: "id", width: 110 },
      { text: "", value: "actions", sortable: false, align: "end", width: 80 },
    ],
  }),
  computed: {
    dialogProxy: {
      get() {
        return this.value;
      },
      set(v) {
        this.$emit("input", v);
      },
    },
    securityId() {
      return this.security?.id || null;
    },
    roomText() {
      return this.roomTextKey || "room_name";
    },
  },
  watch: {
    value(v) {
      if (v) this.init();
    },
    securityId() {
      if (this.value) this.init();
    },
  },
  methods: {
    close() {
      this.dialogProxy = false;
      this.$emit("close");
    },

    isAssigned(roomId) {
      return this.assigned.some((x) => x.id === roomId);
    },

    async init() {
      this.selectedRoomToAdd = null;
      await Promise.all([this.fetchRoomsAll(), this.fetchAssigned()]);
    },

    async fetchRoomsAll() {
      try {
        this.loadingRooms = true;

        const { data } = await this.$axios.get("device_sos_rooms", {
          params: { company_id: this.companyId },
        });

        const list = Array.isArray(data) ? data : [];
        this.roomsAll = list.map((r) => ({
          ...r,
          room_name: r[this.roomText] ?? r.room_name ?? r.name ?? `Room ${r.id}`,
        }));
      } catch (e) {
        this.$emit("notify", { type: "error", text: "Failed to load SOS rooms list" });
      } finally {
        this.loadingRooms = false;
      }
    },

    async fetchAssigned() {
      if (!this.securityId) return;

      try {
        this.loadingAssigned = true;

        const { data } = await this.$axios.get(`security/${this.securityId}/sos-rooms`, {
          params: { company_id: this.companyId },
        });

        const list = data?.assigned || [];
        this.assigned = list.map((r) => ({
          ...r,
          room_name: r[this.roomText] ?? r.room_name ?? r.name ?? `Room ${r.id}`,
        }));
      } catch (e) {
        this.$emit("notify", { type: "error", text: "Failed to load assigned rooms" });
      } finally {
        this.loadingAssigned = false;
      }
    },

    async addRoom() {
      if (!this.securityId || !this.selectedRoomToAdd) return;

      if (this.isAssigned(this.selectedRoomToAdd)) {
        this.$emit("notify", { type: "warning", text: "Room already assigned" });
        return;
      }

      try {
        await this.$axios.post(`security/${this.securityId}/sos-rooms`, {
          company_id: this.companyId,
          sos_room_table_id: this.selectedRoomToAdd,
        });

        this.$emit("notify", { type: "success", text: "Room assigned successfully" });
        this.selectedRoomToAdd = null;

        await this.fetchAssigned();
        this.$emit("updated");
      } catch (e) {
        const msg = e?.response?.data?.message || "Failed to assign room";
        this.$emit("notify", { type: "error", text: msg });
      }
    },

    async removeRoom(room) {
      if (!this.securityId || !room?.id) return;

      if (!confirm(`Remove room "${room.room_name}" from this security?`)) return;

      try {
        await this.$axios.delete(`security/${this.securityId}/sos-rooms/${room.id}`, {
          params: { company_id: this.companyId },
        });

        this.assigned = this.assigned.filter((x) => x.id !== room.id);
        this.$emit("notify", { type: "success", text: "Room removed successfully" });
        this.$emit("updated");
      } catch (e) {
        const msg = e?.response?.data?.message || "Failed to remove room";
        this.$emit("notify", { type: "error", text: msg });
      }
    },
  },
};
</script>
