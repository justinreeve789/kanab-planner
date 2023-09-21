<template>
    <Gallery :photos="resource.images" v-if="type === 'trail' && resource.images" />
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