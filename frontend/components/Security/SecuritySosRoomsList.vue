<!-- components/Security/SecurityRoomsPopup.vue -->
<template>
  <v-dialog v-model="dialogProxy" max-width="820px" persistent>
    <v-card>
      <v-card-title class="popup_background">
        <span>Assigned SOS Rooms</span>
        <v-spacer></v-spacer>
        <v-icon outlined @click="close()">mdi mdi-close-circle</v-icon>
      </v-card-title>

      <v-card-text>
        <!-- Security header -->
        <!-- <div v-if="security" class="mb-4">
          <div style="font-size: 14px;">
            <strong>Security:</strong>
            {{ security.first_name }} {{ security.last_name }}
            <span class="ml-2" v-if="security.user?.email" style="opacity:.8">
              ({{ security.user.email }})
            </span>
          </div>
        </div> -->

        <!-- Add room -->
        <v-row dense>
          <v-col cols="12" md="9">
            <v-autocomplete v-model="selectedRoomToAdd" :items="roomsAll" item-text="room_name" item-value="id"
              label="Add SOS Room" dense outlined clearable hide-details :loading="loadingRooms"
              :disabled="loadingRooms || !securityId">
              <template v-slot:item="{ item }">
                <div class="d-flex align-center justify-space-between" style="width:100%">
                  <div>{{ item.room_name }}</div>
                  <v-chip x-small v-if="isAssigned(item.id)" class="ml-2">Assigned</v-chip>
                </div>
              </template>
            </v-autocomplete>
          </v-col>

          <v-col cols="12" md="3" class="d-flex align-center">
            <v-btn block :disabled="!selectedRoomToAdd || loadingAssigned || loadingRooms || !securityId"
              @click="addRoom()">
              Add
            </v-btn>
          </v-col>
        </v-row>

        <v-divider class="my-4"></v-divider>

        <!-- Assigned list -->
        <div class="d-flex align-center mb-2" style="font-size: 14px;">
          <strong class="mr-2">Assigned:</strong> {{ assigned.length }}
          <v-spacer></v-spacer>
          <v-btn x-small text :disabled="loadingAssigned || !securityId" @click="fetchAssigned()">
            <v-icon small class="mr-1">mdi-reload</v-icon> Reload
          </v-btn>
        </div>

        <div v-if="loadingAssigned" class="py-8 text-center">
          Loading...
        </div>

        <div v-else>
          <div v-if="assigned.length === 0" class="py-4">
            No rooms assigned.
          </div>

          <div v-else>
            <v-chip v-for="r in assigned" :key="r.id" class="ma-1" close @click:close="removeRoom(r)">
              {{ r.room_name }}
            </v-chip>
          </div>
        </div>
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
      // When opened, load data
      if (v) this.init();
    },
    securityId() {
      // If security changes while open, refresh assigned list
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
          params: {
            company_id: this.companyId,
          },
        });

        // Normalize for item-text if your backend returns different key
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
