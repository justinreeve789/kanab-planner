<template>
  <div class="resource-details collapse collapse-arrow" data-type="restaurant" :data-id="restaurant.id">
    <input type="checkbox" v-model="expanded" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-green-100 to-green-200">
      <div class="title flex items-center space-x-2">
        <div class="icon pr-1 shrink-0">
          <img src="/images/icons/dining.svg" alt="Dining">
        </div>
        <div class="text-xl text-left">{{ restaurant.name }}</div>
      </div>
      <div class="activity-stats flex items-center justify-end text-right flex-wrap">
        <span class="distance flex">
          <img class="icon pr-1 svg-anim" src="/images/icons/distance.svg" alt="Distance">
          <span class="amount pr-1"></span>
          <span class="measurement"></span>
        </span>
        <span class="activity-actions flex ml-3 order-4 relative z-10">
          <a class="shrink-0" href="#">
            <img class="icon" src="/images/icons/edit.svg" alt="Change Activity">
          </a>
        </span>
      </div>
    </div>

    <div class="collapse-content bg-gray-200 p-2 bg-gradient-to-r from-white to-green-100" v-if="expanded">
      <div class="lg:flex lg:justify-between">
        <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
          <div class="lg:order-2">
            <ActivityButtons class="text-right my-4 lg:text-left" type="restaurant" :hash="activity.hash" />
          </div>
          <div class="lg:order-1">
            <p class="mb-4" v-if="restaurant.description_alt">{{ restaurant.description_alt }}</p>
            <p class="mb-4" v-if="restaurant.description">{{ restaurant.description }}</p>
            <p v-if="activity.phrase"><em>{{ activity.phrase }}</em></p>
          </div>
        </div>
        <div class="w-full lg:w-1/3 mt-4 lg:mt-0 lg:pl-4" v-if="restaurant.location.images">
          <Side :resource="restaurant" :hash="activity.hash" :type="'restaurant'" :images="restaurant.location.images" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  components: {
    ActivityButtons,
  },
  computed: {
  },
  methods: {
  },
  created() {
  },
  data() {
    return {
      expanded: false,
      restaurant: this.activity.data,
    }
  },
  props: {
    activity: Object,
    day: Object,
  },
};
</script>

<script setup>
import ActivityButtons from '../panels/ActivityButtons.vue'
import Side from '../panels/Side.vue'
</script>