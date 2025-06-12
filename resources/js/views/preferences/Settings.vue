<template>
  <div class="max-w-2xl text-gray-600">
    <div class="max-w-lg mx-auto absolute left-1/2 -translate-x-1/2">
      <div>
        <label for="preferences-show-activity-times" class="label text-lg flex items-center p-2 space-x-2">
          <input id="preferences-show-activity-times" class="checkbox" type="checkbox" v-model="localShowActivityTimes" @change="updateShowActivityTimes" />
          Show approximate activity times
        </label>
      </div>
      <div class="mb-6">
        <label for="preferences-show-traveling-tasks" class="label text-lg flex items-center p-2 space-x-2">
          <input id="preferences-show-traveling-tasks" class="checkbox" type="checkbox" v-model="localShowTravelingTasks" @change="updateShowTravelingTasks" />
          Show driving distances
        </label>
      </div>
    </div>
  </div>
</template>

<script>
export default {
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
