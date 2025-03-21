<template>
    <div v-if="forecast.short = 'Sunny'">
    </div>
    <div v-if="forecast.short = 'Mostly Clear'">
    </div>
    <div v-else>
    </div>

    <div v-if="forecast.high && forecast.low">
        <span class="temperature">{{ forecast.high }}</span>
        <span class="measurement">&deg;</span>
        <span class="unit">F</span>
        /
        <span class="temperature">{{ forecast.low }}</span>
        <span class="measurement">&deg;</span>
        <span class="unit">F</span>
    </div>
</template>

<script>
export default {
  props: {
    forecast: Object,
  },
};
</script>