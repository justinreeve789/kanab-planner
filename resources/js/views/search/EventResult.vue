<template>
  <div tabindex="0" class="collapse collapse-arrow mb-2 bg-base-100 border-base-300 border rounded-none lg:rounded-xl lg:px-4">
    <input v-model="expanded" type="checkbox" class="peer" />
    <div class="collapse-title h-4 bg-gradient-to-r from-blue-100 to-cyan-200 flex items-stretch justify-between">
      <div class="flex items-center">
        <div class="-my-8 -ml-4 h-16 w-12 sm:w-16 bg-neutral relative">
          <div class="text-center text-sm text-white absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
            {{ formatDateFromTimestamp(resource.start_date) }}
          </div>
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
      </div>
    </div>
    <div class="collapse-content border border-solid border-gray-300 border-t-0 text-sm bg-gradient-to-r from-white to-cyan-100" v-if="expanded">
      <div class="card-body md:w-2/3 flex flex-col justify-between p-4 md:rounded-lg">
        <p v-if="resource.area_name" class="text-gray-500">{{ resource.area_name }}</p>
        <p class="text-gray-600 line-clamp-3">{{ resource.description }}</p>
        <div class="card-actions mt-4 space-x-2 flex-wrap text-left md:justify-end">
          <AddToItinerary :resource="resource" resource_table="events" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AddToItinerary from "../buttons/AddToItinerary.vue";

export default {
  components: {
    AddToItinerary,
  },
  setup() {
  },
  mounted() {
  },
  data() {
    return {
      expanded: false,
    };
  },
  props: {
    resource: Object,
  },
  computed: {
    getImageUrl() {
      if (this.resource.images && this.resource.images[0]) {
        return 'image/event/' + this.resource.images[0].id + '?size=thumbnail';
      }
    },
  },
  methods: {
    formatDateFromTimestamp(timestamp) {
      const date = new Date(timestamp * 1000);
      return date.toLocaleDateString('en-us', {
        month: 'short',
        day: 'numeric',
      });
    },
  },
}
</script>