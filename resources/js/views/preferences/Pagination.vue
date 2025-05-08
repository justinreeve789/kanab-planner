<template>
  <div class="flex flex-wrap justify-center items-center gap-6 mt-8">
    <button class="block min-w-[128px] w-full sm:w-auto text-white bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br font-medium rounded-lg text-xs px-5 py-2.5 text-center"
      v-if="step > 1" @click="previous()">
      Previous
    </button>
    <button class="block min-w-[128px] w-full sm:w-auto text-white bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br font-medium rounded-lg text-xs px-5 py-2.5 text-center"
      v-if="step < 4" @click="next()">
      Next
    </button>
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
      this.$emit('update:modelValue', this.step + 1);
    },
    previous() {
      this.$emit('update:modelValue', this.step - 1);
    },
  },
  mounted() {
  },
};
</script>
