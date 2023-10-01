<template>
  <l-map ref="map" v-model:zoom="zoom" :center="[activity.data.latitude, activity.data.longitude]">
    <l-tile-layer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        layer-type="base"
        name="OpenStreetMap"
    ></l-tile-layer>
    <l-geo-json :geojson="geoJson" :options-style="geoOptions"></l-geo-json>
  </l-map>
</template>

<script>
import axios from 'axios';
import "leaflet/dist/leaflet.css";
import { LGeoJson, LMap, LTileLayer } from "@vue-leaflet/vue-leaflet";

export default {
  components: {
    LGeoJson,
    LMap,
    LTileLayer,
  },
  data() {
    return {
      geoOptions: {
        // GPX options (optional, adjust as needed)
        color: 'red', // Line color
        weight: 3, // Line weight
        opacity: 0.7, // Line opacity
      },
      geoJson: {},
      zoom: 14,
    };
  },
  methods: {
    getGeoJson() {
      axios
          .get('/api/v1/trail/' + this.activity.data.id + '/geojson', {
          })
          .then((response) => {
            this.geoJson = response.data;
            console.log(this.geoJson);
          })
          .catch((error) => {
            console.error('Error fetching geoJson data:', error);
          });
    },
  },
  mounted() {
    this.getGeoJson();
  },
  props: {
    activity: Object,
  },
};
</script>