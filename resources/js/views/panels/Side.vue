<template>
  <Gallery :images="images" :type="type" v-if="images" />
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
    resource: Object,
    images: Object,
    type: String,
  },
};
</script>

<script setup>
import Gallery from '../components/Gallery.vue'
</script>