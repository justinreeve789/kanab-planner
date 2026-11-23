<template>
  <div class="bg-stone-100 min-h-[calc(100vh-64px)] pb-28 md:pb-12 text-stone-800">
    <div class="container mx-auto max-w-3xl px-4 pt-6">
      <h1 class="text-2xl font-bold">My trips</h1>
      <p class="text-stone-600 text-sm mt-1">Itineraries saved to your account. Anything you create while signed in is saved here automatically.</p>

      <div v-if="!authStore.checked || loading" class="py-12 text-center"><span class="loading loading-dots loading-lg"></span></div>

      <div v-else-if="!authStore.user" class="alert mt-6">
        <span>Sign in to see the trips saved to your account.</span>
        <router-link class="btn btn-sm btn-primary" :to="{ name: 'login', query: { redirect: '/trips' } }">Sign in</router-link>
      </div>

      <ul v-else-if="trips.length" class="mt-6 space-y-3">
        <li v-for="trip in trips" :key="trip.id">
          <router-link :to="'/' + trip.id" class="flex items-center justify-between gap-4 rounded-2xl bg-white border border-stone-200 shadow-sm p-4 hover:border-orange-300">
            <div class="min-w-0">
              <p class="font-semibold truncate">{{ trip.name || trip.duration_days + '-day trip' }}</p>
              <p class="text-sm text-stone-500">{{ formatDateRange(trip.start_date, trip.end_date) }}</p>
            </div>
            <span class="text-stone-400" aria-hidden="true">→</span>
          </router-link>
        </li>
      </ul>

      <div v-else class="mt-6 rounded-2xl bg-white border border-stone-200 p-6 text-center">
        <p class="text-stone-600">No saved trips yet.</p>
        <router-link class="btn btn-sm btn-primary mt-3" to="/questionnaire">Plan a trip</router-link>
      </div>
    </div>
  </div>
</template>

<script>
import { formatDateRange } from '../../lib/time';
import { useAuthStore } from '../../stores/auth';
import { useItineraryStore } from '../../stores/itinerary';

export default {
  setup() {
    return { authStore: useAuthStore(), itineraryStore: useItineraryStore() };
  },
  data() {
    return { loading: false };
  },
  computed: {
    trips() {
      return this.itineraryStore.savedItineraries;
    },
  },
  watch: {
    'authStore.user': {
      immediate: true,
      handler(user) {
        if (user) {
          this.load();
        }
      },
    },
  },
  methods: {
    formatDateRange,
    async load() {
      this.loading = true;
      try {
        await this.itineraryStore.fetchSaved();
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
