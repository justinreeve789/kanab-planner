<template>
  <div class="h-[calc(100vh-128px)] -mx-8">
    <div>
      <div class="sticky -top-6 z-10 -mt-8 bg-white h-[128px]" style="border-bottom: solid 1px #c0c0c0;">
        <Steps class="py-2" :step="step" v-model="preferencesStore.currentPreferencesStep" />
        <Pagination :step="step" v-model="stepRoute" />
      </div>
      <div class="h-full overflow-hidden pt-6">
        <div class="h-full w-full flex flex-cols justify-center overflow-x-hidden overflow-y-auto" v-if="step !== 0">
          <div class="min-w-[500px]" v-show="step === 1">
            <Dates />
          </div>
          <div v-show="step === 2">
            <ActivityTypes />
          </div>
          <div class="ass mx-auto max-w-[640px] px-8" v-show="step === 3">
            <ASS />
          </div>
          <div v-if="step === 4">
            <Destinations />
          </div>
          <div v-if="step === 5">
            <Complete />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import ActivityTypes from "./ActivityTypes.vue";
import ASS from "./ASS.vue";
import Complete from "./Complete.vue";
import Dates from "./Dates.vue";
import Destinations from "./Destinations.vue";
import Pagination from "./Pagination.vue";
import Start from "./Start.vue";
import Steps from "./Steps.vue";
import { Swiper, SwiperSlide } from 'swiper/vue';
import { usePreferencesStore } from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    const onSwiper = (swiper) => {
      console.log(swiper);
    };
    const onSlideChange = () => {
      console.log('slide change');
    };
    return {
      onSwiper,
      onSlideChange,
      preferencesStore,
    };
  },
  components: {
    ActivityTypes,
    ASS,
    Complete,
    Dates,
    Destinations,
    Pagination,
    Start,
    Steps,
    Swiper,
    SwiperSlide,
  },
  // watch: {
  //   'preferencesStore.currentPreferencesStep'(newVal, oldVal) {
  //     if (newVal !== oldVal) {
  //       this.step = this.preferencesStore.currentPreferencesStep;
  //     }
  //   },
  // },
  computed: {
    // step() {
    //   return this.preferencesStore.currentPreferencesStep;
    // },
  },
  data() {
    return {
      direction: null,
      stepRoute: null,
    };
  },
  props: {
    step: Number,
  },
  methods: {
    closePreferences() {
      this.preferencesStore.isPreferencesOpen = false;
    },
  },
  mounted() {
  },
};
</script>
