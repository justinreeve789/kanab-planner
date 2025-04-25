<template>
  <div class="dock dock-md bg-black text-white md:hidden">
    <button :class="{ 'dock-active': isItineraryOpen() }" @click="openItinerary">
      <img src="/images/icons/itinerary.png" class="h-8 text-white" />
      <span class="dock-label">Itinerary</span>
    </button>

    <button :class="{ 'dock-active': isAssistantOpen() }" @click="openAssistant">
      <img src="/images/icons/assistant.svg" class="h-8 text-white" />
      <span class="dock-label">Assistant</span>
    </button>

    <button :class="{ 'dock-active': isPreferencesOpen() }" @click="openPreferences">
      <svg class="h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor" stroke-linejoin="miter" stroke-linecap="butt"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></circle><path d="m22,13.25v-2.5l-2.318-.966c-.167-.581-.395-1.135-.682-1.654l.954-2.318-1.768-1.768-2.318.954c-.518-.287-1.073-.515-1.654-.682l-.966-2.318h-2.5l-.966,2.318c-.581.167-1.135.395-1.654.682l-2.318-.954-1.768,1.768.954,2.318c-.287.518-.515,1.073-.682,1.654l-2.318.966v2.5l2.318.966c.167.581.395,1.135.682,1.654l-.954,2.318,1.768,1.768,2.318-.954c.518.287,1.073.515,1.654.682l.966,2.318h2.5l.966-2.318c.581-.167,1.135-.395,1.654-.682l2.318.954,1.768-1.768-.954-2.318c.287-.518.515-1.073.682-1.654l2.318-.966Z" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></path></g></svg>
      <span class="dock-label">Preferences</span>
    </button>
  </div>
</template>

<script>
import {useAssistantStore} from '../../stores/assistant';
import {usePreferencesStore} from "../../stores/preferences";

export default {
  emits: ['openAssistant'],
  setup() {
    const assistantStore = useAssistantStore();
    const preferencesStore = usePreferencesStore();
    return { assistantStore, preferencesStore };
  },
  data() {
    return {
    };
  },
  methods: {
    isItineraryOpen() {
      return !this.assistantStore.isAssistantOpen && !this.preferencesStore.isPreferencesOpen;
    },
    isAssistantOpen() {
      return this.assistantStore.isAssistantOpen;
    },
    isPreferencesOpen() {
      return this.preferencesStore.isPreferencesOpen;
    },
    openItinerary() {
      this.assistantStore.isAssistantOpen = false;
      this.preferencesStore.isPreferencesOpen = false;
    },
    openAssistant() {
      this.assistantStore.isAssistantOpen = true;
      this.preferencesStore.isPreferencesOpen = false;
    },
    openPreferences() {
      this.preferencesStore.isPreferencesOpen = true;
      this.assistantStore.isAssistantOpen = false;
    },
    toggleAssistant() {
      this.assistantStore.isAssistantOpen = !this.assistantStore.isAssistantOpen;
    },
  },
  mounted() {
  },
  props: {
  },
};
</script>