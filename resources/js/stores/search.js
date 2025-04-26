import { defineStore } from 'pinia'

export const useSearchStore = defineStore('search', {
  state: () => ({
    isSearchOpen: false,
    query: '',
    selectedAreaIds: [],
    selectedResourceTables: [],
  }),
  actions: {
  }
})
