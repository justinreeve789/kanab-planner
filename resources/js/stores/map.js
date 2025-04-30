import { defineStore } from 'pinia'

export const useMapStore = defineStore('map', {
  state: () => ({
    isMapOpen: false,
    resource_type: '',
    resource_id: null,
    geoJson: {},
    geoJsonMarkers: [],
    lat: null,
    lng: null,
    mapStyle: null,
    x: null,
    y: null,
    zoom: null,
  }),
  actions: {
    setResource(resource) {
      this.resource_type = resource.type;
      this.resource_id = resource.id;
      this.lat = resource.latitude;
      this.lng = resource.longitude;
      this.zoom = 15;
    },
  }
})
