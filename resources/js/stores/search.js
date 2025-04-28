import { defineStore } from 'pinia'

export const useSearchStore = defineStore('search', {
  state: () => ({
    isSearchOpen: false,
    title: 'Search',
    query: '',
    selectedAreaIds: [1, 3, 4, 5, 7],
    selectedResourceTables: ['events', 'locations', 'restaurants', 'trails'],
    showHeader: true,
    showInput: true,
    showFilters: true,
    showFeatured: false,
    showAreaSelect: true,
    showResourceSelect: true,
    // We may want to disable resources that have already been selected in the itinerary.
    disabledResources: [],
    // We may want to hide resources that have already been selected in the itinerary.
    hiddenResources: [],
  }),
  actions: {
    adjustSettingsForGeneralSearch() {
      this.clearSettings();
    },
    adjustSettingsForResourceListForTable(resourceTable) {
      this.clearSettings();
      this.selectedResourceTables = [resourceTable];
      this.showResourceSelect = false;
    },
    clearSettings() {
      this.isSearchOpen = false;
      this.title = 'Search';
      this.query = '';
      this.selectedAreaIds = [1, 3, 4, 5, 7];
      this.selectedResourceTables = ['events', 'locations', 'restaurants', 'trails'];
      this.showHeader = true;
      this.showInput = true;
      this.showFilters = true;
      this.showFeatured = false;
      this.showAreaSelect = true;
      this.showResourceSelect = true;
      this.disabledResources = [];
      this.hiddenResources = [];
    },
  }
})
