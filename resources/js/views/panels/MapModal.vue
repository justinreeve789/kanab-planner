<template>
  <input type="checkbox" :id="'activity-' + hash + '-map'" class="modal-toggle" v-model="expanded" />
  <div class="modal" :id="'activity-' + hash + '-map'">
    <div class="modal-box p-2 max-w-none w-full" style="height: 100%;">
      <Map :activity="activity" />
      <label :for="'activity-' + hash + '-map'">
        <span class="btn btn-md btn-ghost bg-transparent border-green-800 text-2xl text-green-800 absolute right-2 top-2" style="z-index: 5000;">X</span>
      </label>
<!--      <div class="progress w-full h-screen flex justify-center items-center text-6xl relative">-->
<!--        <span class="text text-center text-6xl text-white font-bold relative">-->
<!--          Loading-->
<!--          <span class="loading loading-dots loading-lg text-white absolute ml-2 bottom-0"></span>-->
<!--        </span>-->
<!--      </div>-->
    </div>
  </div>
  <label :for="'activity-' + hash + '-map'">
    <div class="modal-backdrop"></div>
  </label>
</template>

<script>
import Map from './Map.vue';

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
    activity: Object,
    hash: String,
  },
};
</script>