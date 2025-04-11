<template>
  <div class="modal" :id="'resource-' + unique + '-map-modal'">
    <div class="modal-box p-2 max-w-none w-full" style="height: 100%;">
    </div>
  </div>
  <label :for="'activity-' + 'hash' + '-map'" class="modal-backdrop"></label>
</template>

<script>
export default {
  components: {
  },
  data() {
    return {
      expanded: false,
      unique: this.$generateUnique(),
    };
  },
  props: {
    unique: String,
  },
};
</script>
