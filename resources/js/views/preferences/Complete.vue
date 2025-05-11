<template>
  <div class="p-4 text-center text-lg">
    <div class="hero-content text-center">
      <div class="max-w-lg text-gray-600">
        <h1 class="font-bold mb-6 text-5xl">You're All Done!</h1>
        <p class="mb-6">
          Now we'll create an itinerary just for you. Feel free to return here to adjust your preferences if you want to change it. You may also make changes on the itinerary itself.
        </p>
        <div class="flex justify-center items-center">
          <button class="block w-full sm:w-auto text-white bg-gradient-to-r from-green-400 to-green-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-700 font-medium rounded-lg text-md px-5 py-2.5 text-center"
            @click="createItinerary">
            Create itinerary
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {usePreferencesStore} from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
  },
  props: {
  },
  data() {
    return {
    };
  },
  computed: {
  },
  methods: {
    createItinerary() {
      this.preferencesStore.refreshItinerary();
      this.preferencesStore.refresh = true;
      this.preferencesStore.isPreferencesOpen = false;
    },
  },
  mounted() {
  },
};
</script>
