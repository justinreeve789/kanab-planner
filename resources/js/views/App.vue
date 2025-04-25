<template>
  <Itinerary :itinerary-id="itineraryId" />
</template>

<script>
import Itinerary from './Itinerary.vue';
import QuestionnaireModal from './modals/QuestionnaireModal.vue';
import {useFeatureStore} from "../stores/feature";

export default {
  components: {
    Itinerary,
    QuestionnaireModal,
  },
  props: {
    itineraryId: {
      type: String,
      required: false,
    }
  },
  data() {
    return {
      completedQuestionnaire: false,
      openQuestionnaire: false,
    };
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
    this.openQuestionnaire = true;
  },
};
</script>
