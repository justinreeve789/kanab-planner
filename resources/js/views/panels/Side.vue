<template>
    <Gallery :photos="images" v-if="images" />
</template>

<script>
export default {
  components: {
    Gallery,
  },
  mounted() {
    initTabs();
  },
  props: {
    images: Object,
    resource: Object,
    type: String,
  },
};
</script>

<script setup>
import Gallery from '../components/Gallery.vue'
import {
    initTabs,
} from 'flowbite'
</script>