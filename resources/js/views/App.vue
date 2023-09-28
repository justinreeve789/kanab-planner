<template>
    <Itinerary />
</template>

<script>
import axios from 'axios';
import Itinerary from './Itinerary.vue';
//import 'mapbox-gl/dist/mapbox-gl.css';
import "leaflet/dist/leaflet.css";
import { LMap, LTileLayer } from "@vue-leaflet/vue-leaflet";

export default {
  components: {
    Itinerary,
  }
};
</script>