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
    <div v-if="short">
      <div v-if="short === 'Sunny'">
        <img class="tooltip h-8" src="images/icons/weather/sunny.png" alt="Sunny" :data-tip="short" />
      </div>
      <div v-if="short === 'Mostly Sunny'">
        <img class="h-8" src="images/icons/weather/mostly-sunny.png" alt="Mostly Sunny" />
      </div>
      <div v-if="short === 'Mostly Clear'">
        <img class="h-8" src="images/icons/weather/mostly-clear.png" alt="Mostly Clear" />
      </div>
      <div v-else>
      </div>
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
    low() {
      return this.weather.temperatures.low;
    },
    short() {
      return this.weather.forecast.short;
    }
  },
};
</script>