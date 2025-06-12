<template>
  <div class="road -mt-6 relative">
<!--    <img class="car" src="/images/icons/car-3.svg" alt="" />-->
    <div class="distance">{{ activity.distance_miles }}</div>
  </div>
</template>

<script>
export default {
  components: {
  },
  created() {
  },
  data() {
    return {
    }
  },
  props: {
    activity: Object,
  },
};
</script>
