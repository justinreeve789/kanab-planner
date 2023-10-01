<template>
  <Gallery :images="images" :type="type" :hash="hash" v-if="images" />
  <Map :hash="hash" />
</template>

<script>
export default {
  components: {
    CarouselGallery,
    Gallery,
    Map,
  },
  data() {
    return {
      activeTab: 0,
      tabs: [
        { id: "photos", label: "Photos" },
        { id: "map", label: "Map" }
      ]
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
import CarouselGallery from '../components/CarouselGallery.vue'
import Gallery from '../components/Gallery.vue'
import Map from '../panels/Map.vue'
</script>