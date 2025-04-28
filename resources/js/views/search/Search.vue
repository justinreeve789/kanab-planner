<template>
  <div class="search">
    <div class="search-box">
      <label class="input bg-white text-black">
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
        <input type="search" class="bg-white w-full" placeholder="Search" v-model="query" v-if="showInput" />
        <span class="loading loading-spinner loading-sm absolute right-8" v-if="loading"></span>
      </label>
    </div>
    <div class="filters">
      <AreaSelect @update:selected="handleSelectedAreas" :selectedAreaIds="selectedAreaIds" v-if="showAreaSelect" />
      <ResourceSelect @update:selected="handleSelectedResources" :selectedResourceTables="selectedResourceTables" v-if="showResourceSelect" />
    </div>
    <div class="results">
      <div class="featured-results">
      </div>
      <div class="standard-results">
        <div class="result-item -mx-6" v-for="resource in results" :key="result_id">
<!--          <AccommodationResult v-if="resource.type === 'accommodation' && selectedResourceTables.includes('accommodations')" />-->
          <EventResult v-if="resource.type === 'event' && selectedResourceTables.includes('events')" :resource="resource" />
          <LocationResult v-if="resource.type === 'location' && selectedResourceTables.includes('locations')" :resource="resource" />
          <RestaurantResult v-if="resource.type === 'restaurant' && selectedResourceTables.includes('restaurants')" :resource="resource" />
          <TrailResult v-if="resource.type === 'trail' && selectedResourceTables.includes('trails')" :resource="resource" />
        </div>
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
import {mapState} from "pinia";

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
  computed: {
    ...mapState(useSearchStore, [
      'title',
      'isSearchOpen',
      'query',
      'selectedAreaIds',
      'selectedResourceTables',
      'showHeader',
      'showInput',
      'showFilters',
      'showFeatured',
      'showAreaSelect',
      'showResourceSelect',
      'disabledResources',
      'hiddenResources',
    ]),
  },
  data() {
    return {
      loading: false,
      results: [],
    };
  },
  watch: {
    query(newQuery) {
      if (newQuery.trim() !== '') {
        this.debouncedQueryChange();
      }
    },
    selectedAreaIds(newAreaIds) {
      this.debouncedQueryChange();
    },
    selectedResourceTables(newResourceTables) {
      this.debouncedQueryChange();
    },
  },
  mounted() {
    // this.selectedAreaIds = this.searchStore.selectedAreaIds;
    // this.selectedResourceTables = this.searchStore.selectedResourceTables;
  },
  methods: {
    debouncedQueryChange() {
      if (this.debounceTimeout) {
        clearTimeout(this.debounceTimeout);
      }
      this.debounceTimeout = setTimeout(() => {
        this.executeQuery();
      }, 300);
    },
    executeQuery() {
      this.loading = true,
      axios
        .get('/api/v1/search/', {
          params: {
            q: this.query,
            ar: this.selectedAreaIds,
            rt: this.selectedResourceTables,
          },
        })
        .then((response) => {
          this.results = response.data;
          this.loading = false;
        })
        .catch((error) => {
          console.error('Error fetching assistant response:', error);
          this.loading = false;
        });
    },
    handleSelectedAreas(newSelectedIds) {
      this.selectedAreaIds = newSelectedIds;
      // this.searchStore.selectedAreaIds = newSelectedIds;
    },
    handleSelectedResources(newSelectedTables) {
      this.selectedResourceTables = newSelectedTables;
      // this.searchStore.selectedResourceTables = newSelectedTables;
    },
  },
}
</script>

