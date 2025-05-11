<template>
  <div class="weather flex gap-2">
    <div v-if="high && low">
        <span class="temperature">{{ high }}</span>
        <span class="measurement">&deg;</span>
        <span class="unit">F</span>
        <span v-if="high !== low">
          /
          <span class="temperature">{{ low }}</span>
          <span class="measurement">&deg;</span>
          <span class="unit">F</span>
        </span>
    </div>
    <div v-if="short">
      <div v-if="short === 'Sunny'">
        <img class="h-8" src="images/icons/weather/sunny.png" alt="Sunny" />
      </div>
      <div v-if="short === 'Mostly Sunny'">
        <img class="h-8" src="images/icons/weather/mostly-sunny.png" alt="Mostly Sunny" />
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
  mounted() {
    console.log(this.weather.forecast);
  },
};
</script>