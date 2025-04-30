<template>
<!--  <input type="checkbox" :id="'resource-' + unique + '-map'" class="modal-toggle" />-->
  <div class="modal z-100" id="map-modal" v-if="isMapOpen">
    <div class="modal-box p-2 max-w-none w-full" style="height: 100%;">
      <Map />
      <label @click="closeMap">
        <span class="close-modal btn btn-md btn-ghost bg-transparent border-green-800 text-2xl text-green-800 absolute right-2 top-2">✕</span>
      </label>
    </div>
  </div>
<!--  <label :for="'activity-' + 'hash' + '-map'" class="modal-backdrop"></label>-->
</template>

<script>
import Map from '../components/Map.vue';
import {useMapStore} from "../../stores/map";

export default {
  setup() {
    const mapStore = useMapStore;
    return { mapStore };
  },
  components: {
    Map,
  },
  computed: {
    isMapOpen() {
      return this.mapStore.isMapOpen;
    }
  },
  data() {
    return {
      expanded: false,
    };
  },
  methods: {
    closeMap() {
      this.mapStore.isMapOpen = false;
    },
  },
  props: {
    resource: Object,
    unique: String,
    zoom: Number,
  },
};
</script>
