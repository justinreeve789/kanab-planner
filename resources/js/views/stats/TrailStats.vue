<template>
  <div v-if="trail" :class="'activity-stats items-start ' + justify + ' ' + text_align">
    <span class="distance flex items-center mb-0 mr-6" v-if="showDistance">
      <img alt="Distance" class="icon pr-1 svg-anim" src="/images/icons/distance.svg">
      <span class="amount pr-1">{{ trail.distance_miles }}</span>
      <span class="measurement">mi</span>
    </span>

    <span class="incline flex items-center mb-0 mr-6 sm:order-2" v-if="showElevation">
      <img alt="Elevation Gain" class="icon pr-1 svg-anim" src="/images/icons/mountain.svg">
      <span class="amount pr-1">+{{ trail.incline_ft }}</span>
      <span class="measurement">ft</span>
    </span>

    <span class="average-grade flex items-center sm:order-3" v-if="showGrade">
      <img alt="Average Grade" class="icon pr-1 svg-anim" src="/images/icons/average-grade.svg">
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
    return '';
  },
  props: {
    align: String,
    trail: Object,
    showDistance: {
      type: Boolean,
      default: true,
    },
    showElevation: {
      type: Boolean,
      default: true,
    },
    showGrade: {
      type: Boolean,
      default: true,
    },
  },
};
</script>
