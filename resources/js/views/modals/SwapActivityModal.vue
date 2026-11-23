<template>
  <div v-if="searchStore.isResourceListOpen" class="modal modal-open" role="dialog" aria-modal="true" :aria-label="searchStore.title">
    <div class="modal-box w-11/12 max-w-2xl max-h-[85vh] p-0 flex flex-col bg-white text-stone-900">
      <div class="flex justify-between items-center bg-gradient-to-r from-orange-100 to-red-100 px-4 py-3">
        <div>
          <h2 class="text-lg font-semibold">{{ searchStore.title }}</h2>
          <p class="text-xs text-stone-600">{{ subtitle }}</p>
        </div>
        <button class="btn btn-sm btn-ghost" aria-label="Close" @click="close">✕</button>
      </div>

      <div class="px-4 pt-3">
        <label class="input input-sm w-full bg-white">
          <span class="sr-only">Filter by name</span>
          <input v-model="filter" type="search" placeholder="Filter by name" class="w-full" />
        </label>
      </div>

      <div class="flex-1 overflow-y-auto p-4">
        <div v-if="loading" class="py-10 text-center"><span class="loading loading-dots loading-md"></span></div>
        <p v-else-if="!options.length" class="py-10 text-center text-stone-500">Nothing else to choose from here.</p>
        <ul v-else class="space-y-2">
          <li v-for="option in options" :key="option.id"
              class="flex items-center justify-between gap-3 rounded-xl border border-stone-200 p-3">
            <div class="min-w-0">
              <p class="font-semibold truncate">{{ option.name }}</p>
              <p class="text-sm text-stone-500">{{ describe(option) }}</p>
            </div>
            <span v-if="option.id === current?.resource_id" class="badge badge-ghost shrink-0">Current</span>
            <span v-else-if="inTrip(option)" class="badge badge-ghost shrink-0">In your trip</span>
            <button v-else class="btn btn-sm shrink-0 text-white border-0 bg-gradient-to-r from-orange-500 to-red-500"
                    :disabled="saving" @click="choose(option)">
              Use this instead
            </button>
          </li>
        </ul>
      </div>
    </div>
    <div class="modal-backdrop" @click="close"></div>
  </div>
</template>

<script>
import axiosHelper from '../../axiosHelper';
import { useItineraryStore } from '../../stores/itinerary';
import { useSearchStore } from '../../stores/search';

const ENDPOINTS = { trails: 'trail', restaurants: 'restaurant', locations: 'location', events: 'event' };

/**
 * Pick another trail/restaurant/place/event in the same area to replace an itinerary activity. Opened by Day.vue
 * with searchStore.target = { component: 'swap', activity, areaId, date }.
 */
export default {
  setup() {
    return { itineraryStore: useItineraryStore(), searchStore: useSearchStore() };
  },
  data() {
    return { all: [], filter: '', loading: false, saving: false };
  },
  computed: {
    current() {
      return this.searchStore.target?.activity ?? null;
    },
    table() {
      return this.current?.resource_table;
    },
    subtitle() {
      return 'Same area, same start time.';
    },
    options() {
      const needle = this.filter.trim().toLowerCase();
      return this.all.filter((o) => !needle || o.name.toLowerCase().includes(needle));
    },
    usedIds() {
      const ids = new Set();
      for (const day of this.itineraryStore.days) {
        for (const period of day.periods ?? []) {
          for (const activity of period.activities ?? []) {
            if (activity.resource_table === this.table) {
              ids.add(activity.resource_id);
            }
          }
        }
      }
      return ids;
    },
  },
  watch: {
    'searchStore.isResourceListOpen'(open) {
      if (open) {
        this.load();
      }
    },
  },
  methods: {
    async load() {
      const target = this.searchStore.target;
      const endpoint = ENDPOINTS[this.table];
      this.all = [];
      this.filter = '';
      if (!endpoint || !target?.areaId) {
        return;
      }
      this.loading = true;
      try {
        const params = this.table === 'events' ? { date: target.date } : {};
        const { data } = await axiosHelper.get(`/area/${target.areaId}/${endpoint}`, { params });
        this.all = (Array.isArray(data) ? data : data.data ?? []).sort((a, b) => a.name.localeCompare(b.name));
      } finally {
        this.loading = false;
      }
    },
    inTrip(option) {
      return this.usedIds.has(option.id);
    },
    describe(option) {
      if (this.table === 'trails') {
        return [option.distance_miles && `${Number(option.distance_miles)} mi`, option.difficulty,
          option.average_hours && `about ${Number(option.average_hours)} h`].filter(Boolean).join(' · ');
      }
      if (this.table === 'restaurants') {
        return option.average_price ? `About $${option.average_price} per person` : '';
      }
      return option.address ?? option.location?.name ?? '';
    },
    async choose(option) {
      this.saving = true;
      try {
        await this.itineraryStore.updateActivity(this.current, { resource_table: this.table, resource_id: option.id });
        this.close();
      } finally {
        this.saving = false;
      }
    },
    close() {
      this.searchStore.isResourceListOpen = false;
      this.searchStore.target = {};
    },
  },
};
</script>
