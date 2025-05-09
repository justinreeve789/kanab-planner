<template>
  <div class="h-full -mx-8">
    <div class="hero h-full" style="background-image: url('images/sand-caves.jfif');" v-if="step === 0">
      <div class="hero-overlay"></div>
      <div class="hero-content px-8 text-neutral-content text-center">
        <div class="max-w-md">
          <h1 class="mb-8 text-5xl font-bold">Ready to Plan Your Adventure?</h1>
          <p class="mb-8">
            Dreaming of exploring the breathtaking landscapes and unique experiences that Kanab has to offer? We're excited to help you plan the perfect visit!
          </p>
          <div class="flex items-center justify-center">
            <button class="block min-w-[128px] w-full sm:w-auto text-white bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br font-medium rounded-lg text-md px-5 py-2.5 text-center"
              @click="preferencesStore.currentPreferencesStep = 1">
              Let's Get Started!
            </button>
          </div>
          <p class="text-center text-sm text-white mt-8">
            <a class="underline" href="#">No thanks, just create a random itinerary with basic settings</a>
          </p>
        </div>
      </div>
    </div>
    <div v-else>
      <div class="sticky -top-6 z-10 -mt-8 bg-white h-[80px]">
        <Steps class="py-2" :step="step" v-model="preferencesStore.currentPreferencesStep" />
      </div>
      <div class="h-full overflow-hidden pt-4">
<!--      <Pagination :step="step" v-model="preferencesStore.currentPreferencesStep" />-->
      <div class="h-full w-full flex flex-cols justify-center overflow-x-hidden overflow-y-auto" v-if="step !== 0">
        <div v-show="step === 1">
          <ActivityTypes />
        </div>
        <div class="ass mx-auto max-w-[640px] px-8" v-show="step === 2">
          <ASS />
        </div>
        <div v-if="step === 3">
          <Destinations />
        </div>
        <div v-if="step === 4">
          <Complete />
        </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ActivityTypes from "./ActivityTypes.vue";
import Destinations from "./Destinations.vue";
import Pagination from "./Pagination.vue";
import Steps from "./Steps.vue";
import ASS from "./ASS.vue";
import Complete from "./Complete.vue";
import {usePreferencesStore} from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
    ActivityTypes,
    ASS,
    Complete,
    Destinations,
    Pagination,
    Steps,
  },
  watch: {
    'preferencesStore.currentPreferencesStep'(newVal, oldVal) {
      if (newVal !== oldVal) {
        this.step = this.preferencesStore.currentPreferencesStep;
      }
    },
  },
  computed: {
    step() {
      return this.preferencesStore.currentPreferencesStep;
    },
  },
  data() {
    return {
      direction: null,
    };
  },
  props: {
  },
  methods: {
  },
  mounted() {
  },
};
</script>
