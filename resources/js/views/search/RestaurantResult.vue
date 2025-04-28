<template>
  <div tabindex="0" class="collapse collapse-arrow mb-2 bg-base-100 border-base-300 border rounded-none lg:rounded-xl lg:px-4">
    <input type="checkbox" class="peer" />
    <div class="collapse-title h-4 bg-gradient-to-r from-green-100 to-green-200 flex items-stretch justify-between">
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
        <div class="mr-2 text-xs">
        </div>
        <button class="btn btn-primary btn-sm mr-2" @click="addToItinerary(resource.result_id)">
          +
          <span class="hidden md:flex">Add</span>
        </button>
      </div>
    </div>
    <div class="collapse-content">
      <RestaurantContent :resource="resource" />
    </div>
  </div>
</template>

<script>
import RestaurantContent from "../activities/content/RestaurantContent.vue";

export default {
  components: {
    RestaurantContent,
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
    getImageUrl() {
      if (this.resource.images && this.resource.images[0]) {
        return 'image/restaurant/' + this.resource.images[0].id + '?size=thumbnail';
      }
    },
  },
  methods: {
    addToItinerary(resultId) {
    },
  },
}
</script>