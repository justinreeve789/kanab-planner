<template>
  <Gallery :images="images" :type="type" v-if="images" />
  <Map :hash="hash" />
<!--    <div class="container mt-8">-->
<!--        <div class="tabs tabs-full-width">-->
<!--          <ul class="tab-list">-->
<!--            <li v-for="(tab, index) in tabs" :key="index" :class="{ active: index === activeTab }">-->
<!--              <a @click="setActiveTab(index)">{{ tab.label }}</a>-->
<!--            </li>-->
<!--          </ul>-->

<!--          <div class="tab-content">-->
<!--            <div v-for="(tab, index) in tabs" :key="index" :class="{ active: index === activeTab }">-->
<!--              <slot :name="tab.id"></slot>-->
<!--            </div>-->
<!--          </div>-->
<!--        </div>-->
<!--    </div>-->
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
    setActiveTab(index) {
      this.activeTab = index;
    }
  },
  mounted() {
    initTabs();
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
import {
    initTabs,
} from 'flowbite'
</script>