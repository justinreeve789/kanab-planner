<template>
  <div class="dock dock-md bg-black text-white md:hidden">
    <button @click="closeAssistant">
      <img src="/images/icons/itinerary.png" class="h-8 text-white" />
      <span class="dock-label">Itinerary</span>
    </button>

    <button @click="openAssistant">
      <img src="/images/icons/assistant.svg" class="h-8 text-white" />
      <span class="dock-label">Assistant</span>
    </button>

    <button>
      <svg class="h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g fill="currentColor" stroke-linejoin="miter" stroke-linecap="butt"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></circle><path d="m22,13.25v-2.5l-2.318-.966c-.167-.581-.395-1.135-.682-1.654l.954-2.318-1.768-1.768-2.318.954c-.518-.287-1.073-.515-1.654-.682l-.966-2.318h-2.5l-.966,2.318c-.581.167-1.135.395-1.654.682l-2.318-.954-1.768,1.768.954,2.318c-.287.518-.515,1.073-.682,1.654l-2.318.966v2.5l2.318.966c.167.581.395,1.135.682,1.654l-.954,2.318,1.768,1.768,2.318-.954c.518.287,1.073.515,1.654.682l.966,2.318h2.5l.966-2.318c.581-.167,1.135-.395,1.654-.682l2.318.954,1.768-1.768-.954-2.318c.287-.518.515-1.073.682-1.654l2.318-.966Z" fill="none" stroke="currentColor" stroke-linecap="square" stroke-miterlimit="10" stroke-width="2"></path></g></svg>
      <span class="dock-label">Settings</span>
    </button>
  </div>
</template>

<script>
import {useAssistantStore} from '../../stores/assistant.js';

export default {
  emits: ['openAssistant'],
  setup() {
    const store = useAssistantStore();
    return { store };
  },
  data() {
    return {
    };
  },
  methods: {
    closeAssistant() {
      this.store.isAssistantOpen = false;
    },
    openAssistant() {
      this.store.isAssistantOpen = true;
    },
    toggleAssistant() {
      this.store.isAssistantOpen = !this.store.isAssistantOpen;
    },
  },
  mounted() {
  },
  props: {
  },
};
</script>