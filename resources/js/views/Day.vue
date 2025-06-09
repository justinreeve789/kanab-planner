<template>
    <div class="rounded-lg p-[2px] bg-gradient-to-r from-orange-100 to-red-100">
      <div class="rounded-lg p-[1px] bg-gradient-to-r from-orange-200 to-red-200">
        <div class="collapse collapse-arrow bg-white">
          <input type="checkbox" v-touch="toggleEditing" />
          <div class="collapse-title font-medium py-0 md:py-3">
            <h2 :id="'day-heading-' + day.day_num" class="day-heading relative w-full">
              <div class="flex bg-white items-center justify-between w-full font-medium text-left text-gray-500">
                <div class="day-title md:p-4">
                  <span class="day font-large text-xl md:inline md:text-3xl">Day {{ day.day_num + 1 }}</span>
                  <span class="separator hidden md:inline md:text-xl"> - </span>
                  <span class="area font-large md:inline md:text-3xl md:inline-block block">
                    <span class="hidden xl:inline">
                      {{ day.area.name }}
                    </span>
                    <span class="xl:hidden">
                      {{ day.area.short_name }}
                    </span>
                  </span>
                </div>
                <div class="flex justify-end items-center flex-grow">
                  <Weather class="text-right" :weather="day.weather" />
                  <div class="p-4 text-sm md:text-lg text-right">
                    <span class="date-day-of-week hidden md:inline">{{ day.formatted_day_of_week }}</span>
                    <span class="pr-1 hidden md:inline">,</span>
                    <span class="date-month-day sm:hidden">{{ day.formatted_abbr_month_day }}</span>
                    <span class="date-month-day hidden sm:inline">{{ day.formatted_month_day }}</span>
                    <span class="pr-1 hidden md:inline">,</span>
                    <span class="date-year block md:inline">{{ day.formatted_year }}</span>
                  </div>
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
              <div :class="'day-content pt-4 relative period-' + period.type" v-for="(activity, index) in period.activities" :key="index">
                <div class="time text-sm absolute top-[-2px] right-4 opacity-50 md:text-base md:top-[-4px] md:left-4" :class="activityColor(activity)">
                  {{ formatTime(activity.start_time) }}
                </div>
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
                <div class="mb-8 border border-solid border-blue-300 rounded-lg" v-if="activity.name">
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
import Weather from "./components/Weather.vue";
import {usePreferencesStore} from "../stores/preferences";
import { SwipeList, SwipeOut } from "@ahultgren/vue3-swipe-actions";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
    Accommodation,
    Event,
    Location,
    Restaurant,
    Task,
    Trail,
    Weather,
    SwipeOut,
    SwipeList,
  },
  data() {
    return {
      mockSwipeList: [
        {
          id: 0,
          title: "Some title",
          description: "some description",
        },
        {
          id: 1,
          title: "Some title",
          description: "some description",
        },
        {
          id: 2,
          title: "Some title",
          description: "some description",
        },
      ],
      touchDelaySecondsToTrigger: 0.75,
    };
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
    isEditing() {
      return this.preferencesStore.isEditing;
    },
    revealFirstRight() {
      this.$refs.list.revealRight(0);
    },
    revealFirstLeft() {
      this.$refs.list.revealLeft(0);
    },
    closeFirst() {
      this.$refs.list.closeActions(0);
    },
    closeAll() {
      this.$refs.list.closeActions();
    },
    remove(item) {
      this.mockSwipeList = this.mockSwipeList.filter((i) => i !== item);
      // console.log(e, 'remove');
    },
    itemClick(e) {
      console.log(e, "item click");
    },
    fbClick(e) {
      console.log(e, "First Button Click");
    },
    sbClick(e) {
      console.log(e, "Second Button Click");
    },
  },
  methods: {
    abbreviateMonth(month) {
        return month.substr(0, 3);
    },
    activityColor(activity) {
      console.log(activity);
      if (activity.activity_type && activity.activity_type.name) {
        switch (activity.activity_type.name) {
          case 'checking-in':
          case 'checking-out':
            return 'text-blue-700';
          case 'dining':
            return 'text-green-700';
          case 'event':
            return 'text-gray-700';
          case 'hiking':
            return 'text-orange-700';
        }
      }
      return 'text-blue-700';
    },
    formatTime(time) {
      const [hours, minutes, seconds] = time.split(':');
      const date = new Date();
      date.setHours(parseInt(hours));
      date.setMinutes(parseInt(minutes));
      date.setSeconds(parseInt(seconds));
      const formattedTime = date.toLocaleTimeString('en-us', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      });
      return formattedTime.replace(/AM$/, 'am').replace(/PM$/, 'pm');
    },
    handleBookmark(e) {
      console.log(e);
    },
    handleDelete(e) {
      console.log(e);
    },
    nextDay(item, i) {
        return function(direction, mouseEvent) {
            console.log("Swiped item ", i, ": ", item, " in direction ", direction);
        };
    },
    previousDay(item, i) {
        return function(direction, mouseEvent) {
            console.log("Swiped item ", i, ": ", item, " in direction ", direction);
        };
    },
    toggleEditing(e) {
      this.preferencesStore.isEditing = !this.preferencesStore.isEditing;
    },
  },
  props: {
    day: Object,
  },
};
</script>
