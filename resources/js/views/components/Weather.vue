<template>
  <div class="weather">
<!--    <div v-if="forecast.short = 'Sunny'">-->
<!--    </div>-->
<!--    <div v-if="forecast.short = 'Mostly Clear'">-->
<!--    </div>-->
<!--    <div v-else>-->
<!--    </div>-->

    <div v-if="temperatures.high && temperatures.low">
        <span class="temperature">{{ temperatures.high }}</span>
        <span class="measurement">&deg;</span>
        <span class="unit">F</span>
        /
        <span class="temperature">{{ temperatures.low }}</span>
        <span class="measurement">&deg;</span>
        <span class="unit">F</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    forecast: Object,
    temperatures: Object,
  },
};
</script>