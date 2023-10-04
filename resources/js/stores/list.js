import { defineStore } from 'pinia'

export const useListStore = defineStore('list', {
    state: () => ({
        resource: null,
    }),

    actions: {
        setResource(resource) {
            this.resource = resource;
        },
    },
})