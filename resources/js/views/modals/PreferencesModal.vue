<template>
  <input type="checkbox" id="preferences-modal" class="modal-toggle" v-model="preferencesStore.isPreferencesOpen" />
  <div class="modal" id="preferences-modal">
    <div class="modal-container w-full sm:w-auto">
      <div class="flex justify-between items-center mb-0 bg-gradient-to-r from-blue-100 to-cyan-200 p-4 pr-2 md:rounded-t-xl w-full">
        <h2 class="text-lg">Preferences</h2>
        <div class="flex align-right">
          <button class="btn btn-sm btn-ghost hover:bg-cyan-100">
            <label for="preferences-modal">
              <span>✕</span>
            </label>
          </button>
        </div>
      </div>
      <div class="modal-box ass w-full max-w-full sm:max-w-lg">
        <div class="sliders -rotate-90 xs:rotate-0 origin-center mt-24 mb-20 xs:my-0">
          <div class="flex mb-8 xs:mb-4">
            <span class="name text-right rotate-45 xs:rotate-0 origin-right">Adventurousness</span>
            <input class="adventurousness ml-2 mr-2 cursor-ns-resize xs:cursor-ew-resize" type="range" min="0" max="1" step="0.05" v-model="adventurousness" />
            <span class="value badge badge-neutral badge-md rotate-90 xs:rotate-0">{{ (adventurousness * 100).toFixed(0) }}%</span>
          </div>
          <div class="flex mb-8 xs:mb-4">
            <span class="name text-right rotate-45 xs:rotate-0 origin-right">Solitariness</span>
            <input class="solitariness ml-2 mr-2 cursor-ns-resize xs:cursor-ew-resize" type="range" min="0" max="1" step="0.05" v-model="solitariness" />
            <span class="value badge badge-neutral badge-md rotate-90 xs:rotate-0">{{ (solitariness * 100).toFixed(0) }}%</span>
          </div>
          <div class="flex xs:mb-4">
            <span class="name text-right rotate-45 xs:rotate-0 origin-right">Strenuousness</span>
            <input class="strenuousness ml-2 mr-2 cursor-ns-resize xs:cursor-ew-resize" type="range" min="0" max="1" step="0.05" v-model="strenuousness" />
            <span class="value badge badge-neutral badge-md rotate-90 xs:rotate-0">{{ (strenuousness * 100).toFixed(0) }}%</span>
          </div>
        </div>
        <div class="text-sm md:text-base">
          <p class="mb-4">Adjust the sliders and this day will automatically update with activities as close to your specifications as possible.</p>
          <ul>
            <li class="mb-2"><strong>Adventurousness</strong> is the uniqueness of the experience, and how outside an average person's comfort zone it is. Set this higher if you're feeling like exploring, spending more time outdoors, or maybe trying something new.</li>
            <li class="mb-2"><strong>Solitariness</strong> is how much weight you place on less-crowded areas or non-human-interacting experiences would enjoy it. Set this higher if you want to be alone, or lower if you enjoy the interaction.</li>
            <li class="mb-2"><strong>Strenuousness</strong> is a measure of how physically exerting the experience is. Set this higher if you want more challenging hikes or you're the sporty type.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <label for="preferences-modal" class="modal-backdrop"></label>
</template>

<script>
import { usePreferencesStore } from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
  },
  components: {
  },
  computed: {
    adventurousnessPercent() {
      return Math.max(0.01, Math.min(100, Math.round(this.adventurousness * 100)));
    },
    solitarinessPercent() {
      return Math.max(0.01, Math.min(100, Math.round(this.solitariness * 100)));
    },
    strenuousnessPercent() {
      return Math.max(0.01, Math.min(100, Math.round(this.strenuousness * 100)));
    },
  },
  data() {
    return {
      adventurousness: this.preferencesStore.adventurousness,
      solitariness: this.preferencesStore.solitariness,
      strenuousness: this.preferencesStore.strenuousness,
    };
  },
  props: {
  },
};
</script>