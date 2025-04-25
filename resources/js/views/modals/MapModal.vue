<template>
  <input type="checkbox" :id="'resource-' + unique + '-map'" class="modal-toggle" v-model="expanded" />
  <div class="modal" :id="'resource-' + unique + '-map-modal'">
    <div class="modal-box p-2 max-w-none w-full" style="height: 100%;">
      <Map :resource="resource" :zoom="zoom" />
      <label :for="'resource-' + unique + '-map'">
        <span class="close-modal btn btn-md btn-ghost bg-transparent border-green-800 text-2xl text-green-800 absolute right-2 top-2">✕</span>
      </label>
    </div>
  </div>
  <label :for="'activity-' + 'hash' + '-map'" class="modal-backdrop"></label>
</template>

<script>
import Map from '../components/Map.vue';

export default {
  components: {
    Map,
  },
  data() {
    return {
      expanded: false,
    };
  },
  props: {
    resource: Object,
    unique: String,
    zoom: Number,
  },
};
</script>
