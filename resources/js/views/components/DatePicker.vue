<template>
  <div>
    <label for="startDate">Start Date:</label>
    <label for="endDate">End Date:</label>
  </div>
</template>

<script>
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

export default {
  components: {
    VueDatePicker,
  },
  data() {
    return {
      startDate: null,
      endDate: null,
    };
  },
};
</script>