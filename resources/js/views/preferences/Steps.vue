<template>
  <ul class="steps w-full text-sm md:text-md">
    <li class="step cursor-pointer" :class="step >= 1 ? 'step-primary' : ''" @click="goToStep(1)">Activities</li>
    <li class="step cursor-pointer" :class="step >= 2 ? 'step-primary' : ''" @click="goToStep(2)">About You</li>
    <li class="step cursor-pointer" :class="step >= 3 ? 'step-primary' : ''" @click="goToStep(3)">Destinations</li>
    <li class="step cursor-pointer" :class="step >= 4 ? 'step-primary' : ''" @click="goToStep(4)">Itinerary</li>
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