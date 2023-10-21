<template>
  <div v-if="trail" :class="'activity-stats flex align-baseline items-center ' + justify + ' ' + text_align + ' flex-wrap'">
    <span class="distance flex">
     <img class="icon pr-1 svg-anim" src="/images/icons/distance.svg" alt="Distance">
      <span class="amount pr-1">{{ trail.distance_miles }}</span>
      <span class="measurement">mi</span>
    </span>
    <span class="incline flex justify-end ml-3 order-3 md:order-2">
      <img class="icon pr-1 svg-anim" src="/images/icons/mountain.svg" alt="Elevation Gain">
      <span class="amount pr-1">+{{ trail.incline_ft }}</span>
      <span class="measurement">ft</span>
    </span>
    <span class="average-grade flex ml-3 order-2 md:order-3">
      <img class="icon pr-1 svg-anim" src="/images/icons/average-grade.svg" alt="Average Grade">
      <span class="amount">{{ average_grade_pt }}</span>
      <span class="measurement">%</span>
    </span>
  </div>
</template>

<script>
export default {
  computed: {
    average_grade_pt() {
      return Math.round(this.trail.average_grade_pt * 100);
    },
    justify() {
      return (this.align === 'right') ? 'justify-end' : '';
    },
    text_align() {
      return 'text-' + this.align;
    },
  },
  data() {
  },
  props: {
    align: String,
    event: Object,
    location: Object,
    restaurant: Object,
    trail: Object,
  },
};
</script>