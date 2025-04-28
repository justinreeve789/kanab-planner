<template>
  <div tabindex="0" class="collapse collapse-arrow mb-2 bg-base-100 border-base-300 border rounded-none lg:rounded-xl lg:px-4">
    <input type="checkbox" class="peer" />
    <div class="collapse-title h-4 bg-gradient-to-r from-orange-100 to-red-100 flex items-stretch justify-between">
      <div class="flex items-center">
        <div class="-my-8 -ml-4 h-16 w-12 sm:w-16 relative">
          <img :src="getImageUrl" class="object-cover h-full w-full">
          <div :class="resource.area_slug" class="absolute top-0 bg-primary w-full max-h-6 opacity-80 text-center text-xs text-white">
            {{ resource.area_short_name }}
          </div>
        </div>
        <div class="icon pr-1 shrink-0">
        </div>
        <div class="text-md line-clamp-2 ml-2">{{ resource.name }}</div>
      </div>
      <div class="flex items-center">
        <div class="tooltip mr-2" :data-tip="getDifficulty.name">
          <div :class="getDifficulty.class" class="status status-lg ml-2"></div>
        </div>
        <div class="mr-2 text-xs">
          <TrailStats :trail="resource" :showDistance="true" :showElevation="false" :showGrade="false" v-if="resource.distance_miles"/>
        </div>
        <button class="btn btn-primary btn-sm mr-2" @click="addToItinerary(resource.result_id)">
          +
          <span class="hidden md:flex">Add</span>
        </button>
      </div>
    </div>
    <div class="collapse-content p-0">
      <TrailContent :resource="resource" />
    </div>
<!--      text-sm bg-gradient-to-r from-white to-red-50">-->
<!--      <div class="card-body md:w-2/3 flex flex-col justify-between p-4 md:rounded-lg">-->
<!--        <p># miles from Kanab</p>-->
<!--        <p class="text-gray-600 line-clamp-3">{{ resource.description }}</p>-->
<!--        <div class="card-actions mt-4 space-x-2 flex-wrap text-left md:justify-end">-->
<!--          <ViewMap :resource="resource" />-->
<!--        </div>-->
<!--      </div>-->
<!--    </div>-->
  </div>
</template>

<script>
import TrailStats from "../stats/TrailStats.vue";
import ViewMap from "../buttons/ViewMap.vue";
import TrailContent from "../activities/content/TrailContent.vue";

export default {
  components: {
    TrailContent,
    TrailStats,
  },
  setup() {
  },
  mounted() {
  },
  data() {
    return {
    };
  },
  props: {
    resource: Object,
  },
  computed: {
    getDifficulty() {
      if (this.resource.strenuousness) {
        if (this.resource.strenuousness <= 0.5) {
          return { class: 'difficulty-easy', name: 'Easy' };
        } else if (this.resource.strenuousness <= 0.75) {
          return { class: 'difficulty-medium', name: 'Medium' };
        } else {
          return { class: 'difficulty-hard', name: 'Hard' };
        }
      }
    },
    getImageUrl() {
      if (this.resource.images && this.resource.images[0]) {
        return 'image/trail/' + this.resource.images[0].id + '?size=thumbnail';
      }
    },
  },
  methods: {
    addToItinerary(resultId) {
    },
  },
}
</script>