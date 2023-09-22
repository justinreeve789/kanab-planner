<template>
  <div class="day" data-accordion="open">
    <h2 :id="'day-heading-' + day.dayNum" class="day-heading relative w-full" :aria-labelledby="'day-heading-' + day.dayNum">
        <button type="button" class="flex bg-white items-center justify-between w-full font-medium text-left text-gray-500 border border-b-0 border-gray-200 rounded-t-xl focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-800 dark:border-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" :data-accordion-target="'#day-body-' + day.dayNum" aria-expanded="true" :aria-controls="'day-body-' + day.dayNum">
            <div class="day-title p-4 justify-end">
                <span class="day md:inline font-large text-3xl">Day {{ day.dayNum + 1 }}</span>
                <span class="separator hidden md:inline md:text-lg"> - </span>
                <span class="area font-large md:inline text-md md:text-3xl md:inline-block block">{{ day.area.name }}</span>
            </div>
            <div class="flex-1 p-4 items-start justify-end">
                <div class="graph" v-if="day.stats">
                    <div class="gradient-line adventurousness" v-if="day.stats.adventurousness" :style="'height: ' + (day.stats.adventurousness * 100) + '%;'"></div>
                    <div class="gradient-line solitariness" v-if="day.stats.solitariness" :style="'height: ' + (day.stats.solitariness * 100) + '%;'"></div>
                    <div class="gradient-line strenuousness" v-if="day.stats.strenuousness" :style="'height: ' + (day.stats.strenuousness * 100) + '%;'"></div>
                </div>
            </div>
            <div class="p-4 justify-end text-md text-right">
                <span class="date-day-of-week hidden md:inline">{{ day.formattedDayOfWeek }}</span>
                <span class="pr-1 hidden md:inline">,</span>
                <span class="date-month-day">{{ day.formattedMonthDay }}</span>
                <span class="pr-1 hidden md:inline">,</span>
                <span class="date-year block md:inline">{{ day.formattedYear }}</span>
            </div>
        </button>
    </h2>

    <div :id="'day-body-' + day.dayNum" class="bg-white w-full p-4 hidden" :aria-labelledby="'day-header-' + day.dayNum">
        <div v-for="(period, periodType) in day.periods" :key="periodType">
            <div class="timeline"></div>
            <div :class="'day-content pt-4 period-' + period.type" v-for="(activity, index) in period.activities" :key="index">
                <div class="mb-8" v-if="activity.activityType == 'dining'">
                    <Restaurant :restaurant="activity.data" />
                </div>
                <div class="mb-8" v-else-if="activity.activityType == 'hiking'">
                    <Trail :activity="activity" :day="day" />
                </div>
                <div class="mb-8" v-else>
                    <h3 class="text-2xl">{{ activity.name }}</h3>
                    <p>{{ activity.phrase }}</p>
                    <p>{{ activity.description }}</p>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script>
import Event from './activities/Event.vue';
import Location from './activities/Location.vue';
import Restaurant from './activities/Restaurant.vue';
import Trail from './activities/Trail.vue';

export default {
  components: {
    Event,
    Location,
    Restaurant,
    Trail,
  },
  mounted() {
    initAccordions();
  },
  props: {
    day: Object, // The day data passed as a prop
  },
};
</script>

<script setup>
import {
    initAccordions,
} from 'flowbite'
</script>