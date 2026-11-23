<template>
  <section class="trip-summary pt-4 md:pt-6 pb-2">
    <div class="rounded-2xl bg-gradient-to-br from-orange-600 via-red-600 to-rose-700 text-white shadow-md overflow-hidden">
      <div class="p-4 md:p-6">
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <p class="text-xs uppercase tracking-widest text-orange-100">{{ dateRange }}</p>
            <h1 v-if="!isRenaming" class="text-2xl md:text-3xl font-bold leading-tight mt-1 break-words">
              {{ title }}
              <button v-if="itinerary.can_edit" class="btn btn-ghost btn-xs text-orange-100 align-middle" title="Rename trip" @click="startRename">
                <span class="sr-only">Rename trip</span>
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>
              </button>
            </h1>
            <form v-else class="flex gap-2 mt-1" @submit.prevent="saveName">
              <input ref="nameInput" v-model="name" class="input input-sm text-stone-900 w-full max-w-sm" maxlength="255" aria-label="Trip name" />
              <button class="btn btn-sm" type="submit">Save</button>
              <button class="btn btn-sm btn-ghost text-white" type="button" @click="isRenaming = false">Cancel</button>
            </form>
            <p class="text-sm text-orange-50 mt-1">Based in {{ baseAreaName }}</p>
          </div>
          <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            <button class="btn btn-sm btn-outline border-white text-white hover:bg-white hover:text-red-700 hover:border-white" @click="share">
              {{ copied ? 'Link copied' : 'Share' }}
            </button>
            <button class="btn btn-sm bg-white text-red-700 border-0 hover:bg-orange-50" @click="$emit('new-itinerary')">
              New itinerary
            </button>
          </div>
        </div>

        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 text-center">
          <div class="rounded-xl bg-white/10 py-2">
            <dt class="text-[11px] uppercase tracking-wide text-orange-100">Days</dt>
            <dd class="text-xl font-semibold">{{ itinerary.duration_days }}</dd>
          </div>
          <div class="rounded-xl bg-white/10 py-2">
            <dt class="text-[11px] uppercase tracking-wide text-orange-100">Hikes</dt>
            <dd class="text-xl font-semibold">{{ totals.hikes }}</dd>
          </div>
          <div class="rounded-xl bg-white/10 py-2">
            <dt class="text-[11px] uppercase tracking-wide text-orange-100">On the trail</dt>
            <dd class="text-xl font-semibold">{{ formatDuration(totals.hikeMinutes) || '—' }}</dd>
          </div>
          <div class="rounded-xl bg-white/10 py-2">
            <dt class="text-[11px] uppercase tracking-wide text-orange-100">Driving</dt>
            <dd class="text-xl font-semibold">{{ itinerary.total_travel_distance ? '~' + itinerary.total_travel_distance + ' mi' : '—' }}</dd>
          </div>
        </dl>
      </div>

      <div v-if="notice" class="bg-black/20 px-4 md:px-6 py-2 text-sm flex flex-wrap items-center gap-2">
        <span>{{ notice }}</span>
        <button v-if="canClaim" class="btn btn-xs bg-white text-red-700 border-0" @click="claim">Save to my account</button>
        <router-link v-if="!user && itinerary.can_edit" class="link text-white" :to="{ name: 'login' }">Sign in to keep it on every device</router-link>
      </div>
    </div>

    <div v-if="itineraryStore.error" role="alert" class="alert alert-warning mt-3 text-sm">
      <span>{{ itineraryStore.error }}</span>
      <button class="btn btn-xs btn-ghost" @click="itineraryStore.error = null">Dismiss</button>
    </div>
  </section>
</template>

<script>
import { formatDateRange, formatDuration } from '../../lib/time';
import { summarizeDay } from '../../lib/schedule';
import { useAuthStore } from '../../stores/auth';
import { useItineraryStore } from '../../stores/itinerary';

export default {
  emits: ['new-itinerary'],
  props: {
    itinerary: { type: Object, required: true },
  },
  setup() {
    return { authStore: useAuthStore(), itineraryStore: useItineraryStore() };
  },
  data() {
    return { copied: false, isRenaming: false, name: '' };
  },
  computed: {
    user() {
      return this.authStore.user;
    },
    baseAreaName() {
      return this.itinerary.days?.[0]?.area?.name ?? 'Kanab, Utah';
    },
    title() {
      return this.itinerary.name || `${this.itinerary.duration_days} days around ${this.itinerary.days?.[0]?.area?.short_name ?? 'Kanab'}`;
    },
    dateRange() {
      return formatDateRange(this.itinerary.start_date, this.itinerary.end_date);
    },
    totals() {
      return (this.itinerary.days ?? []).map(summarizeDay).reduce((sum, day) => ({
        hikes: sum.hikes + day.hikes,
        hikeMinutes: sum.hikeMinutes + day.hikeMinutes,
      }), { hikes: 0, hikeMinutes: 0 });
    },
    hasToken() {
      return Boolean(this.itineraryStore.tokenFor(this.itinerary.id));
    },
    canClaim() {
      return Boolean(this.user) && this.hasToken && !this.itinerary.owned;
    },
    notice() {
      if (!this.itinerary.can_edit) {
        return 'View only. You can change an itinerary from the browser that made it, or when you\'re signed in as its owner.';
      }
      if (this.itinerary.owned) {
        return null;
      }
      return 'You can edit this itinerary from this browser.';
    },
  },
  methods: {
    formatDuration,
    async share() {
      const url = window.location.origin + '/' + this.itinerary.id;
      try {
        if (navigator.share) {
          await navigator.share({ title: this.title, url });
          return;
        }
        await navigator.clipboard.writeText(url);
        this.copied = true;
        setTimeout(() => { this.copied = false; }, 2500);
      } catch (e) {
        window.prompt('Copy this link to share your itinerary:', url);
      }
    },
    startRename() {
      this.name = this.itinerary.name ?? '';
      this.isRenaming = true;
      this.$nextTick(() => this.$refs.nameInput?.focus());
    },
    async saveName() {
      await this.itineraryStore.rename(this.name.trim() || null);
      this.isRenaming = false;
    },
    async claim() {
      await this.itineraryStore.claim();
    },
  },
};
</script>
