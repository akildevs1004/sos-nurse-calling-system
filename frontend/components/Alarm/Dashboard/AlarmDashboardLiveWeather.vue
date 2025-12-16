<template>
  <div style="width: 100%">
    <!-- Header Section -->
    <div style="margin-bottom: 30px">
      <span style="color: #fff; font-size: 14px; font-weight: bold"
        >Hello, {{ customerName }}</span
      >
      <span style="float: right"
        ><v-icon size="40" color="green" @click="toggleLiveData()"
          >mdi-toggle-switch</v-icon
        ></span
      >
      <div style="font-weight: 400; color: #3498db; font-size: 12px">
        Welcome To XtremeGuard AI Alarm Sensor Monitoring System with Instant
        Alert Messaging and Calling Controller.
      </div>
    </div>

    <!-- City Selection and Last Updated -->
    <div
      style="
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        flex-wrap: wrap;
      "
    >
      <v-row
        ><v-col cols="6">
          <div v-if="weatherData" style="color: #7f8c8d; font-size: 0.9rem">
            Last updated:
            {{ formatLastUpdated(weatherData.current.last_updated) }}
          </div></v-col
        ><v-col cols="6" class="align-right"
          ><div style="float: right" class="align-right">
            <v-select
              small
              v-model="selectedCity"
              :items="cities"
              label="Select City"
              outlined
              dense
              style="max-width: 250px; color: #fff !important"
              class="white-select"
              @change="getWeatherReport"
            ></v-select></div></v-col
      ></v-row>
    </div>

    <!-- Weather Dashboard -->
    <div v-if="loading" style="text-align: center; padding: 50px">
      <v-progress-circular indeterminate color="primary"></v-progress-circular>
      <div>Loading weather data...</div>
    </div>

    <div v-else-if="weatherData" class="weather-dashboard">
      <v-row justify="space-around" style="margin-top: 20px">
        <!-- Temperature Card -->
        <v-col cols="12" sm="4" md="3">
          <v-card
            class="weather-card"
            elevation="0"
            style="
              background-color: transparent;
              color: #fff;
              text-align: center;
            "
          >
            <div class="card-content">
              <img
                src="../../../static/dashboardicons/temperature.png"
                width="80px"
              />
              <div class="weather-value">
                {{ weatherData.current.temp_c }}°C
              </div>
              <div class="weather-label" style="font-size: 12px">
                Temperature
              </div>
              <div class="weather-feelslike" style="font-size: 12px">
                Feels like: {{ weatherData.current.feelslike_c }}°C
              </div>
            </div>
          </v-card>
        </v-col>

        <!-- Humidity Card -->
        <v-col cols="12" sm="4" md="3">
          <v-card
            class="weather-card"
            elevation="0"
            style="
              background-color: transparent;
              color: #fff;
              text-align: center;
            "
          >
            <div class="card-content">
              <img
                src="../../../static/dashboardicons/humidity.png"
                width="80px"
              />
              <div class="weather-value">
                {{ weatherData.current.humidity }}%
              </div>
              <div class="weather-label" style="font-size: 12px">Humidity</div>
              <div class="weather-detail" style="font-size: 12px">
                Dew point: {{ weatherData.current.dewpoint_c }}°C
              </div>
            </div>
          </v-card>
        </v-col>

        <!-- Weather Condition Card -->
        <v-col cols="12" sm="4" md="3">
          <v-card
            class="weather-card"
            elevation="0"
            style="
              background-color: transparent;
              color: #fff;
              text-align: center;
            "
          >
            <div class="card-content">
              <img
                :src="'https:' + weatherData.current.condition.icon"
                width="80px"
              />
              <div class="weather-value">
                {{ weatherData.current.condition.text }}
              </div>
              <div class="weather-label" style="font-size: 12px">Condition</div>
              <div class="weather-detail" style="font-size: 12px">
                UV Index: {{ weatherData.current.uv }}
              </div>
            </div>
          </v-card>
        </v-col>

        <!-- Wind Card -->
        <v-col cols="12" sm="4" md="3">
          <v-card
            class="weather-card"
            elevation="0"
            style="
              background-color: transparent;
              color: #fff;
              text-align: center;
            "
          >
            <div class="card-content">
              <img
                src="../../../static/dashboardicons/cloud1.png"
                width="80px"
              />
              <div class="weather-value">
                {{ weatherData.current.wind_kph }} km/h
              </div>
              <div class="weather-label" style="font-size: 12px">
                Wind Speed
              </div>
              <div class="weather-detail" style="font-size: 12px">
                Direction: {{ weatherData.current.wind_dir }}
              </div>
            </div>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <div v-else style="text-align: center; padding: 50px; color: #e74c3c">
      <v-icon color="error" large>mdi-alert-circle</v-icon>
      <div>Failed to load weather data. Please try again later.</div>
      <v-btn @click="getWeatherReport" color="primary" style="margin-top: 20px"
        >Retry</v-btn
      >
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      customerName: "Customer",
      weatherData: null,
      loading: false,
      error: null,
      selectedCity: "Dubai",
      cities: [
        "Dubai",
        "Abu Dhabi",
        "Sharjah",
        "Ajman",
        "Umm Al Quwain",
        "Ras Al Khaimah",
        "Fujairah",
        "New York",
        "London",
        "Tokyo",
        "Singapore",
        "Sydney",
      ],
      lastUpdated: null,
      intervalObj: null,
    };
  },

  beforeDestroy() {
    if (this.intervalObj) clearInterval(this.intervalObj);
  },

  created() {
    this.getWeatherReport();

    this.intervalObj = setInterval(() => {
      this.getWeatherReport();
    }, 1000 * 60 * 15);
  },

  methods: {
    toggleLiveData() {
      this.$emit("switchBacktoHistoryData");
    },
    async getWeatherReport() {
      this.loading = true;
      this.error = null;

      try {
        const response = await this.$axios.get("/weather", {
          params: {
            q: this.selectedCity,
          },
        });

        this.weatherData = response.data;
        this.lastUpdated = new Date();
        console.log("Weather data loaded for:", this.selectedCity);
      } catch (error) {
        console.error("Error fetching weather data:", error);
        this.error = error.message || "Failed to load weather data";
      } finally {
        this.loading = false;
      }
    },

    formatLastUpdated(datetime) {
      if (!datetime) return "N/A";
      const options = {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
      };
      return new Date(datetime).toLocaleString("en-US", options);
    },
  },
};
</script>
