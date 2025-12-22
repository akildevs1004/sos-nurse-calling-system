<template>
  <div class="pt-2" :id="nameChart" :style="{ width: '100%', height: height + 'px' }" />
</template>

<script>
export default {
  name: "SosHourlyRoomTypeChart",

  props: {
    nameChart: {
      type: String,
      default: "sosHourlyRoomTypeChart",
    },
    height: {
      type: Number,
      default: 220,
    },

    // Optional: allow passing data from parent instead of API
    seriesProp: {
      type: Array,
      default: null,
    },
    categoriesProp: {
      type: Array,
      default: null,
    },
    date_from: {
      type: String,
      default: null,
    },
    date_to: {
      type: String,
      default: null,
    },
    roomType: {
      type: String,
      default: null,
    },
    sosStatus: {
      type: String,
      default: null,
    },
  },

  data() {
    return {
      chart: null,
      _resizeTimer: null,
      _destroyed: false,
      loading: false,

      // Live data from API (hours 0..23)
      categories: Array.from({ length: 24 }, (_, i) => String(i)),
      series: [],
    };
  },

  computed: {
    isDark() {
      return !!this.$vuetify?.theme?.dark;
    },

    labelColor() {
      return this.isDark ? "rgba(255,255,255,0.75)" : "rgba(0,0,0,0.70)";
    },

    gridColor() {
      return this.isDark ? "rgba(255,255,255,0.10)" : "rgba(0,0,0,0.08)";
    },

    finalCategories() {
      if (Array.isArray(this.categoriesProp)) return this.categoriesProp.map((h) => String(h));
      return this.categories;
    },

    finalSeries() {
      if (Array.isArray(this.seriesProp)) return this.normalizeSeries(this.seriesProp);
      return this.series;
    },
  },

  watch: {
    // Full reload on theme change (safe)
    isDark() {
      this.reloadChart();
    },

    // If parent provides series/categories props, update chart
    seriesProp: {
      deep: true,
      handler() {
        this.applyToChart();
      },
    },
    categoriesProp: {
      deep: true,
      handler() {
        this.applyToChart();
      },
    },

    // Height change => rebuild
    height() {
      this.reloadChart();
    },
  },

  async mounted() {
    this._destroyed = false;

    // 1) Create chart ONCE
    await this.initChartSafe();

    // 2) Load API data (unless parent passes props)
    if (!Array.isArray(this.seriesProp) || !Array.isArray(this.categoriesProp)) {
      await this.getDatafromAPI();
    }

    // 3) Apply data to chart
    this.applyToChart();

    window.addEventListener("resize", this.onResize, { passive: true });
  },

  beforeDestroy() {
    this._destroyed = true;
    window.removeEventListener("resize", this.onResize);
    if (this._resizeTimer) clearTimeout(this._resizeTimer);
    this.destroyChart();
  },

  methods: {
    getApex() {
      return window.ApexCharts || (typeof ApexCharts !== "undefined" ? ApexCharts : null);
    },

    onResize() {
      if (!this.chart) return;
      if (this._resizeTimer) clearTimeout(this._resizeTimer);
      this._resizeTimer = setTimeout(() => {
        try {
          this.chart?.resize();
        } catch (_) { }
      }, 120);
    },

    destroyChart() {
      if (!this.chart) return;
      try {
        this.chart.destroy();
      } catch (_) { }
      this.chart = null;
    },

    async initChartSafe() {
      // Ensure DOM is ready and stable before render
      await this.$nextTick();
      await new Promise((r) => requestAnimationFrame(() => r()));

      if (this._destroyed) return;

      const Apex = this.getApex();
      const el = document.getElementById(this.nameChart);
      if (!Apex || !el) return;

      // Prevent duplicate SVG
      this.destroyChart();
      el.innerHTML = "";

      const options = {
        chart: {
          type: "area",
          height: this.height,
          toolbar: { show: false },
          animations: { enabled: false },
          background: "transparent",
          zoom: { enabled: false },
          selection: { enabled: false },
        },

        theme: {
          mode: this.isDark ? "dark" : "light",
        },

        dataLabels: { enabled: false },

        stroke: { curve: "smooth", width: 2 },

        fill: {
          type: "gradient",
          gradient: { opacityFrom: 0.30, opacityTo: 0.05, stops: [20, 100] },
        },

        xaxis: {
          type: "category", // hours 0..23
          categories: this.finalCategories,
          labels: { style: { colors: this.labelColor } },
          axisBorder: { show: false },
          axisTicks: { show: false },
          title: { text: "Hour", style: { color: this.labelColor } },
        },

        yaxis: {
          labels: {
            style: { colors: this.labelColor },
            formatter: (v) => `${Math.round(v)}`,
          },
          title: { text: "SOS Count", style: { color: this.labelColor } },
        },

        // CRITICAL: disable x-axis tooltip to avoid Apex internal null classList bug
        tooltip: {
          shared: true,
          intersect: false,
          x: { show: false },
          y: { formatter: (val) => `${val}` },
        },

        legend: {
          position: "top",
          horizontalAlign: "right",
          labels: { colors: this.labelColor },
        },

        grid: {
          borderColor: this.gridColor,
          strokeDashArray: 3,
        },

        // Start empty; we update right after data loads
        series: [],
      };

      this.chart = new Apex(el, options);

      try {
        await this.chart.render();
      } catch (_) {
        // If component unmounted during render, ignore
      }
    },

    async reloadChart() {
      if (this._destroyed) return;
      await this.initChartSafe();
      this.applyToChart();
    },

    normalizeSeries(series) {
      // Ensures each series has 24 points (0..23)
      return (Array.isArray(series) ? series : []).map((s) => ({
        name: s?.name ?? "Unknown",
        data: Array.isArray(s?.data)
          ? [...s.data, ...Array(Math.max(0, 24 - s.data.length)).fill(0)].slice(0, 24)
          : Array(24).fill(0),
      }));
    },

    applyToChart() {
      if (!this.chart || this._destroyed) return;

      const cats = this.finalCategories?.length
        ? this.finalCategories.map((h) => String(h))
        : Array.from({ length: 24 }, (_, i) => String(i));

      const ser = this.finalSeries?.length ? this.finalSeries : [];

      try {
        this.chart.updateOptions(
          {
            theme: { mode: this.isDark ? "dark" : "light" },
            xaxis: {
              type: "category",
              categories: cats,
              labels: { style: { colors: this.labelColor } },
              title: { text: "Hour", style: { color: this.labelColor } },
            },
            yaxis: {
              labels: { style: { colors: this.labelColor } },
              title: { text: "SOS Count", style: { color: this.labelColor } },
            },
            legend: { labels: { colors: this.labelColor } },
            grid: { borderColor: this.gridColor },
            tooltip: {
              shared: true,
              intersect: false,
              x: { show: false },
              y: { formatter: (val) => `${val}` },
            },
          },
          false,
          true
        );

        this.chart.updateSeries(ser, true);
      } catch (_) { }
    },

    async getDatafromAPI() {
      if (this.loading || this._destroyed) return;
      this.loading = true;

      try {
        const res = await this.$axios.get("sos_hourly_report", {
          params: {
            company_id: this.$auth.user.company_id,
            // from_date: this.fromDate,
            // to_date: this.toDate,

            date_from: this.date_from || null,
            date_to: this.date_to || null,
            sosStatus: this.sosStatus || null,
            roomType: this.roomType || null,
          },
        });

        if (this._destroyed) return;

        const categories = Array.isArray(res.data?.categories) ? res.data.categories : [];
        const series = Array.isArray(res.data?.series) ? res.data.series : [];

        // Enforce 0..23 categories if API returns empty
        this.categories = categories.length
          ? categories.map((h) => String(h))
          : Array.from({ length: 24 }, (_, i) => String(i));

        this.series = this.normalizeSeries(series);
      } catch (e) {
        // Optional: handle errors
        // console.error(e);
        this.series = [];
      } finally {
        this.loading = false;
      }
    },
  },


};
</script>

<style scoped>
/* No extra styling needed; Vuetify theme controls surrounding UI */
</style>
