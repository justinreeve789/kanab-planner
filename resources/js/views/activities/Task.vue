<template>
  <div class="resource-details collapse collapse-arrow" data-type="task" :data-activity-hash="activity.hash">
    <input type="checkbox" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-blue-100 to-cyan-200">
      <div class="title flex items-center space-x-2">
        <div class="icon pr-1">
          <img src="/images/icons/dining.svg" alt="Task" v-if="activity.activityType == 'cooking'">
          <img src="/images/icons/thumbs-up.svg" alt="Task" v-else>
        </div>
        <div class="text-xl text-left">{{ activity.name }}</div>
      </div>
      <div class="activity-stats flex items-center justify-end text-right flex-wrap">
      </div>
    </div>

    <div class="collapse-content bg-gray-200 p-2 bg-gradient-to-r from-white to-cyan-100">
      <div class="md:flex md:justify-between">
        <div class="w-full md:w-2/3">
          <p class="mb-4" v-if="activity.description">{{ activity.description }}</p>
          <p v-if="activity.phrase"><em>{{ activity.phrase }}</em></p>
        </div>
        <div class="w-full md:w-1/3 mt-4 md:mt-0 md:pl-4" v-if="activity.location">
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