<template>
  <div class="rounded-lg p-[2px] bg-gradient-to-r from-orange-100 to-red-100">
    <div class="rounded-lg p-[1px] bg-gradient-to-r from-orange-200 to-red-200">
    <div class="collapse collapse-arrow bg-white">
    <input type="checkbox" />
    <div class="collapse-title text-xl font-medium">
      <h2 :id="'day-heading-' + day.day_num" class="day-heading relative w-full">
        <div class="flex bg-white items-center justify-between w-full font-medium text-left text-gray-500">
          <div class="day-title p-4 justify-end">
            <span class="day md:inline font-large text-3xl">Day {{ day.day_num + 1 }}</span>
            <span class="separator hidden md:inline md:text-lg"> - </span>
            <span class="area font-large md:inline text-md md:text-3xl md:inline-block block">{{ day.area.name }}</span>
          </div>
<!--
          <div class="flex-1 p-4 items-start justify-end">
            <label :for="'day-stats-' + day.day_num" class="graph" v-if="day.stats">
              <div class="gradient-line adventurousness relative z-10" v-if="adventurousness" :style="'height: ' + adventurousness + '%;'"></div>
              <div class="gradient-line solitariness relative z-10" v-if="solitariness" :style="'height: ' + solitariness + '%;'"></div>
              <div class="gradient-line strenuousness relative z-10" v-if="strenuousness" :style="'height: ' + strenuousness + '%;'"></div>
            </label>
          </div>
-->
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

    <div :id="'day-body-' + day.day_num" class="collapse-content bg-white w-full">
      <div v-if="day.preamble">
        <p class="mb-8">{{ day.preamble }}</p>
      </div>
      <section v-for="(period, periodType) in day.periods" :key="periodType">
        <div class="timeline"></div>
        <div :class="'day-content pt-4 period-' + period.type" v-for="(activity, index) in period.activities" :key="index">
          <div class="mb-8 border border-solid border-blue-300 rounded-lg" v-if="activity.activity_type && (activity.activity_type.name == 'checking-in' || activity.activity_type.name == 'checking-out')">
            <Accommodation :activity="activity" :day="day" :resource="activity.resource" />
          </div>
          <div class="mb-8 border border-solid border-gray-300 rounded-lg" v-else-if="activity.activity_type && activity.activity_type.name == 'event'">
            <Event :activity="activity" :day="day" />
          </div>
          <div class="mb-8 border border-solid border-green-300 rounded-lg" v-else-if="activity.activity_type && activity.activity_type.name == 'dining'">
            <Restaurant :phrase="activity.phrase" :activity="activity" :resource="activity.resource" />
          </div>
          <div class="mb-8 border border-solid border-orange-300 rounded-lg" v-else-if="activity.activity_type && activity.activity_type.name == 'hiking'">
            <Trail :phrase="activity.phrase" :activity="activity" :resource="activity.resource" />
          </div>
          <div class="mb-8 border border-solid border-blue-300 rounded-lg" v-else>
            <Task :activity="activity" />
          </div>
        </div>
      </section>
    </div>
  </div>
  </div>
  </div>
</template>

<script>
import Accommodation from "./activities/Accommodation.vue";
import Event from './activities/Event.vue';
import Location from './activities/Location.vue';
import Restaurant from './activities/Restaurant.vue';
import Task from './activities/Task.vue';
import Trail from './activities/Trail.vue';

export default {
  components: {
    Accommodation,
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
