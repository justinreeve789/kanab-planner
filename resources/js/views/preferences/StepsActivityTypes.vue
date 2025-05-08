<template>
  <ul class="steps w-full text-sm md:text-md">
    <li class="step cursor-pointer" :class="selectedTrailIds.length > 0 ? 'step-primary' : ''" v-if="showTrails">Trails</li>
    <li class="step cursor-pointer" :class="selectedEventIds.length > 0 ? 'step-primary' : ''" v-if="showEvents">Events</li>
  </ul>
</template>

<script>
export default {
  emits: ['update:modelValue'],
  components: {
  },
  props: {
    showEvents: Boolean,
    showTrails: Boolean,
  },
  data() {
    return {
      selectedEventIds: [],
      selectedLocationIds: [],
      selectedRestaurantIds: [],
      selectedTrailIds: [],
    };
  },
  methods: {
    goToStep(stepNum) {
      this.$emit('update:modelValue', stepNum);
    },
    isEvent(activityType) {
      return activityType === 'educational-and-fun' ||
        activityType === 'festivals-and-celebrations' ||
        activityType === 'museum' ||
        activityType === 'stargazing' ||
        activityType === 'night-life';
    },
    isTrail(activityType) {
      return activityType === 'bicycling' ||
        activityType === 'hiking' ||
        activityType === 'offroading' ||
        activityType === 'rock-climbing';
    },
  },
  mounted() {
  },
};
</script>