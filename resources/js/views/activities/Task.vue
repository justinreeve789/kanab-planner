<template>
  <div class="resource-details collapse collapse-arrow" data-type="task" :data-activity-id="activity.id">
    <input type="checkbox" v-model="expanded" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-blue-100 to-cyan-200">
      <div class="title flex items-center space-x-2">
        <div class="icon pr-1">
          <img src="/images/icons/dining.svg" alt="Task" v-if="activity.activity_type.name == 'cooking'">
          <img src="/images/icons/thumbs-up.svg" alt="Task" v-else>
        </div>
        <div class="text-xl text-left">{{ activity.resource.name }}</div>
      </div>
      <div class="activity-stats flex items-center justify-end text-right flex-wrap">
      </div>
    </div>

    <div class="collapse-content min-h-[150px] items-stretch w-full bg-gray-200 p-2 bg-gradient-to-r from-white to-cyan-100" v-if="expanded">
      <div class="lg:flex lg:justify-between">
        <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
          <div class="lg:order-2">
            <div class="flex justify-between">
              <div class="flex-auto text-left lg:hidden">
              </div>
              <div class="flex-right">
                <ActivityButtons class="text-right my-4 lg:text-left" :resource_table="'accommodations'" :resource="resource" :type="activity.activity_type" />
              </div>
            </div>
          </div>
          <div class="lg:order-1">
            <p class="mb-4" v-if="activity.description">{{ activity.resource.description }}</p>
            <p v-if="activity.phrase"><em>{{ activity.phrase }}</em></p>
          </div>
        </div>
        <div class="w-[calc(100%+16px)] -ml-2 -mb-4 lg:w-1/3 mt-4 lg:pl-4 lg:-mt-2 lg:-mr-2">
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
  data() {
    return {
      expanded: false,
    }
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
import ActivityButtons from "../components/ActivityButtons.vue";
import TrailStats from "../stats/TrailStats.vue";
</script>
