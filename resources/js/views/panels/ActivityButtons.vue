<template>
  <div class="flex" v-if="type == 'restaurant' || type == 'trail'">
    <label :for="'resource-' + unique + '-menu'" v-if="type == 'restaurant'">
      <span class="text-white bg-gradient-to-r from-green-400 via-green-500 to-green-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-green-300 dark:focus:ring-green-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2">
        View menu
      </span>
    </label>
    <label :for="'resource-' + unique + '-map'" v-if="type == 'trail'">
      <span class="text-white bg-gradient-to-r from-orange-400 via-orange-500 to-orange-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-orange-300 dark:focus:ring-orange-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2">
        View map
      </span>
    </label>
    <label :for="'resource-' + unique + '-assistant'" v-if="type == 'trail'">
      <span @click="openAssistantForResource" class="text-white bg-gradient-to-r from-purple-400 via-purple-500 to-purple-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-purple-300 dark:focus:ring-purple-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2">
        Ask question
      </span>
    </label>
  </div>
</template>

<script>
import { useAssistantStore } from '../../stores/assistant.js';

export default {
  emits: ['openAssistant'],
  setup() {
    const store = useAssistantStore();
    return { store };
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
    unique: String,
    type: String,
  },
};
</script>
