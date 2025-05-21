<template>
  <div class="dock dock-md bg-black text-white md:hidden">
    <button :class="{ 'dock-active': isItineraryOpen() }" @click="openItinerary">
      <img src="/images/icons/itinerary.png" class="h-8 text-white" />
      <span class="dock-label">Itinerary</span>
    </button>

    <router-link :class="{ 'dock-active': isAssistantOpen() }" :to="{ name: 'assistant' }">
      <img src="/images/icons/assistant.svg" class="h-8 text-white" />
      <span class="dock-label">Assistant</span>
    </router-link>

    <router-link :class="{ 'dock-active': isSearchOpen() }" :to="{ name: 'search' }">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
      <span class="dock-label">Search</span>
    </router-link>

    <router-link :class="{ 'dock-active': isPreferencesOpen() }" :to="{ name: 'questionnaire' }">
      <svg class="h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor" stroke-linejoin="miter" stroke-linecap="butt"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></circle><path d="m22,13.25v-2.5l-2.318-.966c-.167-.581-.395-1.135-.682-1.654l.954-2.318-1.768-1.768-2.318.954c-.518-.287-1.073-.515-1.654-.682l-.966-2.318h-2.5l-.966,2.318c-.581.167-1.135.395-1.654.682l-2.318-.954-1.768,1.768.954,2.318c-.287.518-.515,1.073-.682,1.654l-2.318.966v2.5l2.318.966c.167.581.395,1.135.682,1.654l-.954,2.318,1.768,1.768,2.318-.954c.518.287,1.073.515,1.654.682l.966,2.318h2.5l.966-2.318c.581-.167,1.135-.395,1.654-.682l2.318.954,1.768-1.768-.954-2.318c.287-.518.515-1.073.682-1.654l2.318-.966Z" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></path></g></svg>
      <span class="dock-label">Preferences</span>
    </router-link>
  </div>
</template>

<script>
import {useAssistantStore} from '../../stores/assistant';
import {useAuthStore} from "../../stores/auth";
import {usePreferencesStore} from "../../stores/preferences";
import {useSearchStore} from "../../stores/search";

export default {
  emits: ['openAssistant'],
  setup() {
    const assistantStore = useAssistantStore();
    const authStore = useAuthStore();
    const preferencesStore = usePreferencesStore();
    const searchStore = useSearchStore();
    return { assistantStore, authStore, preferencesStore, searchStore };
  },
  data() {
    return {
    };
  },
  methods: {
    isItineraryOpen() {
      return this.$route.name === 'itinerary' || this.$route.name === 'itinerary-new';
    },
    isAssistantOpen() {
      return this.$route.name === 'assistant';
    },
    isPreferencesOpen() {
      return this.$route.path.includes('/questionnaire');
    },
    isSearchOpen() {
      return this.$route.path.includes('/search');
    },
    openItinerary() {
      if (this.$cookies.get('active_itinerary_id')) {
        this.$router.push('/' + this.$cookies.get('active_itinerary_id'));
      } else {
        this.$router.push('/');
      }
    },
    openAssistant() {
      this.assistantStore.isAssistantOpen = true;
      this.authStore.isLoginOpen = false;
      this.preferencesStore.isPreferencesOpen = false;
      this.searchStore.isSearchOpen = false;
    },
    toggleAssistant() {
      this.assistantStore.isAssistantOpen = !this.assistantStore.isAssistantOpen;
    },
  },
};
</script>