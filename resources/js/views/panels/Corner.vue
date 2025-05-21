<template>
  <div class="fixed bottom-4 right-4 z-50">
    <div class="flex flex-col gap-4 items-end">
      <div class="block w-full">
        <QuestionnaireButton />
      </div>
      <button class="block w-full">
        <AssistantButton />
      </button>
    </div>
  </div>
</template>

<script>
import AssistantButton from "../assistant/AssistantButton.vue";
import QuestionnaireButton from "../preferences/QuestionnaireButton.vue";

export default {
  components: {
    AssistantButton,
    QuestionnaireButton
  },
};
</script>