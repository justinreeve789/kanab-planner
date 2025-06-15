<template>
  <div class="p-4 text-center text-lg">
    <div class="hero-content text-center">
      <div class="max-w-2xl text-gray-600">
        <h1 class="font-bold mb-6 text-5xl">Ready for an Adventure?</h1>
        <p class="max-w-lg mb-6">
          Now we'll create an itinerary just for you. Feel free to return here to adjust your preferences and automatically create a new itinerary if you want to change anything. You may also make manual changes on the itinerary itself.
        </p>
        <div class="max-w-lg mx-auto absolute left-1/2 -translate-x-1/2">
          <div class="flex justify-center items-center">
            <button class="block w-full sm:w-auto text-white bg-gradient-to-r from-green-400 to-green-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-700 font-medium rounded-lg text-md px-5 py-2.5 text-center"
                    @click="createItinerary">
              Create itinerary
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {usePreferencesStore} from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  computed: {
    showActivityTimes() {
      return this.$cookies.get('show_activity_times');
    },
  },
  data() {
    return {
      localShowActivityTimes: true,
      localShowTravelingTasks: true,
    };
  },
  mounted() {
    this.localShowActivityTimes = this.$cookies.get('show_activity_times');
    this.localShowTravelingTasks = this.$cookies.get('show_traveling_tasks');
    if (!this.localShowActivityTimes) {
      this.localShowActivityTimes = true;
      this.$cookies.set('show_activity_times', true);
    }
    if (!this.localShowTravelingTasks) {
      this.localShowTravelingTasks = true;
      this.$cookies.set('show_traveling_tasks', true);
    }
  },
  methods: {
    createItinerary() {
      this.$cookies.remove('active_itinerary_id');
      this.preferencesStore.refreshItinerary();
      this.preferencesStore.refresh = true;
      this.$router.push('/');
    },
    updateShowActivityTimes(e) {
      this.$nextTick(() => {
        this.$cookies.set('show_activity_times', e.target._modelValue);
      });
    },
    updateShowTravelingTasks(e) {
      this.$nextTick(() => {
        this.$cookies.set('show_traveling_tasks', e.target._modelValue);
      });
    },
  },
};
</script>
