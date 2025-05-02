<template>
  <div class="resource-details collapse collapse-arrow" data-type="task" :data-activity-id="activity.id">
    <input type="checkbox" v-model="expanded" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-blue-100 to-cyan-200">
      <div class="title flex items-center space-x-2">
        <div class="icon pr-1">
<!--          <img src="/images/icons/dining.svg" alt="Task" v-if="activity.activity_type.name == 'cooking'">-->
<!--          <img src="/images/icons/thumbs-up.svg" alt="Task" v-else>-->
        </div>
        <div class="text-xl text-left">{{ activity.resource.name }}</div>
      </div>
      <div class="activity-stats flex items-center justify-end text-right flex-wrap">
      </div>
    </div>

    <div class="collapse-content p-0" v-if="expanded">
      <TaskContent :activity="activity" />
    </div>
  </div>
</template>

<script>
import TaskContent from "./content/TaskContent.vue";

export default {
  components: {
    TaskContent,
  },
  created() {
  },
  data() {
    return {
      expanded: false,
    }
  },
  props: {
    activity: Object,
  },
};
</script>

<script setup>
import { onMounted } from 'vue'
import Side from '../panels/Side.vue'
import ActivityButtons from "../components/ActivityButtons.vue";
import TrailStats from "../stats/TrailStats.vue";
import RestaurantContent from "./content/RestaurantContent.vue";
</script>
