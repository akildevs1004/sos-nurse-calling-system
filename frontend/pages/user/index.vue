<template>
  <div v-if="can(`user_access`)" :class="$vuetify.theme.dark ? 'theme-dark' : 'theme-light'" class="page-root">
    <div class="text-center ma-2">
      <v-snackbar v-model="snackbar" top="top" elevation="24"
        :color="$vuetify.theme.dark ? 'grey darken-3' : 'secondary'">
        {{ response }}
      </v-snackbar>
    </div>

    <!-- Header -->
    <v-row class="mt-5 mb-5 align-center">
      <v-col cols="12" md="6">
        <h3 class="mb-1">{{ Module }}</h3>

      </v-col>

      <v-col cols="12" md="6">
        <div class="text-right d-flex justify-end align-center flex-wrap">
          <!-- Theme Toggle -->
          <v-btn icon class="mr-2 mb-2" :title="$vuetify.theme.dark ? 'Switch to Light' : 'Switch to Dark'"
            @click="toggleTheme">
            <v-icon>
              {{ $vuetify.theme.dark ? "mdi-weather-sunny" : "mdi-weather-night" }}
            </v-icon>
          </v-btn>

          <v-btn v-if="can(`user_delete`)" small color="error" class="mr-2 mb-2" @click="delteteSelectedRecords">
            Delete Selected Records
          </v-btn>

          <v-btn v-if="can(`user_create`)" small color="primary" to="/user/create" class="mb-2">
            {{ Module }} +
          </v-btn>
        </div>
      </v-col>
    </v-row>

    <!-- Data Table -->
    <v-data-table v-if="can(`user_view`)" v-model="ids" item-key="id" :headers="headers" :items="data"
      :server-items-length="total" :loading="loading" :options.sync="options" :dark="$vuetify.theme.dark" :footer-props="{
        itemsPerPageOptions: [50, 100, 500, 1000],
      }" class="elevation-1">
      <template v-slot:item.action="{ item }">
        <v-icon v-if="can(`user_edit`)" color="secondary" small class="mr-2" @click="editItem(item)">
          mdi-pencil
        </v-icon>

        <v-icon v-if="can(`user_delete`)" color="error" small @click="deleteItem(item)">
          {{ item.role === "customer" ? "" : "mdi-delete" }}
        </v-icon>
      </template>

      <template v-slot:no-data>
        <!-- intentionally blank -->
      </template>
    </v-data-table>

    <NoAccess v-else />
  </div>

  <NoAccess v-else />
</template>

<script>
const THEME_STORAGE_KEY = "theme_dark";

export default {
  data: () => ({
    Module: "User",
    options: {},
    endpoint: "users",
    search: "",
    snackbar: false,
    dialog: false,
    ids: [],
    loading: false,
    total: 0,
    headers: [],
    editedIndex: -1,
    editedItem: { name: "" },
    defaultItem: { name: "" },
    response: "",
    data: [],
    errors: [],
  }),

  computed: {
    formTitle() {
      return this.editedIndex === -1
        ? `New ${this.Module}`
        : `Edit ${this.Module}`;
    },
  },

  watch: {
    options: {
      handler() {
        this.getHeaders();
        this.getDataFromApi();
      },
      deep: true,
    },
  },

  created() {
    this.loading = true;
    this.getHeaders();
  },

  mounted() {
    // Restore theme preference
    const saved = localStorage.getItem(THEME_STORAGE_KEY);
    if (saved !== null) {
      this.$vuetify.theme.dark = saved === "true";
    }
  },

  methods: {
    toggleTheme() {
      this.$vuetify.theme.dark = !this.$vuetify.theme.dark;
      localStorage.setItem(THEME_STORAGE_KEY, String(this.$vuetify.theme.dark));
    },

    getHeaders() {
      this.headers = [
        { text: this.Module, align: "left", sortable: false, value: "name" },
        { text: "Email", align: "left", sortable: false, value: "email" },
        { text: "Role", align: "left", sortable: false, value: "role.name" },
        { text: "Actions", align: "center", value: "action", sortable: false },
      ];
    },

    can(per) {
      return this.$pagePermission.can(per, this);
    },

    getDataFromApi(url = this.endpoint) {
      this.loading = true;

      const { sortBy, sortDesc, page, itemsPerPage } = this.options;

      const options = {
        params: {
          per_page: itemsPerPage,
          company_id: this.$auth.user.company_id,
        },
      };

      this.$axios
        .get(`${url}?page=${page}`, options)
        .then(({ data }) => {
          let items = data.data;

          if (sortBy && sortBy.length === 1 && sortDesc && sortDesc.length === 1) {
            items = this.sorting(items, sortBy, sortDesc);
          }

          this.data = items;
          this.total = data.total;
          this.loading = false;
        })
        .catch((err) => {
          this.loading = false;
          this.snackbar = true;
          this.response = err?.message || "Failed to load users.";
          // console.log(err);
        });
    },

    sorting(items, sortBy, sortDesc) {
      // NOTE: This sorting only works for flat fields (not "role.name").
      // Kept same behavior as your original code.
      return items.sort((a, b) => {
        const sortA = a[sortBy[0]];
        const sortB = b[sortBy[0]];

        if (sortDesc[0]) {
          if (sortA < sortB) return 1;
          if (sortA > sortB) return -1;
          return 0;
        } else {
          if (sortA < sortB) return -1;
          if (sortA > sortB) return 1;
          return 0;
        }
      });
    },

    searchIt(e) {
      if (e.length === 0) {
        this.getDataFromApi();
      } else if (e.length > 2) {
        this.getDataFromApi(`${this.endpoint}/search/${e}`);
      }
    },

    editItem(item) {
      this.$router.push("/user/" + item.id);
    },

    delteteSelectedRecords() {
      const just_ids = this.ids.map((e) => e.id);

      confirm(
        "Are you sure you wish to delete selected records , to mitigate any inconvenience in future."
      ) &&
        this.$axios
          .post(`${this.endpoint}/delete/selected`, { ids: just_ids })
          .then(({ data }) => {
            if (!data.status) {
              this.response = "Something went wrong.";
              this.snackbar = true;
            } else {
              this.getDataFromApi();
              this.snackbar = true;
              this.ids = [];
              this.response = "Selected records has been deleted";
            }
          })
          .catch((err) => {
            this.snackbar = true;
            this.response = err?.message || "Failed to delete selected records.";
            // console.log(err);
          });
    },

    deleteItem(item) {
      confirm(
        "Are you sure you wish to delete , to mitigate any inconvenience in future."
      ) &&
        this.$axios
          .delete(this.endpoint + "/" + item.id)
          .then(({ data }) => {
            if (!data.status) {
              this.snackbar = true;
              this.response = data.message;
            } else {
              const index = this.data.indexOf(item);
              if (index > -1) this.data.splice(index, 1);
              this.snackbar = true;
              this.response = data.message;
            }
          })
          .catch((err) => {
            this.snackbar = true;
            this.response = err?.message || "Failed to delete user.";
            // console.log(err);
          });
    },
  },
};
</script>

<style scoped>
.page-root {
  min-height: 100vh;
  padding: 8px 12px;
}

/* Light/Dark container */
.theme-light {
  background: #f8fafc;
  color: #0f172a;
}

.theme-dark {
  background: #0f172a;
  color: #e5e7eb;
}

/* Optional: breadcrumb styling */
.breadcrumb {
  opacity: 0.75;
  font-size: 13px;
}

/* Optional: make table surface feel correct in dark mode */
.theme-dark ::v-deep .v-data-table {
  background-color: #1e293b;
}

/* Optional: better header separation */
.theme-dark h3,
.theme-dark .breadcrumb {
  color: #e5e7eb;
}
</style>
