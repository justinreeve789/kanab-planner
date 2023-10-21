<script setup>
import {ref} from 'vue';
import {modal} from "../../modal.js";
</script>

<template>
  <div v-if="showModal" id="modal-main" class="modal">
    <div class="modal-content">
      <span @click="closeModal" class="close">&times;</span>
      <p>This is your modal content.</p>
    </div>
  </div>
  <div v-if="showModal" class="modal-backdrop"></div>
</template>

<script>
export default {
  data() {
    return {
      showModal: false
    };
  },
  created() {
    // Listen for the custom event emitted by the sibling component (Itinerary.vue)
    this.$root.$on('open-modal', this.openModal);
  },
  methods: {
    openModal() {
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
    }
  }
};
</script>