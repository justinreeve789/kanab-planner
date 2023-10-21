<template>
  <div class="collapse collapse-arrow bg-white">
    <input type="checkbox" />
    <div class="collapse-title text-xl font-medium">
      <h2 :id="'day-heading-' + day.dayNum" class="day-heading relative w-full">
        <div class="flex bg-white items-center justify-between w-full font-medium text-left text-gray-500">
          <div class="day-title p-4 justify-end">
            <span class="day md:inline font-large text-3xl">Day {{ day.day_num + 1 }}</span>
            <span class="separator hidden md:inline md:text-lg"> - </span>
            <span class="area font-large md:inline text-md md:text-3xl md:inline-block block">{{ day.area.name }}</span>
          </div>
          <div class="flex-1 p-4 items-start justify-end">
            <label :for="'day-stats-' + day.dayNum" class="graph" v-if="day.stats">
              <div class="gradient-line adventurousness relative z-10" v-if="adventurousness" :style="'height: ' + adventurousness + '%;'"></div>
              <div class="gradient-line solitariness relative z-10" v-if="solitariness" :style="'height: ' + solitariness + '%;'"></div>
              <div class="gradient-line strenuousness relative z-10" v-if="strenuousness" :style="'height: ' + strenuousness + '%;'"></div>
            </label>
          </div>
          <div class="p-4 justify-end text-md text-right">
            <span class="date-day-of-week hidden md:inline">{{ day.formatted_day_of_week }}</span>
            <span class="pr-1 hidden md:inline">,</span>
            <span class="date-month-day sm:hidden">{{ day.formatted_abbr_month_day }}</span>
            <span class="date-month-day hidden sm:inline">{{ day.formatted_month_day }}</span>
            <span class="pr-1 hidden md:inline">,</span>
            <span class="date-year block md:inline">{{ day.formatted_year }}</span>
          </div>
        </div>
      </h2>
    </div>

    <div :id="'day-body-' + day.dayNum" class="collapse-content bg-white w-full">
      <div v-if="day.preamble">
        <p class="mb-8">{{ day.preamble }}</p>
      </div>
      <section v-for="(period, periodType) in day.periods" :key="periodType">
        <div class="timeline"></div>
        <div :class="'day-content pt-4 period-' + period.type" v-for="(activity, index) in period.activities" :key="index">
          <div class="mb-8" v-if="activity.activityType == 'event'">
            <Event :activity="activity" :day="day" />
          </div>
          <div class="mb-8" v-if="activity.activityType == 'dining'">
            <Restaurant :activity="activity" :day="day" />
          </div>
          <div class="mb-8" v-else-if="activity.activityType == 'hiking'">
            <Trail :unique="activity.hash" :phrase="activity.phrase" :resource="activity.data" />
          </div>
          <div class="mb-8" v-else>
            <Task :activity="activity" :day="day" />
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script>
import Event from './activities/Event.vue';
import Location from './activities/Location.vue';
import Restaurant from './activities/Restaurant.vue';
import Task from './activities/Task.vue';
import Trail from './activities/Trail.vue';

export default {
  components: {
    Event,
    Location,
    Restaurant,
    Task,
    Trail,
  },
  computed: {
    adventurousness() {
      return Math.max(0.01, Math.min(100, Math.round(this.day.stats.adventurousness * 100)));
    },
    solitariness() {
      return Math.max(0.01, Math.min(100, Math.round(this.day.stats.solitariness * 100)));
    },
    strenuousness() {
      return Math.max(0.01, Math.min(100, Math.round(this.day.stats.strenuousness * 100)));
    },
  },
  methods: {
    abbreviateMonth(month) {
        return month.substr(0, 3);
    },
    tap() {
        alert('Tapped');
    },
    nextDay(item, i) {
        return function(direction, mouseEvent) {
            console.log("Swiped item ", i, ": ", item, " in direction ", direction);
            alert("Swiped item " + i + ": " + item + " in direction " + direction);
        };
    },
    previousDay(item, i) {
        return function(direction, mouseEvent) {
            console.log("Swiped item ", i, ": ", item, " in direction ", direction);
            alert("Swiped item " + i + ": " + item + " in direction " + direction);
        };
    },
  },
  props: {
    day: Object,
  },
};
</script>

<script setup>
</script>
