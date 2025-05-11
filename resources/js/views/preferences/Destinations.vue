<template>
<!--  <StepsActivityTypes :show-events="hasEvents()" :show-trails="hasTrails()" />-->
  <div class="bg-white text-black text-center text-lg">
    <div class="p-4 text-center text-lg">
      <p class="font-semibold mb-2">Are there any specific destinations you wish to visit?</p>
      <p class="mb-1">You don't have to select any, but we'll make sure to add anything you choose to your itinerary, if possible.</p>
    </div>
    <div class="flex flex-wrap justify-center items-center gap-6 my-4">
      <button
        class="text-white text-md font-medium text-center bg-gradient-to-r from-orange-400 via-orange-500 to-orange-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-orange-300 dark:focus:ring-orange-800 rounded-lg px-5 py-2"
        @click="openTrails"
        v-if="hasTrails()">
        Find a trail
      </button>
      <button
        class="text-white text-md font-medium text-center bg-gradient-to-r from-cyan-400 to-cyan-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-cyan-300 dark:focus:ring-cyan-800 rounded-lg px-5 py-2"
        @click="openEvents"
        v-if="hasEvents()">
        Find an event
      </button>
      <button
        class="text-white text-md font-medium text-center bg-gradient-to-r from-cyan-400 to-cyan-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-cyan-300 dark:focus:ring-cyan-800 rounded-lg px-5 py-2"
        @click="openSearch"
        v-if="!hasTrails() && !hasEvents()">
        Find an activity
      </button>
    </div>
    <div v-if="hasTrails()">
      <TrailFeatured :area-ids="[1,2,3,4,5,6,7]" v-if="hasTrails()" />
    </div>
    <div v-if="hasEvents()">
      <EventFeatured :area-ids="[1,2,3,4,5,6,7]" v-if="hasEvents()" />
    </div>
  </div>
</template>

<script>
import EventFeatured from "../search/featured/EventFeatured.vue";
import StepsActivityTypes from "./StepsActivityTypes.vue";
import TrailFeatured from "../search/featured/TrailFeatured.vue";
import {usePreferencesStore} from "../../stores/preferences";
import {useSearchStore} from "../../stores/search";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    const searchStore = useSearchStore();
    return { preferencesStore, searchStore };
  },
  components: {
    EventFeatured,
    StepsActivityTypes,
    TrailFeatured,
  },
  props: {
    selectedActivityTypes: Array,
  },
  data() {
    return {
      featuredActivities: null,
      featuredEvents: null,
      featuredTrails: null,
      selectedResources: [],
    };
  },
  computed: {
    getSelectedActivityTypes() {
      return this.preferencesStore.preferredActivityTypes;
    },
  },
  methods: {
    hasActivityType(activityType) {
      if (this.preferencesStore.preferredActivityTypes) {
        return this.preferencesStore.preferredActivityTypes.includes(activityType);
      }
      return false;
    },
    hasEvents() {
      return this.preferencesStore.hasEventsInPreferredActivities();
    },
    hasTrails() {
      return this.preferencesStore.hasTrailsInPreferredActivities();
    },
    openEvents() {
      this.searchStore.adjustSettingsForResourceListForTable('events');
      this.searchStore.target = { component: 'preferences', store: 'preferences', attribute: 'selectedEventIds' };
      this.searchStore.closeAfterAddToItinerary = true;
      this.searchStore.isSearchOpen = true;
    },
    openSearch() {
      this.searchStore.adjustSettingsForGeneralSearch();
      this.searchStore.target = { component: 'preferences', store: 'preferences', attribute: 'selectedActivities' };
      this.searchStore.closeAfterAddToItinerary = true;
      this.searchStore.isSearchOpen = true;
    },
    openTrails() {
      this.searchStore.adjustSettingsForResourceListForTable('trails');
      this.searchStore.target = { component: 'preferences', store: 'preferences', attribute: 'selectedTrailIds' };
      this.searchStore.closeAfterAddToItinerary = true;
      this.searchStore.isSearchOpen = true;
    },
  },
  mounted() {
  },
};
</script>
