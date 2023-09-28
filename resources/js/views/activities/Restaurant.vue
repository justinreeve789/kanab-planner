<template>
  <div class="resource-details collapse collapse-arrow" data-type="restaurant" :data-id="restaurant.id">
    <input type="checkbox" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-green-100 to-green-200">
      <div class="title flex items-center space-x-2">
        <div class="icon pr-1">
          <img src="/images/icons/dining.svg" alt="Dining">
        </div>
        <div class="text-xl text-left">{{ restaurant.name }}</div>
      </div>
      <div class="activity-stats flex items-center justify-end text-right flex-wrap">
        <span class="distance flex">
          <img class="icon pr-1 svg-anim" src="/images/icons/distance.svg" alt="Distance">
          <span class="amount pr-1">2.1</span>
          <span class="measurement">mi</span>
        </span>
        <span class="incline flex justify-end ml-3 order-3 md:order-2">
          <img class="icon pr-1 svg-anim" src="/images/icons/mountain.svg" alt="Elevation Gain">
          <span class="amount pr-1">+700</span>
          <span class="measurement">ft</span>
        </span>
      </div>
    </div>

    <div class="collapse-content bg-gray-200 p-2 bg-gradient-to-r from-white to-green-100">
      <div class="md:flex md:justify-between">
        <div class="w-full md:w-2/3">
          <p class="mb-4" v-if="restaurant.description_alt">{{ restaurant.description_alt }}</p>
          <p class="mb-4" v-if="restaurant.description">{{ restaurant.description }}</p>
          <p v-if="activity.phrase"><em>{{ activity.phrase }}</em></p>
        </div>
        <div class="w-full md:w-1/3 mt-4 md:mt-0 md:pl-4" v-if="restaurant.location">
          <Side :resource="restaurant" :hash="activity.hash" :type="'restaurant'" :images="restaurant.location.images" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  components: {
  },
  created() {
    console.log(this.activity.hash);
    this.restaurant = this.activity.data;
  },
  props: {
    activity: Object,
    day: Object,
  },
};
</script>

<script setup>
import { onMounted } from 'vue'
import Side from '../panels/Side.vue'
</script>