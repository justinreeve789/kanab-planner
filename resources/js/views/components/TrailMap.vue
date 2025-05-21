<template>
  <l-map ref="map" v-model:zoom="zoom" :center="[latitude, longitude]">
    <l-tile-layer
      url="https://caltopo.com/tile/mb_hybrid/{z}/{x}/{y}.png"
      layer-type="base"
      name="Map"
    ></l-tile-layer>
<!--    <l-geo-json v-if="geoJson" :key="geoJsonKey" :geojson="geoJson" :options-style="geoOptions"></l-geo-json>-->
  </l-map>
</template>

<script>
import axios from 'axios';
import "leaflet/dist/leaflet.css";
import { LCircleMarker, LGeoJson, LIcon, LMap, LMarker, LTileLayer, LTooltip } from "@vue-leaflet/vue-leaflet";

export default {
  components: {
    LCircleMarker,
    LGeoJson,
    LIcon,
    LMap,
    // LMarker,
    LTileLayer,
    LTooltip,
  },
  data() {
    return {
      geoOptions: {
        className: 'trail-path',
        color: '#ff0080', // Replace with your gradient colors
        hourOptions: { permanent: true },
        weight: 5, // Adjust the line thickness
        opacity: 0.8, // Adjust the opacity
        shadowBlur: 15, // Add a blur effect for the glow
        shadowColor: '#bb0080', // Set the glow color to match your gradient
      },
      geoJson: {},
      geoJsonMarkers: {},
      localLatitude: 0,
      localLongitude: 0,
      zoom: 15,
    };
  },
  computed: {
    lat() {
      return this.latitude;
    },
    lng() {
      return this.longitude;
    },
  },
  methods: {
    async getGeoJson() {
      try {
        const response = await this.$axiosHelper.get(`/trail/${this.resourceId}/geojson`);
        this.geoJson = response.data;
      } catch (error) {
        console.error('Error fetching geoJson data:', error);
      }
    },
    getGeoJsonMarkers() {
      this.$axiosHelper
        .get('/trail/' + this.resourceId + '/geojson/analyze')
        .then((response) => {
          if (response.data.time_hours) {
            // this.geoJsonMarkers = response.data.time_hours;
            // const map = L.map('map');
            // for (let h = 0; h < this.geoJsonMarkers.length; h++) {
            //     let marker = new L.marker(this.geoJsonMarkers[h]);
            //     marker.bindTooltip(h + 1 + 'h', { permanent: true, className: "hour-label", offset: [0, 0] });
            //     marker.addTo(map);
            // }
          }
        })
        .catch((error) => {
          console.error('Error fetching geoJson markers:', error);
        });
    },
  },
  mounted() {
    this.getGeoJson();
    // this.getGeoJsonMarkers();
  },
  props: {
    latitude: Number,
    longitude: Number,
    resourceType: String,
    resourceId: Number,
  },
};
</script>
