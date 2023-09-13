<template>
    <h1></h1>
</template>

<script>
import Event from './activities/Event.vue';
import Map from './panels/Map.vue';
import Photo from './panels/Photo.vue';
import Restaurant from './activities/Restaurant.vue';
import Trail from './activities/Trail.vue';
import Weather from './panels/Weather.vue';

export default {
  components: {
    Event,
    Map,
    Photo,
    Restaurant,
    Trail,
    Weather,
  },
};
</script>