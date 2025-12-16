<template>
  <div>
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
    <v-dialog v-model="newItemDialog" max-width="400">
      <v-card>
        <v-toolbar flat dense class="popup_background_noviolet">
          <span v-if="viewMode">View Sensor Info </span>
          <span v-else-if="editedItemIndex == -1">Add Temperature Sensor </span>
          <span v-else>Edit Temperature Sensor </span>
          <v-spacer></v-spacer>
          <v-icon @click="newItemDialog = false" outlined dark color="black">
            mdi-close
          </v-icon>
        </v-toolbar>
        <v-card-text>
          <v-container class="mt-4">
            <v-row>
              <v-col md="12" cols="12">
                <v-text-field
                  label="Room/Sensor Name"
                  :disabled="viewMode"
                  v-model="editedItem.name"
                  outlined
                  dense
                  small
                  :hide-details="true"
                  placeholder="Sensor Name"
                ></v-text-field>
                <span dense v-if="errors && errors.name" class="error--text">{{
                  errors.name[0]
                }}</span>
              </v-col>
              <v-col cols="12">
                <v-text-field
                  label="Sensor Address"
                  :disabled="viewMode"
                  v-model="editedItem.temperature_serial_address"
                  outlined
                  dense
                  small
                  :hide-details="true"
                  placeholder="Sensor Address"
                  type="number"
                ></v-text-field>
                <span
                  dense
                  v-if="errors && errors.temperature_serial_address"
                  class="error--text"
                  >{{ errors.temperature_serial_address[0] }}</span
                >
              </v-col>

              <v-col cols="12" class="text-right" v-if="!viewMode">
                <v-btn
                  small
                  @click="newItemDialog = false"
                  dark
                  filled
                  color="grey white--text"
                  >Cancel</v-btn
                >
                <v-btn small @click="save()" dark filled color="primary"
                  >Save</v-btn
                >
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>
      </v-card>
    </v-dialog>
    <v-card class="mb-5" elevation="0">
      <v-toolbar class="rounded-md mb-2" dense flat>
        <!-- <v-toolbar-title><span>Rooms</span></v-toolbar-title> -->
        <v-tooltip top color="primary">
          <template v-slot:activator="{ on, attrs }">
            <v-btn
              dense
              class="ma-0 px-0"
              x-small
              :ripple="false"
              text
              v-bind="attrs"
              v-on="on"
            >
              <v-icon class="ml-2" @click="reload()" dark
                >mdi mdi-reload</v-icon
              >
            </v-btn>
          </template>
          <span>Reload</span>
        </v-tooltip>
        <!-- <v-tooltip top color="primary">
          <template v-slot:activator="{ on, attrs }">
            <v-btn
              x-small
              :ripple="false"
              text
              v-bind="attrs"
              v-on="on"
              @click="toggleFilter"
            >
              <v-icon white color="#FFF">mdi-filter</v-icon>
            </v-btn>
          </template>
          <span>Filter</span>
        </v-tooltip> -->
        <v-spacer></v-spacer>
        <!-- <v-btn dark class="blue" small @click="AddNewRoom()">
          <v-icon color="white" small>mdi-plus</v-icon> Sensor
        </v-btn> -->
      </v-toolbar>
      <v-row>
        <v-col cols="12">
          <v-data-table
            dense
            :headers="headers_table"
            :items="data"
            :loading="loading"
            :options.sync="options"
            :footer-props="{
              itemsPerPageOptions: [50, 100, 500, 1000],
            }"
            :server-items-length="totalTableRowsCount"
          >
            <template v-slot:header="{ props: { headers } }">
              <tr v-if="isFilter">
                <td v-for="header in headers" :key="header.text">
                  <v-text-field
                    v-if="header.filterable && !header.filterSpecial"
                    clearable
                    :hide-details="true"
                    v-model="filters[header.value]"
                    no-title
                    outlined
                    dense
                    small
                    :id="header.value"
                    autocomplete="off"
                    @input="applyFilters()"
                  ></v-text-field>
                </td>
              </tr>
            </template>
            <template v-slot:item.sno="{ item, index }">
              {{
                currentPage
                  ? (currentPage - 1) * perPage +
                    (cumulativeIndex + itemIndex(item))
                  : ""
              }}
            </template>

            <template v-slot:item.options="{ item }">
              <v-menu bottom left>
                <template v-slot:activator="{ on, attrs }">
                  <v-btn dark-2 icon v-bind="attrs" v-on="on">
                    <v-icon>mdi-dots-vertical</v-icon>
                  </v-btn>
                </template>
                <v-list width="120" dense>
                  <v-list-item @click="editItem(item, true)">
                    <v-list-item-title style="cursor: pointer">
                      <v-icon small> mdi-eye </v-icon>
                      View
                    </v-list-item-title>
                  </v-list-item>
                  <!-- <v-list-item @click="editItem(item, false)">
                    <v-list-item-title style="cursor: pointer">
                      <v-icon small> mdi-pencil </v-icon>
                      Edit
                    </v-list-item-title>
                  </v-list-item>
                  <v-list-item @click="deleteItem(item)">
                    <v-list-item-title style="cursor: pointer">
                      <v-icon color="error" small> mdi-delete </v-icon>
                      Delete
                    </v-list-item-title>
                  </v-list-item>-->
                </v-list>
              </v-menu>
            </template>
          </v-data-table>
        </v-col>
      </v-row>
    </v-card>
  </div>
</template>
<script>
export default {
  props: ["device_id"],
  data: () => ({
    //datatable varables
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
    headers_table: [
      {
        text: "#",
        value: "sno",
        align: "left",
        sortable: false,
        filterable: false,
      },

      {
        text: "Name",
        value: "name",
        align: "left",
        sortable: false,
        filterable: true,
        filterSpecial: true,
      },
      {
        text: "Sensor Address",
        value: "temperature_serial_address",
        align: "left",
        sortable: false,
        filterable: true,
      },

      { text: "Options", value: "options", align: "left", sortable: false },
    ],
    roomTypesData: [],

    endpoint: "device_temperature_sensors",

    newItemDialog: false,

    //add edit item details
    editedItem: {},
    editedItemIndex: -1,
    roomTypesForSelectOptions: [],
    errors: {},
    snackbar: false,
    snackbarColor: "red",
    snackbarResponse: "",
    viewMode: false,
  }),
  watch: {
    options: {
      handler() {
        this.getDataFromApi();
      },
      deep: true,
    },
  },
  created() {
    this.getDataFromApi();
  },
  methods: {
    can(per) {
      let u = this.$auth.user;
      return (
        (u && u.permissions.some((e) => e == per || per == "/")) || u.is_master
      );
    },
    AddNewRoom() {
      this.editedItem = {};
      this.editedItemIndex = -1;
      this.viewMode = false;
      this.newItemDialog = true;
    },
    // updateIndex(page) {

    //     this.currentPage = page;
    //     this.cumulativeIndex = (page - 1) * this.perPage;

    // },
    applyFilters() {
      this.$set(this.options, "page", 1);
      this.getDataFromApi(this.endpoint, 1);
    },
    toggleFilter() {
      this.isFilter = !this.isFilter;
    },
    itemIndex(item) {
      return this.data.indexOf(item);
    },
    reload() {
      this.isFilter = false;
      this.filters = {};
      this.$set(this.options, "page", 1);
      this.getDataFromApi(this.endpoint, 1);
    },
    getDataFromApi(url = this.endpoint, customPage = 0) {
      this.loading = true;
      let { sortBy, sortDesc, page, itemsPerPage } = this.options;
      let sortedBy = sortBy ? sortBy[0] : "";
      let sortedDesc = sortDesc ? sortDesc[0] : "";
      if (customPage == 1) page = 1;
      this.currentPage = page;
      this.perPage = itemsPerPage;

      let options = {
        params: {
          page: page,
          sortBy: sortedBy,
          sortDesc: sortedDesc,
          per_page: itemsPerPage,
          company_id: this.$auth.user.company.id,
          ...this.filters,
        },
      };
      this.$axios.get(`${url}?page=${page}`, options).then(({ data }) => {
        this.loading = false;
        this.data = data.data;
        this.totalTableRowsCount = data.total;
      });
    },

    viewItem(item) {
      this.editItem(item, true);
    },
    editItem(item, viewMode = false) {
      this.viewMode = viewMode;
      this.editedItem = {};
      let options = { params: { company_id: this.$auth.user.company.id } };
      this.newItemDialog = true;

      this.$axios
        .get(`${this.endpoint}/${item.id}`, options)
        .then(({ data }) => {
          this.editedItem = data;
          this.editedItemIndex = item.id;
        });
    },
    save() {
      if (this.editedItemIndex != -1) {
        let options = {
          params: {
            company_id: this.$auth.user.company.id,
            device_id: this.device_id,

            ...this.editedItem,
          },
        };

        this.$axios
          .put(`${this.endpoint}/${this.editedItemIndex}`, options.params)
          .then(({ data }) => {
            if (data.status) {
              this.getDataFromApi();
              this.errors = {};
              this.editedItem = {};
              this.snackbar = true;
              this.snackbarColor = "greeen";
              this.snackbarResponse = data.message;

              this.newItemDialog = false;
            } else {
              if (data.errors) {
                this.errors = data.errors;
              } else {
                this.errors = {};

                this.snackbar = true;
                this.snackbarColor = "red";
                this.snackbarResponse = data.message;
              }
            }
          })
          .catch((error) => {
            if (error.response && error.response.status === 422) {
              this.errors = error.response.data.errors || {};
              this.snackbar = true;
              this.snackbarColor = "red";
              this.snackbarResponse =
                error.response.data.message || "Validation failed.";
            } else {
              this.errors = {};
              this.snackbar = true;
              this.snackbarColor = "red";
              this.snackbarResponse = "An unexpected error occurred.";
              console.error(error);
            }
          });
      } else {
        let options = {
          params: {
            company_id: this.$auth.user.company.id,
            user_id: this.$auth.user.id,
            device_id: this.device_id,
            ...this.editedItem,
          },
        };

        this.$axios
          .post(`${this.endpoint}`, options.params)
          .then(({ data }) => {
            if (data.status) {
              this.getDataFromApi();
              this.errors = {};
              this.editedItem = {};
              this.snackbar = true;
              this.snackbarColor = "greeen";
              this.snackbarResponse = data.message;

              this.newItemDialog = false;
            } else {
              if (data.errors) {
                this.errors = data.errors;
              } else {
                this.errors = {};

                this.snackbar = true;
                this.snackbarColor = "red";
                this.snackbarResponse = data.message;
              }
            }
          })
          .catch((error) => {
            if (error.response && error.response.status === 422) {
              this.errors = error.response.data.errors || {};
              this.snackbar = true;
              this.snackbarColor = "red";
              this.snackbarResponse =
                error.response.data.message || "Validation failed.";
            } else {
              this.errors = {};
              this.snackbar = true;
              this.snackbarColor = "red";
              this.snackbarResponse = "An unexpected error occurred.";
              console.error(error);
            }
          });
      }
    },
    deleteItem(item) {
      confirm("Are you sure you wish to delete?") &&
        this.$axios
          .delete(this.endpoint + "/" + item.id)
          .then(({ data }) => {
            this.getDataFromApi();
            this.snackbar = data.status;
            this.snackbarResponse = data.message;
          })
          .catch((err) => console.log(err));
    },
  },
};
</script>
