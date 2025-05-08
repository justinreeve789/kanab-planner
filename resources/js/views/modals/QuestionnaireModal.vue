<template>
  <input type="checkbox" id="questionnaire-modal" class="modal-toggle" v-model="preferencesStore.isPreferencesOpen" />
  <div class="modal max-h-[calc(100vh-64px)] md:max-h-full" id="questionnaire-modal">
    <div class="modal-container bg-white max-w-full w-full h-calc(100vh-64px) -translate-x-1/2 md:-translate-y-1/2 top-0 md:w-[90vw] md:h-[90vh] md:top-1/2 md:rounded-[15px] md:border-[5px] md:border-[#d0e0f0]">
      <div class="flex justify-between items-center mb-0 bg-gradient-to-r from-blue-100 to-cyan-200 p-4 pr-2 md:rounded-t-xl w-full">
        <h2 class="text-black text-lg">Questionnaire</h2>
        <div class="flex align-right">
          <button class="btn btn-sm btn-ghost text-black hover:bg-cyan-100 md:flex" :class="hiddenClass">
            <label for="questionnaire-modal">
              <span>✕</span>
            </label>
          </button>
        </div>
      </div>
      <div class="modal-box overflow-x-hidden bg-white text-black w-full max-w-full h-[calc(100vh-64px)] max-h-[calc(100vh-128px)] md:w-full md:h-[calc(100%-4rem)]">
        <Questionnaire />
      </div>
    </div>
  </div>
  <label for="questionnaire-modal" class="modal-backdrop"></label>
</template>

<script>
import Questionnaire from '../preferences/Questionnaire.vue';
import {usePreferencesStore} from "../../stores/preferences";
import Search from "../search/Search.vue";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
    Search,
    Questionnaire,
  },
  computed: {
    step() {
      return this.preferencesStore.currentPreferencesStep ?? 0;
    }
  },
  data() {
    return {
      expanded: false,
      hiddenClass: '',
    };
  },
  mounted() {
    if (this.preferencesStore.currentPreferencesStep === 0) {
      this.preferencesStore.isPreferencesOpen = true;
    }
  },
};
</script>
