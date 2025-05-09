<template>
    <fieldset class="fieldset p-4 border border-base-300 text-lg">
        <legend class="fieldset-legend bg-white text-black">Which activities are you interested in?</legend>
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
      selectAll: true,
      selectedActivityTypes: [],
    };
  },
  watch: {
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
