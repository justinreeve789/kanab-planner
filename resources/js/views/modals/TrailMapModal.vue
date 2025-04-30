<template>
  <div class="modal modal-open" id="trail-map-modal" v-if="isMapOpen">
    <div class="modal-box p-2 max-w-none w-full" style="height: 100%;">
      <TrailMap :latitude="latitude" :longitude="longitude" :resource-id="resourceId" :zoom="zoom" />
      <span class="close-modal btn btn-md btn-ghost bg-transparent border-green-800 text-2xl text-green-800 absolute right-2 top-2"
        @click="closeMap">✕</span>
    </div>
  </div>
  <label for="trail-map-modal" class="modal-backdrop"></label>
</template>

<script>
import TrailMap from '../components/TrailMap.vue';
import {useMapStore} from "../../stores/map";

export default {
  setup() {
    const mapStore = useMapStore();
    return { mapStore };
  },
  components: {
    TrailMap,
  },
  computed: {
    isMapOpen() {
      return this.mapStore.isMapOpen;
    },
    latitude() {
      return this.mapStore.latitude ?? '';
    },
    longitude() {
      return this.mapStore.longitude ?? '';
    },
    resourceId() {
      return this.mapStore.resourceId ?? 0;
    },
    zoom() {
      return this.mapStore.zoom ?? 15;
    }
  },
  data() {
  },
  props: {
  },
  methods: {
    closeMap() {
      this.mapStore.isMapOpen = false;
    },
  },
};
</script>
