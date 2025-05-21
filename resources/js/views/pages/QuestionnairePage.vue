<template>
  <div class="hero h-[calc(100vh-64px)] overflow-hidden" style="background-image: url('images/sand-caves.jfif');" v-if="$route.path === '/questionnaire'">
    <Start />
  </div>
  <div class="bg-white overflow-x-hidden px-6 pt-[32px] md:mx-8 md:mb-8 md:px-16 md:pb-16" v-else>
    <Questionnaire :step="step" />
  </div>
</template>

<script>
import Questionnaire from "../preferences/Questionnaire.vue";
import Start from "../preferences/Start.vue";
import {usePreferencesStore} from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
    Start,
    Questionnaire,
  },
  props: {
    step: {
      type: Number,
      default: 0,
    },
  },
  mounted() {
    this.preferencesStore.currentPreferencesStep = this.step;
  },
};
</script>