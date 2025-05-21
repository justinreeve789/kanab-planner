<template>
  <div class="tooltip tooltip-primary tooltip-left hidden md:block" data-tip="Answer the questionnaire">
    <router-link class="btn btn-circle btn-primary h-16 shadow-lg w-16" :to="{ name: 'questionnaire' }">
      <img src="images/icons/questionnaire.png"/>
    </router-link>
  </div>
</template>

<script>
import {usePreferencesStore} from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
  },
  data() {
    return {
    };
  },
  methods: {
    toggleQuestionnaireModal() {
      this.preferencesStore.currentPreferencesStep = 1;
      this.preferencesStore.isPreferencesOpen = !this.preferencesStore.isPreferencesOpen;
    },
  },
  mounted() {
  },
};
</script>
