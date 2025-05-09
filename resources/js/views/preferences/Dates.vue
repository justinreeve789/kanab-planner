<template>
  <fieldset class="fieldset p-4 border border-base-300 text-lg">
    <legend class="fieldset-legend bg-white text-black text-center">When are you visiting?</legend>
    <div class="flex justify-center">
      <button class="btn btn-primary" @click="selectDates" v-if="!noDates">
        <svg class="w-6 h-6 text-white dark:text-black" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
          <path fill-rule="evenodd" d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z" clip-rule="evenodd"/>
        </svg>
        Choose Dates
      </button>
      <div class="flex items-center ml-4 space-x-2" v-if="!noDates">
        <div class="text-center leading-tight">
          <div class="text-xs">{{ getFormattedDate(getStartDate).month }}</div>
          <div class="text-md">{{ getFormattedDate(getStartDate).day }}</div>
        </div>
        <span class="text-xs" v-if="hasDateRange">&ndash;</span>
        <div class="text-center leading-tight" v-if="hasDateRange">
          <div class="text-xs">{{ getFormattedDate(getEndDate).month }}</div>
          <div class="text-md">{{ getFormattedDate(getEndDate).day }}</div>
        </div>
      </div>
    </div>
    <label class="flex justify-center label mt-4">
      <input class="checkbox checkbox-primary" type="checkbox" v-model="noDates" />
      I don't have specific dates
    </label>
    <div v-if="noDates">
      <p class="my-4 text-center">Select the months you'd be interested in visiting</p>
      <ul class="grid gap-2 sm:grid-cols-2 md:grid-cols-3">
        <li v-for="month in months" :key="month.id" class="p-4">
          <label class="label">
            <input
              class="checkbox checkbox-primary"
              type="checkbox"
              :value="month.id"
              v-model="preferencesStore.preferredMonths"
            />
            {{ month.name }}
          </label>
        </li>
      </ul>
    </div>
  </fieldset>
  <div>
    <CallyCalendar class="absolute left-1/2 -translate-x-1/2" v-show="isCalendarOpen" v-model.lazy="dateRange" />
  </div>
</template>

<script>
import CallyCalendar from "../components/CallyCalendar.vue";
import {usePreferencesStore} from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
    CallyCalendar,
  },
  watch: {
    dateRange(newDates, oldDates) {
      if (newDates.start !== oldDates.start && newDates.end !== oldDates.end) {
        this.preferencesStore.dateRange = newDates;
        this.isCalendarOpen = false;
      }
    },
  },
  data() {
    return {
      isCalendarOpen: false,
      dateRange: {
        start: '',
        end: '',
      },
      noDates: false,
      selectedMonths: [],
    };
  },
  computed: {
    getEndDate() {
      if (this.preferencesStore.dateRange) {
        return this.preferencesStore.dateRange.end;
      }
    },
    getStartDate() {
      if (this.preferencesStore.dateRange) {
        return this.preferencesStore.dateRange.start ?? null;
      }
    },
    hasDateRange() {
      return this.preferencesStore.dateRange;
    },
    months() {
      return [
        { id: 1, name: 'January' },
        { id: 2, name: 'February' },
        { id: 3, name: 'March' },
        { id: 4, name: 'April' },
        { id: 5, name: 'May' },
        { id: 6, name: 'June' },
        { id: 7, name: 'July' },
        { id: 8, name: 'August' },
        { id: 9, name: 'September' },
        { id: 10, name: 'October' },
        { id: 11, name: 'November' },
        { id: 12, name: 'December' },
      ];
    },
  },
  methods: {
    getFormattedDate(date) {
      if (date) {
        const dateObj = new Date(date);
        return {
          month: dateObj.toLocaleString('en-us', {
            month: 'short',
          }),
          day: dateObj.toLocaleString('en-us', {
            day: 'numeric',
          }),
        };
      }
      return { month: '', day: ''};
    },
    selectDates() {
      this.isCalendarOpen = !this.isCalendarOpen;
    },
  },
  mounted() {
    // for (let i = 0; i < this.months.length; i++) {
    //   this.selectedMonths.push(this.months[i].id);
    // }
  },
};
</script>
