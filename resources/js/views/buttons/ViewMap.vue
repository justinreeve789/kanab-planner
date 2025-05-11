<template>
  <label class="mb-2 sm:mb-0 sm:mr-2">
    <span class="block w-full sm:w-auto text-white bg-gradient-to-r from-orange-400 via-orange-500 to-orange-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-orange-300 dark:focus:ring-orange-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
      @click="openMap">
      View map
    </span>
  </label>
</template>

<script>
import {useMapStore} from "../../stores/map";

export default {
  setup() {
    const mapStore = useMapStore();
    return { mapStore };
  },
  components: {
  },
  data() {
    return {
    };
  },
  methods: {
    openMap() {
      this.mapStore.setResource(this.resource);
      this.mapStore.isMapOpen = true;
    },
    // zoom() {
    //   // TODO: We'll want to include their base area on the map, so they have an idea where the trail is in relation to
    //   // it. If it's far away, zoom out. If it's close, zoom in.
    //   return 15;
    // },
  },
  props: {
    resource: Object,
  },
};
</script>
