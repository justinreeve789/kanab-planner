<template>
  <div class="search">
    <div class="search-box">
    </div>
    <div class="filters">
      <AreaSelect @update:selected="handleSelectedAreas" />

      <div class="mt-4">
        <h2 class="text-lg font-bold mb-2">Selected Area IDs:</h2>
        <p>{{ selectedAreaIds.join(', ') }}</p>
      </div>

      <ResourceSelect @update:selected="handleSelectedResources" />

      <div class="mt-4">
        <h2 class="text-lg font-bold mb-2">Selected Resource Tables:</h2>
        <p>{{ selectedResourceTables.join(', ') }}</p>
      </div>
    </div>
    <div class="results">
      <div class="result-item">
        <AccommodationResult />
        <EventResult />
        <LocationResult />
        <RestaurantResult />
        <TrailResult />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import {ref, watch} from "vue";
import AccommodationResult from "./AccommodationResult.vue";
import EventResult from "./EventResult.vue";
import LocationResult from "./LocationResult.vue";
import RestaurantResult from "./RestaurantResult.vue";
import TrailResult from "./TrailResult.vue";
import AreaSelect from "./AreaSelect.vue";
import ResourceSelect from "./ResourceSelect.vue";

export default {
  components: {
    AreaSelect,
    ResourceSelect,
    AccommodationResult,
    EventResult,
    LocationResult,
    RestaurantResult,
    TrailResult,
  },
  setup() {
  },
  mounted() {
  },
  data() {
    return {
      selectedAreaIds: [1,2,3,4,5,6,7],
      selectedResourceTables: [],
      query: '',
    };
  },
  methods: {
    query() {
      axios
        .get('/api/v1/search', {
          q: this.query,
        })
        .then((response) => {
        })
        .catch((error) => {
          console.error('Error fetching assistant response:', error);
        });
    },
    handleSelectedAreas(newSelectedIds) {
      this.selectedAreaIds = newSelectedIds;
    },
    handleSelectedResources(newSelectedTables) {
      this.selectedResourceTables = newSelectedTables;
    },
  },
}
</script>

