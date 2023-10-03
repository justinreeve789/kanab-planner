<template>
  <div class="itinerary-stats flex justify-end space-x-1 pr-4 pt-4 align-right text-white" title="Estimated total driving distance" v-if="itineraryData && itineraryData.totalTravelDistance">
      <img class="icon mr-1" src="/images/icons/car.svg" alt="Distance">
      <span class="amount">
          <span class="distance-low">{{ round(itineraryData.totalTravelDistance * 0.85) }}</span>
          -
          <span class="distance-high">{{ round(itineraryData.totalTravelDistance * 1.15) }}</span>
      </span>
      <span class="measurement">mi</span>
  </div>

  <div v-if="itineraryData" v-for="(day, index) in itineraryData.days" :key="index" class="p-2 md:p-4">
      <Day :day="day" />
  </div>

  <div v-else class="progress w-full h-screen flex justify-center items-center text-6xl relative">
      <span class="text text-center text-6xl text-white font-bold relative">
        Loading
        <span class="loading loading-dots loading-lg text-white absolute ml-2 bottom-0"></span>
      </span>
  </div>

  <div v-if="itineraryData" v-for="(day, index) in itineraryData.days" :key="index">
    <ActivityStatsModal :day="day" v-if="day.stats" />
  </div>

  <ListModal :type="'trail'" />
</template>

<script>
import axios from 'axios';
import Day from './Day.vue';
import ListModal from './panels/ListModal.vue';
import Select from './components/Select.vue';

export default {
  data() {
    return {
      itineraryData: null,
    };
  },
  mounted() {
    this.fetchItineraryData();
  },
  methods: {
    round(num) {
        return Math.round(num);
    },
    fetchItineraryData() {
      const urlParams = new URLSearchParams(window.location.search);
      const params = {};
      urlParams.forEach((value, key) => {
        params[key] = value;
      });
      axios
        .get('/api/v1/itinerary', {
           params: params,
        })
        .then((response) => {
          this.itineraryData = response.data;
        })
        .catch((error) => {
          console.error('Error fetching itinerary data:', error);
        });
    }
  },
  components: {
    ActivityStatsModal,
    Day,
  },
};
</script>

<script setup>
import { onMounted } from 'vue'
import ActivityStatsModal from './panels/ActivityStatsModal.vue'
import Side from './panels/Side.vue'
</script>