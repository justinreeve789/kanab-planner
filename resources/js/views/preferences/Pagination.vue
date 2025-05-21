<template>
  <div class="flex flex-wrap gap-6 justify-between md:justify-center">
    <div class="flex-shrink-0 md:px-8">
      <button class="block min-w-[96px] text-center text-xs text-white font-medium rounded-lg px-5 py-2.5 min-w-[96px] md:min-w-[128px] md:text-sm"
        @click="previous()" :class="step > 1 ? 'bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br' : 'btn btn-primary'" :disabled="step === 1">
        <span class="hidden mr-2 sm:inline">&lt;&lt;</span>
        Previous
      </button>
    </div>
    <div class="flex-shrink-0 md:px-8">
      <button class="block min-w-[96px] text-center text-xs text-white font-medium rounded-lg px-5 py-2.5 min-w-[96px] md:min-w-[128px] md:text-sm"
        @click="next()" :class="step < 5 ? 'bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br' : 'btn btn-primary'" :disabled="step === 5">
        Next
        <span class="hidden ml-2 sm:inline">&gt;&gt;</span>
      </button>
    </div>
  </div>
</template>

<script>
import ViewMap from "../buttons/ViewMap.vue";
import AddToItinerary from "../buttons/AddToItinerary.vue";
import AskQuestion from "../buttons/AskQuestion.vue";

export default {
  emits: ['update:modelValue'],
  components: {
    AskQuestion, AddToItinerary, ViewMap
  },
  props: {
    step: Number,
  },
  data() {
    return {
    };
  },
  methods: {
    next() {
      this.$router.push(this.stepRoute(this.step + 1));
    },
    previous() {
      this.$router.push(this.stepRoute(this.step - 1));
    },
    stepRoute(step) {
      switch (step) {
        case 1:
          return 'questionnaire-dates';
        case 2:
          return 'questionnaire-activities';
        case 3:
          return 'questionnaire-about';
        case 4:
          return 'questionnaire-destinations';
        case 5:
          return 'questionnaire-complete';
        default:
          return 'questionnaire';
      }
    },
  },
};
</script>
