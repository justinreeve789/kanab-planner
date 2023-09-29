<template>
  <CarouselGallery :images="images" v-if="images" />
</template>

<script>
export default {
  components: {
    CarouselGallery,
  },
  data() {
  },
  props: {
    hash: String,
    resource: Object,
    images: Object,
    type: String,
  },
};
</script>

<script setup>
import CarouselGallery from '../components/CarouselGallery.vue'
</script>