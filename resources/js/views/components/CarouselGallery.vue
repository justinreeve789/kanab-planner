<template>
  <swiper-container slides-per-view="4" loop="false" v-if="images.length">
    <swiper-slide v-for="(image, index) in images" :key="index" style="height: 320px; width: 320px;">
      <img :src="image.url" style="height: 320px;" alt="...">
    </swiper-slide>
  </swiper-container>
</template>

<script>
export default {
  components: {
  },
  props: {
    images: Object,
  },
};
</script>

<script setup>
</script>