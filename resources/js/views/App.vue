<template>
    <Itinerary />
</template>

<script>
import axios from 'axios';
import Itinerary from './Itinerary.vue';
import {useFeatureStore} from "../stores/feature";

export default {
  components: {
    Itinerary,
  },
  setup() {
    const featureStore = useFeatureStore();
    return featureStore;
  },
  methods: {
    getFeatureFlags() {
    },
  },
  mounted() {
    this.getFeatureFlags();
  },
};
</script>