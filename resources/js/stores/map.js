import { defineStore } from 'pinia'

export const useMapStore = defineStore('map', {
  state: () => ({
    isMapOpen: false,
    resourceType: '',
    resourceId: null,
    geoJson: {},
    geoJsonMarkers: [],
    latitude: null,
    longitude: null,
    mapStyle: null,
    x: null,
    y: null,
    zoom: null,
  }),
  actions: {
    setResource(resource) {
      this.resourceType = resource.type;
      this.resourceId = resource.id;
      this.latitude = resource.latitude ?? '';
      this.longitude = resource.longitude ?? '';
      this.zoom = 15;
    },
  }
})
