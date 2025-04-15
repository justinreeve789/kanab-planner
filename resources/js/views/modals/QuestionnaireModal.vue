<template>
  <div class="modal" :id="'questionnaire'">
    <div class="modal-box p-2 max-w-none w-full" style="height: 100%;">
        <Questionnaire />
    </div>
  </div>
  <label :for="'questionnaire'" class="modal-backdrop"></label>
</template>

<script>
import Questionnaire from '../questionnaire/Questionnaire.vue';

export default {
  components: {
      Questionnaire,
  },
  data() {
    return {
      expanded: false,
    };
  },
  props: {
  },
};
</script>
