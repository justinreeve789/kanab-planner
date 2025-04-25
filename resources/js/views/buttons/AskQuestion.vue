<template>
  <label :for="'resource-' + unique + '-assistant'" class="mb-2 sm:mb-0 sm:mr-2">
      <span @click="openAssistantForResource" class="block w-full sm:w-auto text-white bg-gradient-to-r from-purple-400 via-purple-500 to-purple-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-purple-300 dark:focus:ring-purple-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
        Ask question
      </span>
  </label>
</template>

<script>
import { useAssistantStore } from '../../stores/assistant.js';

export default {
  emits: ['openAssistant'],
  setup() {
    const store = useAssistantStore();
    return { store };
  },
  data() {
    return {
      unique: this.$generateUnique,
    };
  },
  methods: {
    openAssistantForResource() {
      this.store.setCurrentResource(this.resource_table, this.resource.id, this.resource);
      this.store.isAssistantOpen = true;
    },
  },
  props: {
    resource_table: String,
    resource: Object,
    type: String,
  },
};
</script>
