<template>
  <section class="mt-10 max-w-[640px] mx-auto px-4" aria-labelledby="home-base-heading">
    <p id="home-base-heading" class="font-semibold text-lg text-center">Where are you staying?</p>
    <p class="text-sm text-gray-500 text-center mt-1">Every day starts and ends here, so drive times are worked out from it.</p>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-4" role="radiogroup" aria-label="Home base">
      <button v-for="area in areas" :key="area.id" type="button" role="radio"
              :aria-checked="preferencesStore.baseAreaId === area.id"
              :disabled="closedDuringTrip(area)"
              class="home-base-option rounded-lg border px-3 py-2 text-left transition disabled:opacity-40 disabled:cursor-not-allowed"
              :class="preferencesStore.baseAreaId === area.id ? 'border-orange-500 bg-orange-50 ring-2 ring-orange-400' : 'border-gray-200 hover:border-orange-300'"
              @click="choose(area)">
        <span class="block text-sm font-medium">{{ area.name }}</span>
        <span v-if="closureLabel(area)" class="block text-xs text-gray-500">{{ closureLabel(area) }}</span>
      </button>
    </div>
    <p v-if="closedNotice" class="text-sm text-amber-700 mt-2" role="status">{{ closedNotice }}</p>

    <div class="mt-6">
      <p class="text-sm font-medium">Know exactly where? <span class="text-gray-500 font-normal">(optional)</span></p>
      <p class="text-xs text-gray-500">Tap the map where you're staying for more accurate drive times, and give it a name for your check-in.</p>
      <div class="h-56 mt-2 rounded-lg overflow-hidden border border-gray-200" v-if="baseArea">
        <l-map :zoom="11" :center="mapCenter" :use-global-leaflet="false" @click="pin">
          <l-tile-layer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" layer-type="base" name="Streets"
                        attribution="&copy; OpenStreetMap contributors" />
          <l-circle-marker v-if="hasPin" :lat-lng="[preferencesStore.lodgingLat, preferencesStore.lodgingLng]"
                           :radius="9" color="#ea580c" :fill-opacity="0.9" />
        </l-map>
      </div>
      <div class="flex flex-wrap items-end gap-2 mt-2">
        <label class="flex-1 min-w-[200px] text-sm">
          <span class="block text-xs text-gray-600">Name</span>
          <input id="lodging-name" v-model.trim="preferencesStore.lodgingName" maxlength="120" type="text"
                 placeholder="e.g. Zion Canyon Lodge" class="input input-bordered input-sm w-full" />
        </label>
        <button v-if="hasPin" type="button" class="btn btn-ghost btn-sm" @click="clearPin">Remove pin</button>
      </div>
    </div>
  </section>
</template>

<script>
import "leaflet/dist/leaflet.css";
import { LCircleMarker, LMap, LTileLayer } from "@vue-leaflet/vue-leaflet";
import axiosHelper from "../../axiosHelper";
import { closureLabel, isAreaClosedDuring } from "../../lib/areas";
import { DEFAULT_BASE_AREA_ID, usePreferencesStore } from "../../stores/preferences";

export default {
  name: 'HomeBase',
  components: { LCircleMarker, LMap, LTileLayer },
  setup() {
    return { preferencesStore: usePreferencesStore() };
  },
  data() {
    return { areas: [], closedNotice: null };
  },
  computed: {
    baseArea() {
      return this.areas.find((area) => area.id === this.preferencesStore.baseAreaId) ?? null;
    },
    hasPin() {
      return this.preferencesStore.lodgingLat !== null && this.preferencesStore.lodgingLng !== null;
    },
    mapCenter() {
      return this.hasPin
        ? [this.preferencesStore.lodgingLat, this.preferencesStore.lodgingLng]
        : [Number(this.baseArea.latitude), Number(this.baseArea.longitude)];
    },
    tripDates() {
      return this.preferencesStore.dateRange ?? null;
    },
  },
  watch: {
    // Picking dates in a closure moves you off a base that will be closed.
    tripDates() {
      this.moveOffClosedBase();
    },
  },
  async mounted() {
    try {
      const { data } = await axiosHelper.get('/area', { persistent: true });
      this.areas = data;
      this.moveOffClosedBase();
    } catch (error) {
      this.areas = [];
    }
  },
  methods: {
    closureLabel,
    closedDuringTrip(area) {
      return Boolean(this.tripDates?.start) && isAreaClosedDuring(area, this.tripDates.start, this.tripDates.end);
    },
    choose(area) {
      if (area.id !== this.preferencesStore.baseAreaId) {
        this.preferencesStore.setBaseArea(area.id);
      }
      this.closedNotice = null;
    },
    pin(event) {
      if (!event?.latlng) {
        return;
      }
      this.preferencesStore.lodgingLat = Math.round(event.latlng.lat * 1e6) / 1e6;
      this.preferencesStore.lodgingLng = Math.round(event.latlng.lng * 1e6) / 1e6;
    },
    clearPin() {
      this.preferencesStore.lodgingLat = null;
      this.preferencesStore.lodgingLng = null;
    },
    moveOffClosedBase() {
      if (this.baseArea && this.closedDuringTrip(this.baseArea)) {
        const fallback = this.areas.find((area) => area.id === DEFAULT_BASE_AREA_ID);
        this.closedNotice = `${this.baseArea.name} is closed on your dates, so we've switched you to ${fallback?.name ?? 'Kanab'}.`;
        this.preferencesStore.setBaseArea(DEFAULT_BASE_AREA_ID);
      }
    },
  },
};
</script>
