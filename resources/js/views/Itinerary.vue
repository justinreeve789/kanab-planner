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
  <div v-else>
    <Loading />
  </div>
</div>
</template>

<script>
import Day from './Day.vue';
import Loading from "./components/Loading.vue";
import { usePreferencesStore } from "../stores/preferences";
import { useRouter } from 'vue-router';

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    const router = useRouter();
    return { preferencesStore, router };
  },
  watch: {
    'preferencesStore.refresh'(newVal, oldVal) {
      if (newVal === true && oldVal !== true) {
        this.preferencesStore.refresh = false;
        this.$cookies.remove('active_itinerary_id');
        this.itineraryData = null;
        this.$router.push('/');
        this.fetchItineraryData();
      }
    },
    'preferencesStore.dateRange'(newDateRange) {
      this.itineraryParams.start_date = newDateRange.start;
      this.itineraryParams.end_date = newDateRange.end;
      // this.itineraryData = null;
      // this.fetchItineraryData();
      // this.$cookies.remove('active_itinerary_id');
      this.preferencesStore.refreshItinerary();
    },
    // 'preferencesStore.itineraryData'(newItineraryData, oldItineraryData) {
    //   if (newItineraryData === null) {
    //     this.itineraryData = null;
    //     this.fetchItineraryData();
    //   }
    // },
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
      itineraryId: '',
      itineraryParams: {},
    };
  },
  mounted() {
    this.itineraryId = this.getActiveItineraryId();
    this.fetchItineraryData();
  },
  methods: {
    fetchItineraryData() {
      const itineraryParams = this.getItineraryParams();
      const urlParams = new URLSearchParams(window.location.search);
      urlParams.forEach((value, key) => {
        itineraryParams[key] = value;
      });
      let url = '/itinerary';
      if (this.itineraryId) {
        url += '/' + this.itineraryId;
      }
      this.$axiosHelper.get(url, {
        params: itineraryParams,
      }).then((response) => {
        this.itineraryData = response.data;
        this.preferencesStore.setAttributesFromItineraryData(this.itineraryData);
        this.$cookies.set('active_itinerary_id', this.itineraryData.id);
        this.router.push('/' + this.itineraryData.id);
      }).catch((error) => {
        // Redirect to / if an invalid id was passed.
        if (error.status === 422) {
          window.location.href = '/';
        }
        if (error.code !== 'ERR_CANCELED') {
          console.log('Error fetching itinerary:', error);
          this.$cookies.remove('active_itinerary_id');
        }
      });
    },
    getActiveItineraryId() {
      if (this.$route.params.itineraryId) {
        return this.$route.params.itineraryId;
      }
      const activeItineraryId = this.$cookies.get('active_itinerary_id');
      if (typeof(activeItineraryId) !== 'undefined') {
        return activeItineraryId;
      }
      return null;
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
        preferred_activity_types: this.preferencesStore.preferredActivityTypes,
        preferred_months: this.preferencesStore.preferredMonths,
        duration_days: this.preferencesStore.durationDays,
      };
    },
    round(num) {
      return Math.round(num);
    },
  },
  components: {
    Day,
    Loading,
  },
};
</script>