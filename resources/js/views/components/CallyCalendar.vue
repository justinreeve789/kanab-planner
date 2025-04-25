<template>
    <calendar-range class="cally bg-base-100 border border-base-300 shadow-lg rounded-box w-full md:w-fit" :months="1" :value="rangeString" @change="changeDates">
      <div class="card card-border bg-base-100 card-lg shadow-sm md:flex md:flex-row space-y-8 md:space-x-8 md:space-y-0">
        <div class="flex flex-col">
          <calendar-month></calendar-month>
        </div>
        <div class="flex flex-col">
          <calendar-month :offset="1"></calendar-month>
        </div>
      </div>
    </calendar-range>
</template>

<script>
import 'cally';

export default {
  props: {
    modelValue: {
      type: Object,
      default: () => ({ start: '', end: '' }),
    },
  },
  emits: ['update:modelValue'],
  computed: {
    rangeString() {
      const { start, end } = this.modelValue || {};
      return start && end ? `${start}/${end}` : '';
    },
  },
  methods: {
    changeDates(event) {
      const value = event?.target?.value || '';
      const [start, end] = value.split('/'); // Split the range at the slash

      // Emit start and end dates as an object
      this.$emit('update:modelValue', {
        start: start || '',
        end: end || '',
      });
    },
  },
};
</script>