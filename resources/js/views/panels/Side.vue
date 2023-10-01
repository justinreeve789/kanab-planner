<template>
  <Gallery :images="images" :type="type" :hash="hash" v-if="images" />
</template>

<script>
export default {
  components: {
    Gallery,
  },
  data() {
    return {
    };
  },
  methods: {
  },
  mounted() {
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
import Gallery from '../components/Gallery.vue'
</script>