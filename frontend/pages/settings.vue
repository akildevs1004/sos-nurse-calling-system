<template>
  <NoAccess v-if="!$pagePermission.can('company_view', this)" />
  <div v-else style="width: 100%; margin-top: -20px">
    <div v-if="!preloader">
      <div class="text-center ma-2">
        <v-snackbar v-model="snackbar" top="top" elevation="24" duration="3000">
          {{ response }}
        </v-snackbar>
      </div>

      <v-row>
        <v-col>
          <v-card class="mt-2">
            <v-tabs class="pt-3" :vertical="vertical">
              <v-tabs-slider color="violet"></v-tabs-slider>
              <v-tab>
                <span>Company Profile</span>
              </v-tab>

              <v-tab>
                <span>Settings</span>
              </v-tab>
              <v-tab-item>
                <v-card flat>
                  <v-card-text>
                    <v-row>
                      <v-col cols="12" sm="6" md="2" style="margin: auto">
                        <v-card
                          elevation="0"
                          class="ml-1 mr-1"
                          style="text-align: center; margin: auto"
                        >
                          <!-- <v-card-title>Profile Picture</v-card-title> -->
                          <div style="width: 100%; text-align: center">
                            <div class="pb-2">
                              <v-img
                                class="company-profile-picture"
                                style="
                                  width: 200px;
                                  height: 200px;
                                  border: 1px solid #ddd;
                                  border-radius: 50%;
                                  margin: 0 auto;
                                "
                                :src="
                                  previewImage ||
                                  company_payload.logo ||
                                  '/no-image.PNG'
                                "
                              ></v-img>
                            </div>
                          </div>
                          <v-btn
                            @click="onpick_attachment"
                            text
                            v-if="
                              !upload.name && !$vuetify.breakpoint.smAndDown
                            "
                            color="primary"
                            elevation="0"
                            outlined
                            plain
                            rounded
                            >Upload Logo</v-btn
                          >

                          <v-btn
                            text
                            color="primary"
                            elevation="0"
                            outlined
                            plain
                            rounded
                            v-if="upload.name"
                            style="width: 100px"
                            @click="cancelAttachment"
                            >Cancel
                          </v-btn>
                          <v-btn
                            text
                            color="primary"
                            elevation="0"
                            outlined
                            plain
                            rounded
                            v-if="upload.name"
                            style="width: 100px"
                            @click="update_company"
                            >{{ !upload.name ? "Change Logo" : "Submit" }}
                          </v-btn>
                        </v-card>

                        <input
                          required
                          type="file"
                          @change="attachment"
                          style="display: none"
                          accept="image/*"
                          ref="attachment_input"
                        />

                        <span
                          v-if="errors && errors.logo"
                          class="text-danger mt-2"
                          >{{ errors.logo[0] }}</span
                        >
                      </v-col>
                      <v-col cols="10">
                        <v-card elevation="0">
                          <!-- <v-card-title>Information</v-card-title> -->

                          <v-card-text>
                            <v-row>
                              <v-col cols="12" sm="6" md="4">
                                <v-text-field
                                  readonly
                                  label="Company Code"
                                  dense
                                  outlined
                                  v-model="company_payload.company_code"
                                  hide-details
                                ></v-text-field>
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <v-text-field
                                  label="Company Name"
                                  readonly
                                  dense
                                  outlined
                                  hide-details
                                  v-model="company_payload.name"
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.name"
                                  class="text-danger mt-2"
                                  >{{ errors.name[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">Company Email</label>
                        <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Company Email"
                                  readonly
                                  dense
                                  outlined
                                  hide-details
                                  v-model="user_payload.email"
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.email"
                                  class="text-danger mt-2"
                                  >{{ errors.email[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <v-text-field
                                  readonly
                                  label="Contact Person Name"
                                  dense
                                  outlined
                                  v-model="contact_payload.name"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.name"
                                  class="text-danger mt-2"
                                  >{{ errors.name[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">
                              Contact Person Number
                            </label>
                            <span class="text-danger">*</span> -->
                                <v-text-field
                                  readonly
                                  label="Contact Person Number"
                                  dense
                                  outlined
                                  v-model="contact_payload.number"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.number"
                                  class="text-danger mt-2"
                                  >{{ errors.number[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">
                              Contact Person Position
                            </label>
                            <span class="text-danger">*</span> -->
                                <v-text-field
                                  readonly
                                  label="Contact Person Position"
                                  dense
                                  outlined
                                  v-model="contact_payload.position"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.position"
                                  class="text-danger mt-2"
                                  >{{ errors.position[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">
                              Whatsapp (with Country Code ex: 9199XXX)
                            </label>
                            <span class="text-danger">*</span> -->
                                <v-text-field
                                  readonly
                                  label="Whatsapp (with Country Code ex: 97199XXX)"
                                  dense
                                  outlined
                                  v-model="contact_payload.whatsapp"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.whatsapp"
                                  class="text-danger mt-2"
                                  >{{ errors.whatsapp[0] }}</span
                                >
                              </v-col>
                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">Mol ID</label>
                        <span class="text-danger">*</span> -->
                                <v-text-field
                                  readonly
                                  label="Mobile Number"
                                  color="primary"
                                  dense
                                  outlined
                                  v-model="company_payload.mol_id"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.mol_id"
                                  class="text-danger mt-2"
                                  >{{ errors.mol_id[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">P.O Box</label>
                        <span class="text-danger">*</span> -->
                                <v-text-field
                                  readonly
                                  label="P.O Box"
                                  color="primary"
                                  dense
                                  outlined
                                  v-model="company_payload.p_o_box_no"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.p_o_box_no"
                                  class="text-danger mt-2"
                                  >{{ errors.p_o_box_no[0] }}</span
                                >
                              </v-col>
                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label"> Lat </label>
                            <span class="text-danger">*</span> -->
                                <v-text-field
                                  readonly
                                  label="Latitude"
                                  dense
                                  outlined
                                  v-model="geographic_payload.lat"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.lat"
                                  class="text-danger mt-2"
                                  >{{ errors.lat[0] }}</span
                                >
                              </v-col>
                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label"> Lon </label>
                            <span class="text-danger">*</span> -->
                                <v-text-field
                                  readonly
                                  label="Longitude"
                                  dense
                                  outlined
                                  v-model="geographic_payload.lon"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.lon"
                                  class="text-danger mt-2"
                                  >{{ errors.lon[0] }}</span
                                >
                              </v-col>
                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label"> Location </label>
                            <span class="text-danger">*</span> -->
                                <v-textarea
                                  readonly
                                  label="Location"
                                  dense
                                  outlined
                                  height="30px"
                                  v-model="geographic_payload.location"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                >
                                </v-textarea>
                                <span
                                  v-if="errors && errors.location"
                                  class="text-danger mt-2"
                                  >{{ errors.location[0] }}</span
                                >
                              </v-col>
                              <!-- <v-col>
                                <v-autocomplete
                                  class="pb-0"
                                  hide-details
                                  v-model="geographic_payload.utc_time_zone"
                                  outlined
                                  dense
                                  label="Time Zone(Ex:UTC+)"
                                  :items="getTimezones()"
                                  item-value="key"
                                  item-text="text"

                                ></v-autocomplete>
                              </v-col> -->

                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">Member From</label>
                        <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Member From"
                                  readonly
                                  dense
                                  outlined
                                  hide-details
                                  v-model="company_payload.member_from"
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.member_from"
                                  class="text-danger mt-2"
                                  >{{ errors.member_from[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">Expiry Date</label>
                        <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Expiry Date"
                                  readonly
                                  dense
                                  outlined
                                  hide-details
                                  v-model="company_payload.expiry"
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.expiry"
                                  class="text-danger mt-2"
                                  >{{ errors.expiry[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">Max Branches</label>
                        <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Max Branches"
                                  readonly
                                  dense
                                  outlined
                                  hide-details
                                  v-model="company_payload.max_branches"
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.max_branches"
                                  class="text-danger mt-2"
                                  >{{ errors.max_branches[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">Max Employees</label>
                        <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Max Employees"
                                  readonly
                                  dense
                                  outlined
                                  hide-details
                                  v-model="company_payload.max_employee"
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.max_employee"
                                  class="text-danger mt-2"
                                  >{{ errors.max_employee[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="4">
                                <!-- <label class="col-form-label">Max Devices</label>
                        <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Max Devices"
                                  readonly
                                  dense
                                  outlined
                                  hide-details
                                  v-model="company_payload.max_devices"
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.max_devices"
                                  class="text-danger mt-2"
                                  >{{ errors.max_devices[0] }}</span
                                >
                              </v-col>
                            </v-row>
                          </v-card-text>
                        </v-card>
                      </v-col>
                      <v-col cols="12" style="display: none">
                        <v-card elevation="2" style="height: 500px">
                          <v-card-title>Contact Details</v-card-title>

                          <v-card-text>
                            <v-row>
                              <v-col cols="6">
                                <v-text-field
                                  label="Contact Person Name"
                                  dense
                                  outlined
                                  v-model="contact_payload.name"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.name"
                                  class="text-danger mt-2"
                                  >{{ errors.name[0] }}</span
                                >
                              </v-col>

                              <v-col cols="12" sm="6" md="6">
                                <!-- <label class="col-form-label">
                              Contact Person Number
                            </label>
                            <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Contact Person Number"
                                  dense
                                  outlined
                                  v-model="contact_payload.number"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.number"
                                  class="text-danger mt-2"
                                  >{{ errors.number[0] }}</span
                                >
                              </v-col>

                              <v-col cols="6">
                                <!-- <label class="col-form-label">
                              Contact Person Position
                            </label>
                            <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Contact Person Position"
                                  dense
                                  outlined
                                  v-model="contact_payload.position"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.position"
                                  class="text-danger mt-2"
                                  >{{ errors.position[0] }}</span
                                >
                              </v-col>

                              <v-col cols="6">
                                <!-- <label class="col-form-label">
                              Whatsapp (with Country Code ex: 9199XXX)
                            </label>
                            <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Whatsapp (with Country Code ex: 97199XXX)"
                                  dense
                                  outlined
                                  v-model="contact_payload.whatsapp"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.whatsapp"
                                  class="text-danger mt-2"
                                  >{{ errors.whatsapp[0] }}</span
                                >
                              </v-col>
                              <v-col cols="6" md="6" sm="6">
                                <!-- <label class="col-form-label">Mol ID</label>
                        <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Mobile Number"
                                  color="primary"
                                  dense
                                  outlined
                                  v-model="company_payload.mol_id"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.mol_id"
                                  class="text-danger mt-2"
                                  >{{ errors.mol_id[0] }}</span
                                >
                              </v-col>

                              <v-col cols="6" md="6" sm="6">
                                <!-- <label class="col-form-label">P.O Box</label>
                        <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="P.O Box"
                                  color="primary"
                                  dense
                                  outlined
                                  v-model="company_payload.p_o_box_no"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.p_o_box_no"
                                  class="text-danger mt-2"
                                  >{{ errors.p_o_box_no[0] }}</span
                                >
                              </v-col>
                              <v-col cols="6">
                                <!-- <label class="col-form-label"> Lat </label>
                            <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Latitude"
                                  dense
                                  outlined
                                  v-model="geographic_payload.lat"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.lat"
                                  class="text-danger mt-2"
                                  >{{ errors.lat[0] }}</span
                                >
                              </v-col>
                              <v-col cols="6">
                                <!-- <label class="col-form-label"> Lon </label>
                            <span class="text-danger">*</span> -->
                                <v-text-field
                                  label="Longitude"
                                  dense
                                  outlined
                                  v-model="geographic_payload.lon"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-text-field>
                                <span
                                  v-if="errors && errors.lon"
                                  class="text-danger mt-2"
                                  >{{ errors.lon[0] }}</span
                                >
                              </v-col>
                              <v-col cols="12">
                                <!-- <label class="col-form-label"> Location </label>
                            <span class="text-danger">*</span> -->
                                <v-textarea
                                  label="Location"
                                  dense
                                  outlined
                                  height="50px"
                                  v-model="geographic_payload.location"
                                  hide-details
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                >
                                </v-textarea>
                                <span
                                  v-if="errors && errors.location"
                                  class="text-danger mt-2"
                                  >{{ errors.location[0] }}</span
                                >
                              </v-col>
                              <v-col>
                                <v-autocomplete
                                  class="pb-0"
                                  hide-details
                                  v-model="geographic_payload.utc_time_zone"
                                  outlined
                                  dense
                                  label="Time Zone(Ex:UTC+)"
                                  :items="getTimezones()"
                                  item-value="key"
                                  item-text="text"
                                  :disabled="
                                    !$pagePermission.can('company_edit', this)
                                  "
                                ></v-autocomplete>
                              </v-col>
                            </v-row>
                          </v-card-text>
                        </v-card>
                      </v-col>
                    </v-row>

                    <!-- <v-row>
                      <v-col cols="12">
                        <div class="text-right">
                          <v-btn
                            v-if="$pagePermission.can('company_edit', this)"
                            small
                            :loading="loading"
                            color="primary"
                            @click="update_company"
                          >
                            Submit
                          </v-btn>
                        </div>
                      </v-col>
                    </v-row> -->
                  </v-card-text>
                </v-card>
              </v-tab-item>
              <v-tab-item>
                <v-card>
                  <v-card-text>
                    <v-row>
                      <v-col cols="12" sm="6" md="3">
                        <div class="form-group">
                          <label class="col-form-label">Theme</label>

                          <v-select
                            outlined
                            dense
                            small
                            v-model="company_payload.theme"
                            :items="[
                              { text: 'Light', value: 'light' },
                              { text: 'Dark', value: 'dark' },
                            ]"
                          >
                          </v-select>
                        </div>
                        <v-col cols="12">
                          <div class="text-right">
                            <v-btn
                              dark
                              small
                              :loading="loading_password"
                              color="primary"
                              @click="update_company()"
                            >
                              Update
                            </v-btn>
                          </div>
                        </v-col>
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </v-tab-item>
            </v-tabs>
          </v-card>
        </v-col>
      </v-row>
    </div>
    <Preloader v-else />
  </div>
</template>

<script>
import timeZones from "../defaults/utc_time_zones.json";

export default {
  components: {},

  data: () => ({
    contact_payload_update: {},
    business_licence_payload: {},
    timeZones,
    originalURL: process.env.APP_URL + "register/visitor/walkin/", //`https://mytime2cloud.com/register/visitor/walkin/`,
    fullCompanyLink: null,
    qrCompanyCodeDataURL: null,
    show_password_confirm: false,
    current_password_show: false,
    show_password: false,
    loading_password: false,
    menuIssueDate: false,
    menuExpiryDate: false,
    IssueDate: null,
    vertical: false,
    id: "",
    loading: false,
    preloader: true,
    upload: {
      name: "",
    },
    tableHeight: 600,
    company_payload: {
      name: "",
      logo: "",
      member_from: "",
      expiry: "",
      max_branches: "",
      max_employee: "",
      max_devices: "",
      mol_id: "",
      p_o_box_no: "",
    },

    company_trade_license: {
      license_no: "",
      license_type: "",
      emirate: "",
      makeem_no: "",
      manager: "",
      issue_date: "",
      expiry_date: "",
    },

    contact_payload: {
      name: "",
      number: "",
      position: "",
      whatsapp: "",
    },
    user_payload: {
      password: "",
      password_confirmation: "",
    },
    payload: {
      password: "",
      current_password: "",
      password_confirmation: "",
    },
    geographic_payload: {
      lat: "",
      lon: "",
      location: "",
    },
    e1: 1,
    errors: [],
    previewImage: null,
    data: {},
    response: "",
    snackbar: false,
    menu_start_date: false,
    menu_end_date: false,
  }),
  async mounted() {
    this.tableHeight = window.innerHeight - 90;
    try {
      if (window)
        window.addEventListener("resize", () => {
          this.tableHeight = window.innerHeight - 90;
        });
    } catch (e) {}
  },
  async created() {
    try {
      this.getDataFromApi();
      if (process.env.ENVIRONMENT == "local") {
        this.originalURL = `http://${process.env.LOCAL_IP}:${process.env.LOCAL_PORT}/register/visitor/walkin/`;
      }
      this.fullCompanyLink = this.originalURL + this.$auth.user.company_id;
      //this.generateCompanyQRCode(this.fullCompanyLink);
    } catch (e) {}
  },
  methods: {
    can(per) {
      return this.$pagePermission.can(per, this);
    },
    update_company_currency() {
      this.$axios
        .post(`/company/${this.$auth?.user?.company?.id}/update_currency`, {
          currency: this.company_payload.currency,
        })
        .then(({ data }) => {
          this.loading = false;

          if (!data.status) {
            this.errors = data.errors;
          } else {
            this.snackbar = true;
            this.response = "Currency updated successfully";
          }
        })
        .catch((e) => console.log(e));
    },

    getTimezones() {
      return Object.keys(this.timeZones).map((key) => ({
        offset: this.timeZones[key].offset,
        time_zone: this.timeZones[key].time_zone,
        key: key,
        text:
          this.timeZones[key].time_zone +
          " - " +
          key +
          " - " +
          this.timeZones[key].offset,
      }));
    },
    getDataFromApi() {
      this.id = this.$auth.user.company_id;
      this.$axios
        .get(`company/${this.$auth.user.company_id}`)
        .then(({ data }) => {
          let r = data.record;
          this.company_payload = r;
          this.contact_payload = r.contact;
          this.contact_payload_update = r.contact;
          this.user_payload = r.user;

          if (r.trade_license) {
            this.company_trade_license = r.trade_license;
          }

          let mf = this.formatted_date(r.member_from);
          let exp = this.formatted_date(r.expiry);
          this.company_payload.member_from = mf;
          this.company_payload.expiry = exp;

          this.geographic_payload = {
            lat: this.company_payload.lat,
            lon: this.company_payload.lon,
            location: this.company_payload.location,
            utc_time_zone: this.company_payload.utc_time_zone,
          };
          this.preloader = false;
        });
    },

    formatted_date(v) {
      let [year, month, date] = v.split("/");
      return `${year}-${month}-${date}`;
    },
    onpick_attachment() {
      this.$refs.attachment_input.click();
    },

    attachment(e) {
      this.upload.name = e.target.files[0] || "";

      let input = this.$refs.attachment_input;
      let file = input.files;
      if (file && file[0]) {
        let reader = new FileReader();
        reader.onload = (e) => {
          this.previewImage = e.target.result;
        };
        reader.readAsDataURL(file[0]);
        this.$emit("input", file[0]);
      }
      this.loading = false;
    },
    cancelAttachment() {
      this.upload.name = "";
      this.previewImage = null;
    },
    update_company() {
      // this.update_contact();
      // this.update_geographic();

      let payload = new FormData();

      payload.append("theme", this.company_payload.theme);

      this.start_process(
        `/company/${this.id}/update_settings`,
        payload,
        `Company`
      ).then(() => {
        setTimeout(() => {
          window.location.reload();
        }, 1000 * 3);
      });
      this.loading = false;
    },
    update_contact() {
      // let payload = new FormData();

      // if (this.contact_payload_update.contact_person_name)
      //   payload.append("name", this.contact_payload_update.contact_person_name);
      // if (this.contact_payload_update.contact_person_number)
      //   payload.append(
      //     "number",
      //     this.contact_payload_update.contact_person_number
      //   );
      // if (this.contact_payload_update.contact_person_position)
      //   payload.append(
      //     "position",
      //     this.contact_payload_update.contact_person_position
      //   );
      // if (this.contact_payload_update.contact_person_whatsapp)
      //   payload.append(
      //     "whatsapp",
      //     this.contact_payload_update.contact_person_whatsapp
      //   );

      this.start_process(
        `/company/${this.id}/update/contact`,
        this.contact_payload_update,
        `Contact`
      );
    },

    update_license() {
      this.start_process(
        `/company/${this.id}/trade-license`,
        this.company_trade_license,
        `Trade License`
      );
    },
    update_geographic() {
      this.start_process(
        `/company/${this.id}/update/geographic`,
        this.geographic_payload,
        `Geographic Info`
      );
    },
    update_user() {
      this.start_process(
        `/company/${this.id}/update/user`,
        this.user_payload,
        `User`
      );
    },
    async start_process(url, payload, model) {
      this.loading = true;

      this.$axios
        .post(url, payload)
        .then(({ data }) => {
          this.loading = false;

          if (!data.status) {
            this.errors = data.errors;
          } else {
            this.snackbar = true;
            this.response = model + " updated successfully";

            this.upload.name = "";
            this.errors = [];
            this.loading = false;
          }
        })
        .catch((e) => console.log(e));
    },
  },
};
</script>
