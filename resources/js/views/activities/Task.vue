<template>
  <div class="resource-details collapse collapse-arrow" data-type="task" :data-activity-id="activity.id">
    <input type="checkbox" v-model="expanded" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-blue-100 to-cyan-200">
      <div class="title flex items-center space-x-2">
        <div class="icon pr-1">
        </div>
        <div class="text-left text-lg md:text-xl">{{ activity.name }}</div>
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
