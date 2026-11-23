<template>
  <div class="itinerary bg-stone-100 min-h-[calc(100vh-64px)] pb-28 md:pb-12 text-stone-800">
    <div class="container mx-auto max-w-5xl px-2 sm:px-4">
      <template v-if="itinerary">
        <TripSummary :itinerary="itinerary" @new-itinerary="createItinerary" />

        <nav class="day-nav sticky top-16 z-30 -mx-2 sm:mx-0 mb-4 bg-stone-100/90 backdrop-blur py-2 px-2 sm:px-0 overflow-x-auto" aria-label="Days">
          <ul class="flex gap-2 min-w-max">
            <li v-for="day in itinerary.days" :key="day.id">
              <a :href="'#day-' + day.day_num"
                 class="day-chip flex flex-col items-center rounded-xl border border-stone-200 bg-white px-3 py-1.5 text-xs shadow-sm hover:border-orange-300 hover:bg-orange-50"
                 @click.prevent="scrollToDay(day.day_num)">
                <span class="font-semibold">Day {{ day.day_num + 1 }}</span>
                <span class="text-stone-500">{{ day.area.short_name }}</span>
              </a>
            </li>
          </ul>
        </nav>

        <div class="space-y-4">
          <Day v-for="day in itinerary.days" :key="day.id" :day="day" :home-name="homeName" :can-edit="itinerary.can_edit" />
        </div>
      </template>

      <div v-else-if="itineraryStore.error" class="pt-16 max-w-lg mx-auto">
        <div role="alert" class="alert alert-error">
          <span>{{ itineraryStore.error }}</span>
          <button class="btn btn-sm" @click="createItinerary">Try again</button>
        </div>
      </div>

      <div v-else>
        <Loading />
      </div>
    </div>
  </div>
</template>

<script>
import Day from './Day.vue';
import Loading from './components/Loading.vue';
import TripSummary from './components/TripSummary.vue';
import { useItineraryStore } from '../stores/itinerary';
import { usePreferencesStore } from '../stores/preferences';

export default {
  components: { Day, Loading, TripSummary },
  setup() {
    return { itineraryStore: useItineraryStore(), preferencesStore: usePreferencesStore() };
  },
  computed: {
    itinerary() {
      return this.itineraryStore.itinerary;
    },
    homeName() {
      return this.itineraryStore.homeName;
    },
  },
  watch: {
    'preferencesStore.refresh'(newVal, oldVal) {
      if (newVal === true && oldVal !== true) {
        this.preferencesStore.refresh = false;
        this.createItinerary();
      }
    },
    'preferencesStore.dateRange'(newRange, oldRange) {
      if (newRange && oldRange && (newRange.start !== oldRange.start || newRange.end !== oldRange.end)
        && this.itinerary && (newRange.start !== this.itinerary.start_date || newRange.end !== this.itinerary.end_date)) {
        this.createItinerary();
      }
    },
    '$route.params.itineraryId'(id) {
      if (id && id !== this.itinerary?.id) {
        this.loadItinerary(id);
      }
    },
  },
  mounted() {
    // "Create itinerary" in the questionnaire and the header's refresh button ask for a new one.
    if (this.preferencesStore.refresh) {
      this.preferencesStore.refresh = false;
      this.createItinerary();
      return;
    }
    const id = this.$route.params.itineraryId || this.$cookies.get('active_itinerary_id');
    if (id) {
      this.loadItinerary(id);
    } else {
      this.createItinerary();
    }
  },
  methods: {
    async loadItinerary(id) {
      try {
        const itinerary = await this.itineraryStore.load(id);
        this.afterLoad(itinerary);
      } catch (error) {
        if (error?.code === 'ERR_CANCELED') {
          return;
        }
        // Unknown or deleted itinerary: start a fresh one.
        this.$cookies.remove('active_itinerary_id');
        this.createItinerary();
      }
    },
    async createItinerary() {
      this.itineraryStore.itinerary = null;
      try {
        const itinerary = await this.itineraryStore.create(this.itineraryParams());
        this.afterLoad(itinerary);
      } catch (error) {
        console.error('Error creating itinerary:', error);
      }
    },
    afterLoad(itinerary) {
      this.preferencesStore.setAttributesFromItineraryData(itinerary);
      this.$cookies.set('active_itinerary_id', itinerary.id, '30d');
      if (this.$route.params.itineraryId !== itinerary.id) {
        this.$router.replace('/' + itinerary.id);
      }
    },
    itineraryParams() {
      const p = this.preferencesStore;
      const params = {
        adventurousness: p.adventurousness,
        solitariness: p.solitariness,
        strenuousness: p.strenuousness,
        start_date: p.dateRange?.start || null,
        end_date: p.dateRange?.end || null,
        duration_days: p.durationDays,
        average_hiking_speed: p.averageHikingSpeed,
        area_ids: p.areaIds,
        event_ids: p.eventIds,
        location_ids: p.locationIds,
        restaurant_ids: p.restaurantIds,
        trail_ids: p.trailIds,
        preferred_activity_types: p.preferredActivityTypes,
        preferred_months: p.dateRange ? [] : p.preferredMonths,
        ...p.baseParams(),
      };
      // Anything in the address bar (e.g. ?base_area_id=7) overrides the questionnaire.
      new URLSearchParams(window.location.search).forEach((value, key) => {
        params[key] = value;
      });
      return params;
    },
    scrollToDay(dayNum) {
      document.getElementById('day-' + dayNum)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
  },
};
</script>
