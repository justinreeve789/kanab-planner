import { defineStore } from 'pinia'

export const useSearchStore = defineStore('search', {
  state: () => ({
    isSearchOpen: false,
    // The swap-an-activity picker (SwapActivityModal).
    isResourceListOpen: false,
    title: '',
    target: {},
    sortField: '',
    query: '',
    modal: false,
    highlightSearchInMenu: false,
    closeAfterAddToItinerary: false,
    selectedAreaIds: [],
    selectedResourceTables: [],
    showCloseButtonOnMobile: false,
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
      this.highlightSearchInMenu = true;
      this.title = 'Search';
      this.selectedResourceTables = ['events', 'locations', 'restaurants', 'trails'];
      this.selectedAreaIds = [1, 2, 3, 4, 5, 6, 7];
    },
    adjustSettingsForResourceListForTable(resourceTable) {
      this.clearSettings();
      this.title = resourceTable.charAt(0).toUpperCase() + resourceTable.slice(1);
      this.showCloseButtonOnMobile = true;
      this.selectedResourceTables = [resourceTable];
      this.showResourceSelect = false;
      this.sortField = 'name';
    },
    adjustSettingsForPage(resourceTable) {
      this.clearSettings();
      this.title = resourceTable.charAt(0).toUpperCase() + resourceTable.slice(1);
      this.modal = false;
      this.showCloseButtonOnMobile = true;
      this.selectedResourceTables = [resourceTable];
      this.showResourceSelect = false;
      this.showFeatured = true;
      this.sortField = 'name';
    },
    clearSettings() {
      this.isSearchOpen = false;
      this.title = '';
      this.showCloseButtonOnMobile = false;
      this.target = {};
      this.sortField = '';
      this.query = '';
      this.modal = false;
      this.selectedAreaIds = [];
      this.selectedResourceTables = [];
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
