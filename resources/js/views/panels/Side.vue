<template>
  <Gallery :images="images" :type="type" v-if="images" />
</template>

<script>
import Gallery from '../components/Gallery.vue'

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