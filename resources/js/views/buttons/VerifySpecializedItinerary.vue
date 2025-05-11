<template>
  <label class="mb-2 sm:mb-0 sm:mr-2">
    <span class="block min-w-[96px] text-center text-xs text-white font-medium rounded-lg px-5 py-2.5 min-w-[96px] md:min-w-[128px] md:text-sm"
      :class="'bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br'"
      @click="confirm">
      Yes
    </span>
  </label>
  <label class="mb-2 sm:mb-0 sm:mr-2">
    <span class="block min-w-[96px] text-center text-xs text-white font-medium rounded-lg px-5 py-2.5 min-w-[96px] md:min-w-[128px] md:text-sm"
      :class="'bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br'"
      @click="cancel">
      No
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
  computed: {
    shouldReplaceItinerary() {
      return this.replaceItinerary ?? false;
    }
  },
  data() {
    return {
    };
  },
  methods: {
    cancel() {
    },
    confirm() {
    },
    createSpecializedItinerary() {
      if (this.replaceItinerary) {
        axios
          .post('/api/v1/assistant/itinerary', {
            params: {
              query: this.query,
            },
          })
          .then((response) => {
          })
          .catch((error) => {
            console.error('Error creating itinerary:', error);
          });
      } else {
        axios
          .put('/api/v1/assistant/itinerary', {
            params: {
              query: this.query,
            },
          })
          .then((response) => {
          })
          .catch((error) => {
            console.error('Error updating itinerary:', error);
          });
      }
    }
  },
  props: {
    query: String,
    replaceItinerary: Boolean,
  },
};
</script>
