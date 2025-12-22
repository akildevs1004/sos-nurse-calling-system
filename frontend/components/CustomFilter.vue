<template>
  <date-picker class="backgroundcolordate" :class="[$vuetify.theme.dark ? 'dark-theme' : 'light-theme']"
    :style="pickerStyle" value-type="format" format="YYYY-MM-DD" type="date" v-model="time3" range
    :disabled="!!disabled" @change="emitFilter()" @clear="clearDates()" />
</template>

<script>
import DatePicker from "vue2-datepicker";
import "vue2-datepicker/index.css";

export default {
  components: { DatePicker },
  props: {
    disabled: { type: [Boolean, String], default: false },
    defaultFilterType: { type: [Number, String], default: 1 },
    height: { type: [Number, String], default: 30 },
    width: { type: [Number, String], default: 200 },
    default_date_from: { type: String, default: null },
    default_date_to: { type: String, default: null },
  },
  data() {
    return {
      time3: null,
      from_date: null,
      to_date: null,
      filterType: 1,
      search: "",
    };
  },

  computed: {
    pickerStyle() {
      const h = String(this.height).replace("px", "");
      const w = String(this.width).replace("px", "");
      return {
        zIndex: 99,
        "--cf-height": `${h}px`,
        "--cf-width": `${w}px`,
      };
    },
  },

  created() {
    this.filterType = this.defaultFilterType ? Number(this.defaultFilterType) : 1;

    const today = new Date().toISOString().slice(0, 10);

    // this.from_date = this.default_date_from || today;
    // this.to_date = this.default_date_to || today;

    // this.time3 = [this.from_date, this.to_date];

    // this.emitFilter(); // initial emit
  },

  methods: {
    clearDates() {
      this.time3 = null;
      this.from_date = null;
      this.to_date = null;

      this.$emit("filter-attr", {
        from: null,
        to: null,
        date_from: null,
        date_to: null,
        type: this.filterType,
        search: this.search,
      });
    },

    emitFilter() {
      const arr = Array.isArray(this.time3) ? this.time3 : [];
      this.from_date = arr[0] || null;
      this.to_date = arr[1] || null;

      // Only emit when both are present (range complete)
      if (!this.from_date || !this.to_date) return;

      this.$emit("filter-attr", {
        from: this.from_date,
        to: this.to_date,
        date_from: this.from_date, // IMPORTANT: for your SOS API
        date_to: this.to_date,     // IMPORTANT: for your SOS API
        type: this.filterType,
        search: this.search,
      });
    },
  },
};
</script>

<style>
/* Size control via CSS variables (no DOM hacking) */
.mx-datepicker {
  width: var(--cf-width, 200px) !important;
}

.mx-input {
  height: var(--cf-height, 30px) !important;
  border: 1px solid #9e9e9e !important;
  margin-top: 2px !important;
  color: black !important;
}

/* Dark theme */
.dark-theme .mx-input {
  background: transparent !important;
  color: #fff !important;
}

.dark-theme .mx-icon-calendar {
  color: #fff !important;
}

/* Calendar table alignment */
.mx-table-date td,
.mx-table-date th {
  text-align: center !important;
}
</style>
