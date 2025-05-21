<template>
  <div class="bg-white overflow-x-hidden h-[calc(100vh-128px)] md:mx-8 md:mb-8 md:hidden">
    <Assistant />
  </div>
</template>

<script>
import Assistant from "../assistant/Assistant.vue";
import {useAssistantStore} from "../../stores/assistant";

export default {
  components: {
    Assistant,
  },
  setup() {
    const assistantStore = useAssistantStore();
    return { assistantStore };
  },
}
</script>