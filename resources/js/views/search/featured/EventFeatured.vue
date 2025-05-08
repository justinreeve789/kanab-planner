<template>
  <div class="bg-gray-100 mt-4 -mx-8" v-if="results">
    <div class="mx-auto px-8 py-4">
      <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <template v-for="(resource, index) in results">
          <div class="bg-white rounded-lg shadow-md overflow-hidden" v-if="index < 2">
            <img :src="'/image/event/1?size=medium'" alt="" class="w-full h-48 object-cover">
            <p class="text-xl text-white font-semibold p-3 text-center w-full" style="background-color: #00b3cc;">{{ resource.name }}</p>
            <div class="p-6 pt-3">
              <p class="font-semibold text-center">{{ eventDate(resource) }}</p>
              <p class="text-gray-600 text-sm mb-4 line-clamp-4">{{ resource.description }}</p>
              <div class="flex flex-wrap gap-2">
                <button class="flex-grow h-10">
                  <AskQuestion :resource="resource" :resource-table="'events'" />
                </button>
                <button class="flex-grow h-10">
                  <AddToItinerary :resource="resource" />
                </button>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow-md overflow-hidden xs:block md:hidden lg:block" v-else-if="index < 3">
            <img :src="'/image/event/1?size=medium'" alt="" class="w-full h-48 object-cover">
            <p class="text-xl text-white font-semibold p-3 text-center w-full" style="background-color: #00b3cc;">{{ resource.name }}</p>
            <div class="p-6 pt-3">
              <p class="font-semibold text-center">{{ eventDate(resource) }}</p>
              <p class="text-gray-600 text-sm mb-4 line-clamp-4">{{ resource.description }}</p>
              <div class="flex flex-wrap gap-2">
                <button class="flex-grow h-10">
                  <AskQuestion :resource="resource" :resource-table="'events'" />
                </button>
                <button class="flex-grow h-10">
                  <AddToItinerary :resource="resource" />
                </button>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow-md overflow-hidden hidden xl:block" v-else>
            <img :src="'/image/event/1?size=medium'" alt="" class="w-full h-48 object-cover">
            <p class="text-xl text-white font-semibold p-3 text-center w-full" style="background-color: #00b3cc;">{{ resource.name }}</p>
            <div class="p-6 pt-3">
              <p class="font-semibold text-center">{{ eventDate(resource) }}</p>
              <p class="text-gray-600 text-sm mb-4 line-clamp-4">{{ resource.description }}</p>
              <div class="flex flex-wrap gap-2">
                <button class="flex-grow h-10">
                  <AskQuestion :resource="resource" :resource-table="'events'" />
                </button>
                <button class="flex-grow h-10">
                  <AddToItinerary :resource="resource" />
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
import {usePreferencesStore} from "../../../stores/preferences";
import axios from "axios";
import ViewMap from "../../buttons/ViewMap.vue";
import AddToItinerary from "../../buttons/AddToItinerary.vue";
import AskQuestion from "../../buttons/AskQuestion.vue";
import { format } from 'date-fns';

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
    AskQuestion, AddToItinerary, ViewMap
  },
  data() {
    return {
      loading: false,
      results: null,
    };
  },
  props: {
    areaIds: Array,
  },
  watch: {
    areaIds(newVal) {
      this.debouncedQueryChange();
    },
  },
  mounted() {
    this.getFeatured();
  },
  methods: {
    debouncedQueryChange() {
      if (this.debounceTimeout) {
        clearTimeout(this.debounceTimeout);
      }
      this.debounceTimeout = setTimeout(() => {
        this.getFeatured();
      }, 300);
    },
    eventDate(resource) {
      const startDate = new Date(resource.start_date);
      const endDate = resource.end_date ? new Date(resource.end_date) : null;

      const startMonth = format(startDate, 'MMMM');
      const startDay = format(startDate, 'd');
      const startYear = format(startDate, 'yyyy');

      if (endDate) {
        const endMonth = format(endDate, 'MMMM');
        const endDay = format(endDate, 'd');
        const endYear = format(endDate, 'yyyy');

        if (startMonth !== endMonth || startDay !== endDay || startYear !== endYear) {
          if (startYear === endYear) {
            if (startMonth === endMonth) {
              return `${startMonth} ${startDay} - ${endDay}, ${startYear}`;
            } else {
              return `${startMonth} ${startDay} - ${endMonth} ${endDay}, ${startYear}`;
            }
          } else {
            return `${startMonth} ${startDay}, ${startYear} - ${endMonth} ${endDay}, ${endYear}`;
          }
          }
      }
      return `${startMonth} ${startDay}, ${startYear}`;
    },
    getFeatured() {
      this.loading = true;
      axios
        .get('/api/v1/featured/events', {
          params: {
            area_ids: this.areaIds,
          },
        })
        .then((response) => {
          this.results = response.data;
          this.loading = false;
        })
        .catch((error) => {
          console.error('Error fetching search response:', error);
          this.loading = false;
        });
    },
  },
};
</script>