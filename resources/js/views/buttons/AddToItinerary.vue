<template>
  <label class="mb-2 sm:mb-0 sm:mr-2">
    <span class="block w-full sm:w-auto text-white bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
          @click="addToItinerary">
      Add to itinerary
    </span>
  </label>
</template>

<script>
// import MapModal from "../modals/MapModal.vue";
// import {useMapStore} from "../../stores/map";

export default {
  setup() {
    // const mapStore = useMapStore();
    // return { mapStore };
  },
  components: {
    // MapModal
  },
  data() {
    return {
      // unique: this.$generateUnique(),
    };
  },
  methods: {
    addToItinerary() {
      // this.mapStore.setResource(this.resource);
      // this.mapStore.isMapOpen = true;
    },
  },
  props: {
    resource: Object,
  },
};
</script>
