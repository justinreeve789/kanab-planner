<template>
  <div class="weather flex items-center gap-2 text-sm md:text-base">
    <div v-if="high && low">
        <span class="temperature">{{ high }}</span>
        <span class="measurement">&deg;</span>
        <span class="unit">F</span>
        <span class="hidden md:inline" v-if="high !== low">
          /
          <span class="temperature">{{ low }}</span>
          <span class="measurement">&deg;</span>
          <span class="unit">F</span>
        </span>
    </div>
    <div v-if="icon">
      <img class="tooltip h-8" :src="icon" :alt="short" :data-tip="short" />
    </div>
  </div>
</template>

<script>
export default {
  props: {
    weather: Object,
  },
  computed: {
    detailed() {
      return this.weather.forecast.detailed;
    },
    high() {
      return this.weather.temperatures.high;
    },
    icon() {
      return this.weather.icon;
    },
    low() {
      return this.weather.temperatures.low;
    },
    short() {
      return this.weather.forecast.short;
    }
  },
};
</script>