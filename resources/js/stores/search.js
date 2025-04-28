import { defineStore } from 'pinia'

export const useSearchStore = defineStore('search', {
  state: () => ({
    isResourceListOpen: false,
    isSearchOpen: false,
    query: '',
    selectedAreaIds: [],
    selectedResourceTables: [],
  }),
  actions: {
  }
})
