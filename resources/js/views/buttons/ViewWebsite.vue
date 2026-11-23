<template>
  <a v-if="url" :href="url" target="_blank" rel="noopener noreferrer" class="mb-2 sm:mb-0 sm:mr-2 block w-full sm:w-auto">
    <span class="block w-full sm:w-auto text-white bg-gradient-to-r from-green-400 via-green-500 to-green-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
      Website
    </span>
  </a>
</template>

<script>
/**
 * Opens a place's own website. Restaurants keep theirs on their location; hidden when there isn't one.
 */
export default {
  props: {
    resource: Object,
  },
  computed: {
    url() {
      const url = this.resource?.location?.url || this.resource?.url;
      return /^https?:\/\//i.test(url ?? '') ? url : null;
    },
  },
};
</script>
