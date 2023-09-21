<template>
<div v-if="itineraryData" v-for="(day, index) in itineraryData.days" :key="index" class="p-4">
    <Day :day="day" />
</div>
<div v-else class="progress w-full h-screen flex justify-center items-center text-6xl">
    <span class="text text-center text-6xl text-white font-bold">Loading</span>
</div>
</template>

<script>
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import axios from 'axios';
import Day from './Day.vue';

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
    Day,
  },
};
</script>
