<template>
  <input type="checkbox" id="preferences-modal" class="modal-toggle" v-model="preferencesStore.isPreferencesOpen" />
  <div class="modal max-h-[calc(100vh-64px)] md:max-h-full" id="preferences-modal">
    <div class="modal-container bg-white max-w-full w-full h-calc(100vh-64px) -translate-x-1/2 md:-translate-y-1/2 top-0 md:max-w-[640px] md:h-auto md:top-1/2 md:rounded-[15px] md:border-[5px] md:border-[#d0e0f0]">
      <div class="flex justify-between items-center mb-0 bg-gradient-to-r from-blue-100 to-cyan-200 p-4 pr-2 md:rounded-t-xl w-full">
        <h2 class="text-black text-lg">Preferences</h2>
        <div class="flex align-right">
          <button class="btn btn-sm btn-ghost hover:bg-cyan-100 text-black hidden md:flex">
            <label for="preferences-modal">
              <span>✕</span>
            </label>
          </button>
        </div>
      </div>
      <div class="modal-box ass overflow-x-hidden bg-white text-black w-full max-w-full h-[calc(100vh-64px)] max-h-[calc(100vh-128px)] md:max-w-[640px] md:h-auto md:max-h-[90vh]">
        <ASS />
      </div>
    </div>
  </div>
  <label for="preferences-modal" class="modal-backdrop hidden md:block"></label>
</template>

<script>
import { usePreferencesStore } from "../../stores/preferences";
import ASS from "../preferences/ASS.vue";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
    ASS,
  },
  data() {
    return {
    };
  },
  props: {},
};
</script>