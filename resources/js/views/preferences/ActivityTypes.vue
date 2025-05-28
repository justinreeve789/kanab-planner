<template>
  <div class="p-4 text-center text-lg">
    <p class="font-semibold mb-2">Which activities are you interested in?</p>
  </div>
  <fieldset class="fieldset mx-auto max-w-[320px] text-lg">
    <label class="fieldset-label">
      <input type="checkbox" class="checkbox text-black" value="all" v-model="selectAll" />
      <span class="font-semibold bg-white text-black">I'm Up For Anything! (select all)</span>
    </label>
    <label class="fieldset-label bg-white text-black" v-for="(activityType, index) in activityTypes" :key="index">
      <input
        type="checkbox"
        class="checkbox text-black"
        :value="activityType.name"
        v-model="selectedActivityTypes"
      />
      {{ activityType.description }}
    </label>
  </fieldset>

  <div class="max-w-[640px] mb-8 p-4" v-if="isHikingSelected">
    <div class="divider"></div>
    <p class="mb-4 text-lg">How many minutes does it take you to hike 1 mile on a continuous gentle incline (estimate)?</p>
    <div class="flex justify-center items-center text-2xl">
      <input class="input bg-white text-black px-4 text-2xl w-24" type="number" step="5" min="5" max="500" v-model="estimatedMinutesToHikeOneMile" />
      <span class="ml-2 text-2xl">minutes</span>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import {usePreferencesStore} from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
  },
  data() {
    return {
      activityTypes: [],
      estimatedMinutesToHikeOneMile: 30,
      selectAll: true,
      selectedActivityTypes: [],
    };
  },
  watch: {
    estimatedMinutesToHikeOneMile(newVal) {
      if (newVal < 5) {
        newVal = 5;
      } else if (newVal > 500) {
        newVal = 500;
      }
      this.preferencesStore.averageHikingSpeed = Math.max(0.1, Math.round((60 / newVal) * 10) / 10);
    },
    selectedActivityTypes(newVal) {
      this.preferencesStore.preferredActivityTypes = newVal;
    },
    selectAll(newVal) {
      if (newVal === true) {
        this.selectAllActivityTypes();
      } else {
        this.deselectAllActivityTypes();
      }
    },
  },
  computed: {
    isHikingSelected() {
      return this.selectedActivityTypes.includes('hiking');
    },
  },
  methods: {
    getActivityTypes() {
      axios
        .get('/api/v1/activity_type')
        .then((response) => {
          this.activityTypes = response.data;
          this.selectAllActivityTypes();
        })
        .catch((error) => {
          console.error('Error fetching activity types:', error);
        });
    },
    deselectAllActivityTypes() {
      this.selectedActivityTypes = [];
    },
    selectAllActivityTypes() {
      this.selectedActivityTypes = [];
      for (let i = 0; i < this.activityTypes.length; i++) {
        this.selectedActivityTypes.push(this.activityTypes[i].name);
      }
    },
  },
  mounted() {
    this.getActivityTypes();
  },
};
</script>
