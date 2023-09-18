<template>
  <div>
    <div v-if="itineraryData">
      <div v-for="(day, index) in itineraryData.days" :key="index">
        <Day :day="day" />
      </div>
    </div>
    <div v-else>
      Loading itinerary data...
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Day from './Day.vue'; // Import the Day component

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
      axios
        .get('/api/v1/itinerary?start_date=2023-11-15&duration_days=15') // Replace with your API endpoint
        .then((response) => {
          this.itineraryData = response.data;
        })
        .catch((error) => {
          console.error('Error fetching itinerary data:', error);
        });
    },
  },
  components: {
    Day, // Register the Day component
  },
};
</script>
