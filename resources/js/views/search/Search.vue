<template>
  <div class="search">
    <div class="search-box">
      <label class="input">
        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <g
            stroke-linejoin="round"
            stroke-linecap="round"
            stroke-width="2.5"
            fill="none"
            stroke="currentColor"
          >
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.3-4.3"></path>
          </g>
        </svg>
        <input type="search" class="w-full" placeholder="Search" />
      </label>
    </div>
    <div class="filters">
      <AreaSelect @update:selected="handleSelectedAreas" />
      <ResourceSelect @update:selected="handleSelectedResources" />
    </div>
    <div class="results">
      <div class="result-item">
        <AccommodationResult v-if="selectedResourceTables.includes('accommodations')" />
        <EventResult v-if="selectedResourceTables.includes('events')" />
        <LocationResult v-if="selectedResourceTables.includes('locations')" />
        <RestaurantResult v-if="selectedResourceTables.includes('restaurants')" />
        <TrailResult v-if="selectedResourceTables.includes('trails')" />
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
import { useSearchStore } from "../../stores/search";

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
    const searchStore = useSearchStore();
    return { searchStore };
  },
  mounted() {
  },
  data() {
    return {
      query: '',
      selectedAreaIds: [1,2,3,4,5,6,7],
      selectedResourceTables: [],
    };
  },
  methods: {
    query() {
      axios
        .get('/api/v1/search', {
          q: this.query,
          ar: this.selectedAreaIds,
          rt: this.selectedResourceTables,
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

