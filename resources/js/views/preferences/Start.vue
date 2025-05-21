<template>
  <div class="hero-overlay"></div>
  <div class="hero-content px-8 text-neutral-content text-center">
    <div class="max-w-md">
      <h1 class="mb-8 text-5xl font-bold">Ready to Plan Your Adventure?</h1>
      <p class="mb-8">
        Dreaming of exploring the breathtaking landscapes and unique experiences that Kanab has to offer? We're excited to help you plan the perfect visit!
      </p>
      <div class="flex items-center justify-center">
        <router-link class="block min-w-[128px] w-full sm:w-auto text-white bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br font-medium rounded-lg text-md px-5 py-2.5 text-center"
                :to="{ name: 'questionnaire-dates', props: { step: 1 } }">
          Let's Get Started!
        </router-link>
      </div>
      <p class="text-center text-sm text-white mt-8">
        <a class="cursor-pointer underline" @click="closePreferences()">No thanks, just create a random itinerary with basic settings</a>
      </p>
    </div>
  </div>
</template>

<script>
import { usePreferencesStore } from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return {
      preferencesStore,
    };
  },
  computed: {
    step() {
      return this.preferencesStore.currentPreferencesStep;
    },
  },
  methods: {
    closePreferences() {
      this.preferencesStore.refresh = true;
      this.$router.push('/');
    },
  },
  mounted() {
  },
};
</script>