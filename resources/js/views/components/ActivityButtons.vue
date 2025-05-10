<template>
  <div class="flex flex-col sm:flex-row" v-if="type == 'checking-in' || type == 'restaurant' || type == 'trail'">
<!--    <FindAccommodations :resource="resource" v-if="type == 'checking-in'" />-->
    <ViewAccommodations :resource="resource" v-if="type == 'checking-in'" />
    <ViewMenu :resource="resource" v-if="type == 'restaurant'" />
    <ViewMap :resource="resource" v-if="type == 'trail'" />
    <AskQuestion :resource="resource" :resource_table="resource_table" :resource_id="resource_id" v-if="type == 'trail'" />
    <AddToItinerary :resource="resource" :resource_table="resource_table" :resource_id="resource_id" />
  </div>
</template>

<script>
import AddToItinerary from "../buttons/AddToItinerary.vue";
import AskQuestion from '../buttons/AskQuestion.vue';
import FindAccommodations from '../buttons/FindAccomodations.vue';
import ViewAccommodations from '../buttons/ViewAccommodations.vue';
import ViewMap from '../buttons/ViewMap.vue';
import ViewMenu from '../buttons/ViewMenu.vue';
import { useAssistantStore } from '../../stores/assistant.js';

export default {
  emits: ['openAssistant'],
  components: {
    AddToItinerary,
    AskQuestion,
    FindAccommodations,
    ViewAccommodations,
    ViewMap,
    ViewMenu,
  },
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
    type: String,
  },
};
</script>
