<template>
  <label class="mb-2 sm:mb-0 sm:mr-2">
    <span class="block w-full sm:w-auto text-white bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
      @click="addToItinerary">
      Add to itinerary
    </span>
  </label>
</template>

<script>
import axios from 'axios';
import {usePreferencesStore} from "../../stores/preferences";
import {useSearchStore} from "../../stores/search";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    const searchStore = useSearchStore();
    return { preferencesStore, searchStore };
  },
  components: {
  },
  data() {
    return {
    };
  },
  methods: {
    addToItinerary() {
      if (this.searchStore.target) {
        const component = this.searchStore.target.component ?? null;
        const store = this.searchStore.target.store ?? null;
        const attribute = this.searchStore.target.attribute ?? null;
        if (component === 'preferences' && store === 'preferences') {
          this.preferencesStore.addPreferredResource(this.resource);
        }
      } else {
        axios
          .put('/api/v1/itinerary', {
            params: {
              resource_table: this.resource.table ?? null,
              resource_id: this.resource.id ?? null,
            },
          })
          .then((response) => {
          })
          .catch((error) => {
            console.error('Error adding resource to itinerary:', error);
          });
      }
    },
  },
  props: {
    resource: Object,
  },
};
</script>
