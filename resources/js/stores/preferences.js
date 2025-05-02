import { defineStore } from 'pinia'

export const usePreferencesStore = defineStore('preferences', {
  state: () => ({
    itineraryData: null,
    refresh: false,
    adventurousness: 0.5,
    solitariness: 0.5,
    strenuousness: 0.5,
    isPreferencesOpen: false,
    dateRange: null,
    areaIds: null,
    eventIds: null,
    locationIds: null,
    restaurantIds: null,
    trailIds: null,
  }),
  actions: {
    getAdventurousnessPercent() {
      return Math.round(this.adventurousness * 100);
    },
    getSolitarinessPercent() {
      return Math.round(this.solitariness * 100);
    },
    getStrenuousnessPercent() {
      return Math.round(this.strenuousness * 100);
    },
    refreshItinerary() {
      this.itineraryData = null;
    },
  }
})
