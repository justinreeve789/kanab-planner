<template>
  <div class="resource-details collapse collapse-arrow" data-type="restaurant" :data-id="resource.id">
    <input type="checkbox" v-model="expanded" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-green-100 to-green-200">
      <div class="title w-1/2 flex items-center space-x-2 text-left">
        <div class="icon pr-1 shrink-0">
          <img src="/images/icons/dining.svg" alt="Dining">
        </div>
        <div class="text-xl">{{ resource.name }}</div>
      </div>
      <div class="flex space-x-4 items-center mr-8 h-full">
        <RestaurantStats :restaurant="resource" :align="'right'" class="hidden lg:flex" />
        <label for="restaurant-list">
          <span @click="select" class="icon btn btn-sm btn-ghost relative z-10 h-full">
            <img src="/images/icons/edit.svg" alt="Change Activity">
          </span>
        </label>
      </div>
    </div>

    <div class="collapse-content min-h-[150px] items-stretch bg-gray-200 p-2 bg-gradient-to-r from-white to-green-100" v-if="expanded">
      <div class="lg:flex lg:justify-between">
        <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
          <div class="lg:order-2">
            <ActivityButtons class="text-right my-4 lg:text-left" type="restaurant" :resource_table="'restaurants'" :resource_id="resource.id" />
          </div>
          <div class="lg:order-1">
            <p class="mb-4" v-if="resource.description_alt">{{ resource.description_alt }}</p>
            <p class="mb-4" v-if="resource.description">{{ resource.description }}</p>
            <p v-if="phrase"><em>{{ phrase }}</em></p>
          </div>
        </div>
        <div class="w-[calc(100%+16px)] -ml-2 -mb-4 lg:w-1/3 mt-4 lg:pl-4 lg:-mt-2 lg:-mr-2" v-if="resource.location.images">
          <Side :resource="restaurant" :type="'restaurant'" :images="resource.location.images" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import RestaurantStats from "../stats/RestaurantStats.vue";

export default {
  components: {
    ActivityButtons,
    RestaurantStats,
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
      unique: this.$generateUnique(),
    }
  },
  props: {
      activity: Object,
      phrase: String,
      resource: Object,
  },
};
</script>

<script setup>
import ActivityButtons from '../components/ActivityButtons.vue'
import RestaurantStats from '../stats/RestaurantStats.vue'
import Side from '../panels/Side.vue'
</script>
