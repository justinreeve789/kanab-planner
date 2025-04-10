import { defineStore } from 'pinia';
import axios from 'axios';

export const useFeatureStore = defineStore('featureStore', {
  state: () => ({
    features: [],  // List of feature objects with 'enabled' property
    loading: false, // To track the loading state
    error: null, // To store any errors during the API call
  }),

  actions: {
    async fetchFeatures() {
      this.loading = true;
      try {
        const response = await axios.get('/api/v1/feature');
        this.features = response.data; // Assuming the API returns an array of feature objects
        console.log(this.features);
      } catch (error) {
        this.error = error;
      } finally {
        this.loading = false;
      }
    }
  },

  getters: {
    enabled: (state) => (feature) => {
      // Find the feature object by its name and check if it is enabled
      const featureObj = state.features.find(f => f.feature === feature);
      return featureObj ? featureObj.enabled : false; // Return true if enabled, false if not found
    }
  }
});
