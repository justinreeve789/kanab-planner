<template>
  <div>
    <div class="tooltip tooltip-primary tooltip-left hidden md:block" data-tip="Talk to a virtual assistant">
      <button class="btn btn-circle btn-primary h-16 shadow-lg w-16" @click="toggleAssistant">
        <img src="images/icons/virtual-assistant.png"/>
      </button>
    </div>
  </div>
</template>

<script>
import {useAssistantStore} from '../../stores/assistant.js';

export default {
  setup() {
    const store = useAssistantStore();
    return {store};
  },
  data() {
    return {
      isOpen: false,
    };
  },
  methods: {
    toggleAssistant() {
      this.store.isAssistantOpen = !this.store.isAssistantOpen;
      // if (this.store.isAssistantOpen) {
      //   this.$addHashToUrl('#assistant');
      // } else {
      //   this.$removeHashFromUrl();
      // }
    },
  }
};
</script>
