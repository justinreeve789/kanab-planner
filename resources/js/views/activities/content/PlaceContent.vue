<template>
  <div class="items-stretch w-full p-2 sm:p-3">
    <div class="lg:flex lg:justify-between gap-4">
      <div class="w-full lg:w-2/3 space-y-3">
        <p v-if="resource.description_short">{{ resource.description_short }}</p>
        <p v-else-if="resource.description" class="line-clamp-6">{{ resource.description }}</p>
        <p v-if="phrase"><em>{{ phrase }}</em></p>
        <p v-if="resource.address" class="text-sm text-stone-600">{{ resource.address }}<span v-if="resource.city">, {{ resource.city }}</span></p>
        <div class="flex flex-wrap gap-2">
          <a v-if="resource.url" :href="resource.url" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline">Website</a>
          <ActivityButtons :resource="resource" :resource_table="resourceTable" type="place" :hide-add="hideAdd" />
        </div>
      </div>
      <div v-if="images && images.length" class="w-full lg:w-1/3 mt-4 lg:mt-0">
        <Side :images="images" :resource="resource" :type="type" />
      </div>
    </div>
  </div>
</template>

<script>
import ActivityButtons from '../../components/ActivityButtons.vue';
import Side from '../../panels/Side.vue';

/**
 * Details for an event or a location (museum, viewpoint, visitor center...).
 */
export default {
  components: { ActivityButtons, Side },
  props: {
    resource: { type: Object, required: true },
    resourceTable: { type: String, default: 'locations' },
    phrase: String,
    hideAdd: { type: Boolean, default: false },
  },
  computed: {
    type() {
      return this.resourceTable === 'events' ? 'event' : 'location';
    },
    images() {
      return this.resource.images?.length ? this.resource.images : this.resource.location?.images;
    },
  },
};
</script>
