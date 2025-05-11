<template>
  <label class="mb-2 sm:mb-0 sm:mr-2">
    <span class="block w-full sm:w-auto text-white bg-gradient-to-r from-orange-400 via-orange-500 to-orange-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-orange-300 dark:focus:ring-orange-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
      @click="openMap">
      View map
    </span>
  </label>
</template>

<script>
import axios from 'axios';
import {usePreferencesStore} from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
  },
  data() {
    return {
    };
  },
  methods: {
    createSpecializedItinerary() {
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
      },
  },
  props: {
    query: String,
  },
};
</script>
