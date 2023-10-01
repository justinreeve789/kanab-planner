<template>
  <input type="checkbox" :id="'activity-' + hash + '-map'" class="modal-toggle" />
  <div class="modal">
    <div class="modal-box p-2 max-w-none w-full" style="height: 100%;">
      <Map :activity="activity" />
<!--      <div class="progress w-full h-screen flex justify-center items-center text-6xl relative">-->
<!--        <span class="text text-center text-6xl text-white font-bold relative">-->
<!--          Loading-->
<!--          <span class="loading loading-dots loading-lg text-white absolute ml-2 bottom-0"></span>-->
<!--        </span>-->
<!--      </div>-->
    </div>
  </div>
  <label class="modal-backdrop" :for="'activity-' + hash + '-map'"></label>
</template>

<script>
import Map from './Map.vue';

export default {
  components: {
    Map,
  },
  data() {
    return {
    };
  },
  props: {
    activity: Object,
    hash: String,
  },
};
</script>