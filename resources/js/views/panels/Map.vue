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
        className: 'trail-path',
        color: '#ff0080', // Replace with your gradient colors
        weight: 5, // Adjust the line thickness
        opacity: 0.8, // Adjust the opacity
        shadowBlur: 15, // Add a blur effect for the glow
        shadowColor: '#bb0080', // Set the glow color to match your gradient
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