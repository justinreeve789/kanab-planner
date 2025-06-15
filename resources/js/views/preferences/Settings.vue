<template>
  <div class="max-w-2xl text-gray-600">
    <div class="max-w-lg mx-auto absolute left-1/2 -translate-x-1/2">
      <div>
        <label for="preferences-show-activity-times" class="label text-lg flex items-center p-2 space-x-2">
          <input id="preferences-show-activity-times" class="checkbox" type="checkbox" v-model="settings.showActivityTimes" @change="updateSetting('showActivityTimes', $event.target.checked)" />
          Show approximate activity times
        </label>
      </div>
      <div>
        <label for="preferences-show-traveling-tasks" class="label text-lg flex items-center p-2 space-x-2">
          <input id="preferences-show-traveling-tasks" class="checkbox" type="checkbox" v-model="settings.showTravelingTasks" @change="updateSetting('showTravelingTasks', $event.target.checked)" />
          Show driving distances
        </label>
      </div>
      <div>
        <label for="preferences-show-weather-forecasts" class="label text-lg flex items-center p-2 space-x-2">
          <input id="preferences-show-weather-forecasts" class="checkbox" type="checkbox" v-model="settings.showWeatherForecasts" @change="updateSetting('showWeatherForecasts', $event.target.checked)" />
          Show weather forecasts
        </label>
      </div>
<!--      <div class="p-4">-->
<!--        <div class="divider"></div>-->
<!--        <p class="mb-4 text-lg">How many minutes does it take you to hike 1 mile on a continuous gentle incline (estimate)?</p>-->
<!--        <div class="flex justify-center items-center text-2xl">-->
<!--          <input class="input bg-white text-black px-4 text-2xl w-24" type="number" step="5" min="5" max="500" v-model="settings.estimatedMinutesToHikeOneMile" />-->
<!--          <span class="ml-2 text-2xl">minutes</span>-->
<!--        </div>-->
<!--      </div>-->
    </div>
  </div>
</template>

<script>
import { usePreferencesStore } from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  data() {
    return {
      settings: {
        estimatedMinutesToHikeOneMile: 30,
        showActivityTimes: true,
        showTravelingTasks: true,
        showWeatherForecasts: true,
      },
      cookieMap: {
        estimatedMinutesToHikeOneMile: 'estimated_minutes_to_hike_one_mile',
        showActivityTimes: 'show_activity_times',
        showTravelingTasks: 'show_traveling_tasks',
        showWeatherForecasts: 'show_weather_forecasts',
      },
    };
  },
  watch: {
    'settings.estimatedMinutesToHikeOneMile'(newVal) {
      if (newVal < 5) {
        newVal = 5;
      } else if (newVal > 500) {
        newVal = 500;
      }
      this.preferencesStore.averageHikingSpeed = Math.max(0.1, Math.round((60 / newVal) * 10) / 10);
      this.$cookies.set('estimated_minutes_to_hike_one_mile', newVal);
    },
  },
  mounted() {
    for (const key in this.settings) {
      if (Object.hasOwnProperty.call(this.settings, key)) {
        const cookieName = this.cookieMap[key];
        let cookieValue = this.$cookies.get(cookieName);

        // If the cookie isn't found or is undefined/null, set the default and store it. Otherwise, use the cookie's
        // value (converting from string to boolean).
        if (cookieValue === null || cookieValue === undefined) {
          this.$cookies.set(cookieName, this.settings[key]);
        } else {
          this.settings[key] = cookieValue;
        }
      }
    }
  },
  methods: {
    updateSetting(settingName, value) {
      if (this.settings.hasOwnProperty(settingName) && this.cookieMap.hasOwnProperty(settingName)) {
        this.settings[settingName] = value;
        const cookieName = this.cookieMap[settingName];
        this.$nextTick(() => {
          this.$cookies.set(cookieName, value);
        });
      } else {
        console.warn(`Attempted to update unknown setting: ${settingName}`);
      }
    },
  },
};
</script>
