<template>
  <div v-if="assistantStore.isAssistantOpen"
    class="fixed right-4 top-auto left-auto bottom-20 w-full w-96 max-w-[90vw] bg-base-100 bg-white border rounded-xl shadow-2xl p-0 z-[1000] hidden md:block">
    <Assistant :modal="true" />
  </div>
</template>

<script>
import {useAssistantStore} from '../../stores/assistant.js';
import Assistant from "../assistant/Assistant.vue";

export default {
  components: {
    Assistant,
  },
  setup() {
    const assistantStore = useAssistantStore();
    return { assistantStore };
  },
  watch: {
    // '$route.hash'(newVal) {
    //   if (typeof newVal === 'string') {
    //     if (newVal.startsWith('#assistant')) {
    //       this.assistantStore.isAssistantOpen = true;
    //       return;
    //     }
    //   }
    //   this.assistantStore.isAssistantOpen = false;
    // },
  },
};
</script>