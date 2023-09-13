<template>
  <div>
    <label for="startDate">Start Date:</label>
    <datepicker v-model="startDate" id="startDate"></datepicker>

    <label for="endDate">End Date:</label>
    <datepicker v-model="endDate" id="endDate"></datepicker>
  </div>
</template>

<script>
import Datepicker from 'vuejs-datepicker';

export default {
  components: {
    Datepicker,
  },
  data() {
    return {
      startDate: null,
      endDate: null,
    };
  },
};
</script>