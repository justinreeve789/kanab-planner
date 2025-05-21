<template>
  <ul class="steps w-full text-sm md:text-md">
    <li class="step cursor-pointer" :class="{'step-primary': this.step >= 1}" @click="$router.push('questionnaire-dates')">Dates</li>
    <li class="step cursor-pointer" :class="{'step-primary': this.step >= 2}" @click="$router.push('questionnaire-activities')">Activities</li>
    <li class="step cursor-pointer" :class="{'step-primary': this.step >= 3}" @click="$router.push('questionnaire-about')">About You</li>
    <li class="step cursor-pointer" :class="{'step-primary': this.step >= 4}" @click="$router.push('questionnaire-destinations')">Destinations</li>
    <li class="step cursor-pointer" :class="{'step-primary': this.step >= 5}" @click="$router.push('questionnaire-complete')">Itinerary</li>
  </ul>
</template>

<script>
import {usePreferencesStore} from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  emits: ['update:modelValue'],
  components: {
  },
  props: {
    step: Number,
  },
  data() {
    return {
    };
  },
  methods: {
    goToStep(stepNum) {
      this.$emit('update:modelValue', stepNum);
    },
  },
  mounted() {
  },
};
</script>