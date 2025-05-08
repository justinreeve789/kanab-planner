<template>
  <div class="fixed bottom-4 right-4 z-50">
    <div class="flex flex-col gap-4 items-end">
      <button class="block w-full">
        <QuestionnaireButton />
      </button>
      <button class="block w-full">
        <Assistant />
      </button>
    </div>
  </div>
</template>

<script>
import {useAssistantStore} from '../../stores/assistant';
import {useAuthStore} from "../../stores/auth";
import {usePreferencesStore} from "../../stores/preferences";
import {useSearchStore} from "../../stores/search";
import QuestionnaireButton from "../preferences/QuestionnaireButton.vue";
import Assistant from "../assistant/Assistant.vue";

export default {
  components: {Assistant, QuestionnaireButton},
  setup() {
  },
  data() {
    return {
    };
  },
  methods: {
  },
  mounted() {
  },
  props: {
  },
};
</script>