<template>
  <div class="add-to-itinerary mb-2 sm:mb-0 sm:mr-2">
    <button v-if="isSwap" class="btn btn-sm w-full sm:w-auto text-white border-0 bg-gradient-to-r from-orange-500 to-red-500"
            :disabled="isCurrent" @click="swap">
      {{ isCurrent ? 'Already in your day' : 'Use this instead' }}
    </button>

    <button v-else-if="isPreferences" class="btn btn-sm w-full sm:w-auto text-white border-0 bg-gradient-to-r from-indigo-400 to-indigo-600"
            :disabled="isWished" @click="wish">
      {{ isWished ? 'Added to your wish list' : 'Add to my trip' }}
    </button>

    <div v-else-if="editableDays.length" class="dropdown">
      <div tabindex="0" role="button" class="btn btn-sm w-full sm:w-auto text-white border-0 bg-gradient-to-r from-indigo-400 to-indigo-600">
        {{ added ? 'Added to Day ' + added : 'Add to itinerary' }}
      </div>
      <ul tabindex="0" class="dropdown-content menu menu-sm bg-base-100 rounded-box z-30 w-56 p-2 shadow text-stone-800 max-h-72 overflow-y-auto">
        <li class="menu-title">Add to which day?</li>
        <li v-for="day in editableDays" :key="day.id">
          <a @click="addToDay(day)">
            Day {{ day.day_num + 1 }} · {{ day.area.short_name }}
            <span v-if="day.area.id === resourceAreaId" class="badge badge-xs">nearby</span>
          </a>
        </li>
      </ul>
    </div>

    <button v-else class="btn btn-sm w-full sm:w-auto text-white border-0 bg-gradient-to-r from-indigo-400 to-indigo-600"
            :disabled="isWished" @click="wish">
      {{ isWished ? 'Saved for your next itinerary' : 'Add to itinerary' }}
    </button>
  </div>
</template>

<script>
import { usePreferencesStore } from '../../stores/preferences';
import { useSearchStore } from '../../stores/search';
import { useItineraryStore } from '../../stores/itinerary';

/** Default start for something added by hand, by kind. */
const DEFAULT_START = { trails: '09:00', restaurants: '12:00', events: '18:00', locations: '10:00', accommodations: '16:00' };

/**
 * One button, three jobs:
 * - inside the swap picker: replace the activity being swapped;
 * - in the questionnaire (or with no editable itinerary): add to the wish list sent with the next itinerary;
 * - otherwise: add to a chosen day of the current itinerary.
 */
export default {
  props: {
    resource: Object,
    resource_table: String,
  },
  setup() {
    return { itineraryStore: useItineraryStore(), preferencesStore: usePreferencesStore(), searchStore: useSearchStore() };
  },
  data() {
    return { added: null };
  },
  computed: {
    table() {
      return this.resource_table || this.resource?.resource_table || (this.resource?.type ? this.resource.type + 's' : null);
    },
    resourceAreaId() {
      return this.resource?.area_id ?? this.resource?.area?.id ?? null;
    },
    isSwap() {
      return this.searchStore.target?.component === 'swap';
    },
    isPreferences() {
      return this.searchStore.target?.component === 'preferences';
    },
    isCurrent() {
      const current = this.searchStore.target?.activity;
      return current && current.resource_table === this.table && current.resource_id === this.resource?.id;
    },
    isWished() {
      return this.preferencesStore.hasResource(this.resource?.type, this.resource?.id);
    },
    editableDays() {
      if (!this.itineraryStore.canEdit) {
        return [];
      }
      // Days in the resource's area first.
      return [...this.itineraryStore.days].sort((a, b) =>
        (b.area.id === this.resourceAreaId) - (a.area.id === this.resourceAreaId) || a.day_num - b.day_num);
    },
  },
  methods: {
    async swap() {
      const activity = this.searchStore.target.activity;
      await this.itineraryStore.updateActivity(activity, { resource_table: this.table, resource_id: this.resource.id });
      this.searchStore.isResourceListOpen = false;
      this.searchStore.target = {};
    },
    wish() {
      this.preferencesStore.addPreferredResource(this.resource);
    },
    async addToDay(day) {
      document.activeElement?.blur();
      await this.itineraryStore.addActivity(day, {
        resource_table: this.table,
        resource_id: this.resource.id,
        start_time: DEFAULT_START[this.table] ?? '10:00',
      });
      this.added = day.day_num + 1;
    },
  },
};
</script>
