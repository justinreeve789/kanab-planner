<template>
  <swiper-container :class="'flex justify-between space-x-2'" slides-per-view="4" loop="false" v-if="photos.length">
    <swiper-slide v-for="(photo, index) in photos" :key="index">
      <div class="bg-cover" :style="'background-image: url(' + photo.url + '); min-height: 160px; width: 160px;'" alt="..." />
    </swiper-slide>
  </swiper-container>
</template>

<script>
export default {
  components: {
    Photo,
  },
  props: {
    photos: Object,
  },
};
</script>

<script setup>
import Photo from '../panels/Photo.vue'
</script>