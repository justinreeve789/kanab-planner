import { defineStore } from 'pinia'

export const usePreferencesStore = defineStore('preferences', {
  state: () => ({
    itineraryData: null,
    refresh: false,
    adventurousness: 0.5,
    solitariness: 0.5,
    strenuousness: 0.5,
    isEditing: false,
    isLoadingItinerary: false,
    isPreferencesOpen: false,
    currentPreferencesStep: 0,
    preferredActivityTypes: [],
    durationDays: 7,
    preferredMonths: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
    earliestDayTime: '07:00',
    averageHikingSpeed: 2.0,
    dateRange: null,
    areaIds: null,
    eventIds: [],
    locationIds: [],
    restaurantIds: [],
    trailIds: [],
  }),
  actions: {
    addPreferredResource(resource) {
      if (resource.type === 'event') {
        if (!this.eventIds.includes(resource.id)) {
          this.eventIds.push(resource.id);
        }
      } else if (resource.type === 'location') {
        if (!this.locationIds.includes(resource.id)) {
          this.locationIds.push(resource.id);
        }
      } else if (resource.type === 'restaurant') {
        if (!this.restaurantIds.includes(resource.id)) {
          this.restaurantIds.push(resource.id);
        }
      } else if (resource.type === 'trail') {
        if (!this.trailIds.includes(resource.id)) {
          this.trailIds.push(resource.id);
        }
      }
    },
    getAdventurousnessPercent() {
      return Math.round(this.adventurousness * 100);
    },
    getSolitarinessPercent() {
      return Math.round(this.solitariness * 100);
    },
    getStrenuousnessPercent() {
      return Math.round(this.strenuousness * 100);
    },
    getRandomPreferredMonth() {
      if (this.preferredMonths.length > 0) {
        const index = Math.floor(Math.random() * this.preferredMonths);
        return this.preferredMonths[index];
      }
    },
    hasResource(resourceType, resourceId) {
      if (resourceType === 'event') {
        return this.eventIds.includes(resourceId);
      } else if (resourceType === 'location') {
        return this.locationIds.includes(resourceId);
      } else if (resourceType === 'restaurant') {
        return this.restaurantIds.includes(resourceId);
      } else if (resourceType === 'location') {
        return this.trailIds.includes(resourceId);
      }
      return false;
    },
    hasEventsInPreferredActivities() {
      for (let i = 0; i < this.preferredActivityTypes.length; i++) {
        const activityType = this.preferredActivityTypes[i];
        if (activityType === 'educational-and-fun' ||
          activityType === 'festivals-and-celebrations' ||
          activityType === 'museum' ||
          activityType === 'stargazing' ||
          activityType === 'night-life') {
          return true;
        }
      }
      return false;
    },
    hasTrailsInPreferredActivities() {
      for (let i = 0; i < this.preferredActivityTypes.length; i++) {
        const activityType = this.preferredActivityTypes[i];
        if (activityType === 'bicycling' ||
          activityType === 'hiking' ||
          activityType === 'offroading' ||
          activityType === 'rock-climbing') {
          return true;
        }
      }
      return false;
    },
    refreshItinerary() {
      this.itineraryData = null;
    },
    setAttributesFromItineraryData(itineraryData) {
      if (itineraryData.adventurousness) {
        this.adventurousness = itineraryData.adventurousness;
      }
      if (itineraryData.solitariness) {
        this.solitariness = itineraryData.solitariness;
      }
      if (itineraryData.strenuousness) {
        this.strenuousness = itineraryData.strenuousness;
      }
      if (itineraryData.start_date && itineraryData.end_date) {
        this.dateRange = {
          start: itineraryData.start_date,
          end: itineraryData.end_date,
        }
      }
      if (itineraryData.average_hiking_speed) {
        this.averageHikingSpeed = itineraryData.average_hiking_speed;
      }
      if (itineraryData.earliest_day_time) {
        this.earliestDayTime = itineraryData.earliest_day_time;
      }
    },
  },
})
