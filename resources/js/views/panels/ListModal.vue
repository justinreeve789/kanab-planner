<template>
  <input type="checkbox" :id="type + '-list'" class="modal-toggle" v-model="expanded" />
  <div class="modal" v-if="expanded">
    <div class="modal-container w-11/12 xl:w-9/12">
      <div class="modal-box p-4 max-w-none w-full">
        <TrailList />
        <label :for="type + '-list'">
          <span class="btn btn-md btn-ghost bg-transparent border-green-800 text-2xl text-green-800 absolute right-2 top-2" style="z-index: 5000;">X</span>
        </label>
      </div>
    </div>
  </div>
  <label :for="type + '-list'" class="modal-backdrop"></label>
</template>

<script>
export default {
  created() {
    //this.$root.$on('select-' + this.type, (activity) => {
    //  this.activity = activity;
    //});
  },
  data() {
    return {
      activity: {},
      expanded: false,
    };
  },
  props: {
    type: String,
  },
};
</script>

<script setup>
import TrailList from '../lists/TrailList.vue';
</script>