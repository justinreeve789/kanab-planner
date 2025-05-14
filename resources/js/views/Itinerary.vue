<template>
  <div class="container mx-auto">
  <div v-if="itineraryData" class="md:pb-0 pb-[85px] text-black">
    <div class="itinerary-stats flex justify-end space-x-1 pr-4 pt-4 align-right text-white" title="Estimated total driving distance" v-if="itineraryData && itineraryData.totalTravelDistance">
      <img class="icon mr-1" src="/images/icons/car.svg" alt="Distance">
      <span class="amount">
          <span class="distance-low">{{ round(itineraryData.totalTravelDistance * 0.85) }}</span>
          -
          <span class="distance-high">{{ round(itineraryData.totalTravelDistance * 1.15) }}</span>
      </span>
      <span class="measurement">mi</span>
    </div>
    <div v-for="(day, index) in itineraryData.days" :key="index" class="p-2 md:p-4">
      <Day :day="day" />
    </div>
  </div>
  <div v-else class="progress w-full h-[85vh] flex justify-center items-center text-6xl relative">
    <span class="text text-center text-6xl text-white font-bold relative">
      Loading
      <span class="loading loading-dots loading-lg text-white absolute ml-2 bottom-0"></span>
    </span>
  </div>
</div>
</template>

<script>
import axios from 'axios';
import Day from './Day.vue';
import { usePreferencesStore } from "../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  watch: {
    'preferencesStore.refresh'(newVal, oldVal) {
      if (newVal === true && oldVal !== true) {
        this.preferencesStore.refresh = false;
        this.$cookies.remove('active_itinerary_id');
        this.itineraryData = null;
        this.fetchItineraryData();
      }
    },
    // 'preferencesStore.itineraryData'(newItineraryData, oldItineraryData) {
    //   if (newItineraryData === null) {
    //     this.itineraryData = null;
    //     this.fetchItineraryData();
    //   }
    // },
    'preferencesStore.dateRange'(newDateRange) {
      this.itineraryParams.start_date = newDateRange.start;
      this.itineraryParams.end_date = newDateRange.end;
      // this.itineraryData = null;
      // this.fetchItineraryData();
      this.preferencesStore.refreshItinerary();
    },
    // 'preferencesStore.isPreferencesOpen'(newVal, oldVal) {
    //   let preferencesChange = false;
    //   if (newVal !== oldVal && newVal === false) {
    //     // When the preferences close, update any values.
    //     if (this.preferencesStore.adventurousness) {
    //     }
    //   }
    // },
    // 'preferencesStore.adventurousness'(newVal) {
    //   this.itineraryParams.adventurousness = newVal;
    //   this.itineraryData = null;
    //   this.fetchItineraryData();
    // },
    // 'preferencesStore.solitariness'(newVal) {
    //   this.itineraryParams.solitariness = newVal;
    //   this.itineraryData = null;
    //   this.fetchItineraryData();
    // },
    // 'preferencesStore.strenuousness'(newVal) {
    //   this.itineraryParams.strenuousness = newVal;
    //   this.itineraryData = null;
    //   this.fetchItineraryData();
    // },
  },
  data() {
    return {
      isEditing: false,
      itineraryData: null,
      itineraryParams: {},
    };
  },
  props: {
    itineraryId: String,
  },
  mounted() {
    this.fetchItineraryData();
  },
  methods: {
    round(num) {
      return Math.round(num);
    },
    getItineraryParams() {
      return {
        adventurousness: this.preferencesStore.adventurousness,
        solitariness: this.preferencesStore.solitariness,
        strenuousness: this.preferencesStore.strenuousness,
        start_date: this.preferencesStore.dateRange ? this.preferencesStore.dateRange.start : null,
        end_date: this.preferencesStore.dateRange ? this.preferencesStore.dateRange.end : null,
        average_hiking_speed: this.preferencesStore.averageHikingSpeed,
        area_ids: this.preferencesStore.areaIds,
        event_ids: this.preferencesStore.eventIds,
        location_ids: this.preferencesStore.locationIds,
        restaurant_ids: this.preferencesStore.restaurantIds,
        trail_ids: this.preferencesStore.trailIds,
      };
    },
    fetchItineraryData() {
      const itineraryParams = this.getItineraryParams();
      const urlParams = new URLSearchParams(window.location.search);
      urlParams.forEach((value, key) => {
        itineraryParams[key] = value;
      });
      let url = '/api/v1/itinerary';
      if (this.itineraryId) {
        url += '/' + this.itineraryId;
      }
      axios
        .get(url, {
          params: itineraryParams,
        })
        .then((response) => {
          this.itineraryData = response.data;
          this.preferencesStore.setAttributesFromItineraryData(this.itineraryData);
          this.$cookies.set('active_itinerary_id', this.preferencesStore.itineraryData.id);
        })
        .catch((error) => {
          console.error('Error fetching itinerary data:', error);
        });
    },
  },
  components: {
    // ActivityStatsModal,
    Day,
  },
};
</script>