<template>
</template>

<script>
import { usePreferencesStore } from "../../../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
  },
  computed: {
  },
  methods: {
    getAccommodation() {
      try {
        console.log(this.preferencesStore.itineraryData);
        const activity = this.preferencesStore.itineraryData.days[0].periods[0].activities[0];
        console.log(activity);
      } catch (e) {
        console.error(e);
      }
    },
  },
  created() {
  },
  mounted() {
    console.log('accommodation');
    this.getAccommodation();
  },
  data() {
    return {
    }
  },
  props: {
    activity: Object,
  },
};
</script>