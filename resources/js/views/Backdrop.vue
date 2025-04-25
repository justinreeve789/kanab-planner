<script>
import {useAssistantStore} from '../stores/assistant.js';

export default {
  setup() {
    const store = useAssistantStore();
    return {store};
  },
  methods: {
    showBackdrop() {
      return this.store.isAssistantOpen;
    }
  },
}
</script>

<template>
<div v-if="showBackdrop()" class="backdrop"></div>
</template>

<style scoped>

</style>