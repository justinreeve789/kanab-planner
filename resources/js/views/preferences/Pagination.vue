<template>
  <div class="flex flex-wrap justify-between gap-6">
    <div class="flex-shrink-0">
      <button class="block min-w-[96px] text-white bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br font-medium rounded-lg text-xs px-5 py-2.5 text-center"
        v-if="step > 1" @click="previous()">
       Previous
      </button>
    </div>
    <div class="flex-shrink-0">
      <button class="block min-w-[96px] text-white bg-gradient-to-r from-indigo-400 to-indigo-600 hover:bg-gradient-to-br font-medium rounded-lg text-xs px-5 py-2.5 text-center"
        v-if="step < 5" @click="next()">
        Next
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
