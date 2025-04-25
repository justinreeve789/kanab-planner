<template>
  <input type="checkbox" id="preferences-modal" class="modal-toggle" v-model="preferencesStore.isPreferencesOpen" />
  <div class="modal" id="preferences-modal">
    <div class="modal-container bg-white w-full sm:w-auto">
      <div class="flex justify-between items-center mb-0 bg-gradient-to-r from-blue-100 to-cyan-200 p-4 pr-2 md:rounded-t-xl w-full">
        <h2 class="text-black text-lg">Preferences</h2>
        <div class="flex align-right">
          <button class="btn btn-sm btn-ghost hover:bg-cyan-100 text-black">
            <label for="preferences-modal">
              <span>✕</span>
            </label>
          </button>
        </div>
      </div>
      <div class="modal-box ass overflow-x-hidden max-h-[90vh] w-full max-w-full sm:max-w-lg">
        <p class="mb-4 text-sm">Adjust the sliders, and your activities will match as close to your preferences as possible.</p>
        <div class="sliders origin-center">
          <div class="-mx-6 px-6 pt-4 pb-2 bg-gradient-to-r from-red-50 to-orange-100">
            <div class="flex justify-between items-center -mx-6 px-6 -mt-4 mb-2 py-2 bg-gradient-to-r from-red-100 to-orange-200">
              <div class="font-bold text-black text-md">
                Adventurousness
              </div>
              <div class="tooltip tooltip-left">
                <div class="tooltip-content max-w-[250px] md:max-w-[400px]">
                  <p class="text-left">
                    <span class="font-bold">Adventurousness</span> is the uniqueness of the experience, and how outside an average person's comfort zone it is. Set this higher if you're feeling like exploring, spending more time outdoors, or maybe trying something new.
                  </p>
                </div>
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.529 9.988a2.502 2.502 0 1 1 5 .191A2.441 2.441 0 0 1 12 12.582V14m-.01 3.008H12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
              </div>
            </div>
            <div class="flex mb-4 w-full">
              <input class="adventurousness cursor-ns-resize xs:cursor-ew-resize" type="range" min="0" max="1" step="0.1" style="width: 100%;" v-model="adventurousness" />
              <span class="value badge badge-neutral badge-md hidden">{{ (adventurousness * 100).toFixed(0) }}%</span>
            </div>
            <div class="h-32">
              <p class="font-bold text-sm">"{{ adventurousnessTitle }}"</p>
              <p class="text-sm">{{ adventurousnessDescription }}</p>
            </div>
          </div>

          <div class="-mx-6 px-6 pt-4 pb-2 bg-gradient-to-r from-green-50 to-cyan-100">
            <div class="flex justify-between items-center -mx-6 px-6 -mt-4 mb-2 py-2 bg-gradient-to-r from-green-100 to-cyan-200">
              <div class="font-bold text-black text-md">
                Solitariness
              </div>
              <div class="tooltip tooltip-left">
                <div class="tooltip-content max-w-[250px] md:max-w-[400px]">
                  <p class="text-left">
                    <span class="font-bold">Solitariness</span> is how much weight you place on less-crowded areas and non-human interactions. Set this higher if you want to be alone, or lower if you don't mind when areas a little busy.
                  </p>
                </div>
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.529 9.988a2.502 2.502 0 1 1 5 .191A2.441 2.441 0 0 1 12 12.582V14m-.01 3.008H12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
              </div>
            </div>
            <div class="flex mb-4 w-full">
              <input class="solitariness cursor-ns-resize xs:cursor-ew-resize" type="range" min="0" max="1" step="0.1" style="width: 100%;" v-model="solitariness" />
              <span class="value badge badge-neutral badge-md hidden">{{ (solitariness * 100).toFixed(0) }}%</span>
            </div>
            <div class="h-32">
              <p class="font-bold text-sm">"{{ solitarinessTitle }}"</p>
              <p class="text-sm">{{ solitarinessDescription }}</p>
            </div>
          </div>

          <div class="-mx-6 px-6 pt-4 pb-2 bg-gradient-to-r from-blue-50 to-purple-100">
            <div class="flex justify-between items-center -mx-6 px-6 -mt-4 mb-2 py-2 bg-gradient-to-r from-blue-100 to-purple-200">
              <div class="font-bold text-black text-md">
                Strenuousness
              </div>
              <div class="tooltip tooltip-left">
                <div class="tooltip-content max-w-[250px] md:max-w-[400px]">
                  <p class="text-left">
                    <span class="font-bold">Strenuousness</span> is a measure of how physically exerting the experience is. Set this higher if you want more challenging hikes or you're the sporty type.
                  </p>
                </div>
                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.529 9.988a2.502 2.502 0 1 1 5 .191A2.441 2.441 0 0 1 12 12.582V14m-.01 3.008H12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
              </div>
            </div>
            <div class="flex mb-4 w-full">
              <input class="strenuousness cursor-ns-resize xs:cursor-ew-resize" type="range" min="0" max="1" step="0.1" style="width: 100%;" v-model="strenuousness" />
              <span class="value badge badge-neutral badge-md hidden">{{ (strenuousness * 100).toFixed(0) }}%</span>
            </div>
            <div class="h-32">
              <p class="font-bold text-sm">"{{ strenuousnessTitle }}"</p>
              <p class="text-sm">{{ strenuousnessDescription }}</p>
            </div>
          </div>
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
    adventurousnessTitle() {
      if (this.adventurousness < 0.2) {
        return 'I Came for the Gift Shop';
      } else if (this.adventurousness >= 0.2 && this.adventurousness < 0.4) {
        return 'I Brake for Scenic Pullouts';
      } else if (this.adventurousness >= 0.4 && this.adventurousness < 0.6) {
        return 'The Trail Less Traveled';
      } else if (this.adventurousness >= 0.6 && this.adventurousness < 0.8) {
        return 'Let\'s Get Dirt On Our Shoes';
      } else if (this.adventurousness >= 0.8) {
        return 'Did I Just Discover a New Planet?';
      }
    },
    adventurousnessDescription() {
      if (this.adventurousness < 0.2) {
        return 'You prefer a nice view through the window, and destinations with indoor plumbing. No surprises, thanks.';
      } else if (this.adventurousness >= 0.2 && this.adventurousness < 0.4) {
        return 'You admire the wild, and want to make sure you hit the good spots. If it\'s on a postcard, you\'re there, as long as there\'s a paved path.';
      } else if (this.adventurousness >= 0.4 && this.adventurousness < 0.6) {
        return 'You\'re happy to chase petroglyphs, ghost towns, or an oddly shaped rock with a good backstory. You\'re down for exploring, as long as it doesn\'t involve ropes, scrambling, or ambiguous forks in the path.';
      } else if (this.adventurousness >= 0.6 && this.adventurousness < 0.8) {
        return 'You\'re willing to explore the local legends and secret slot canyons, and aren\'t afraid to get dirty. You\'ll possibly get lost, but you\'ll definitely get a cool story out of it.';
      } else if (this.adventurousness >= 0.8) {
        return 'You crave the uncharted. If it looks like alien terrain, requires a headlamp, and maybe a backup plan, you\'re already packing your boots.';
      }
    },
    solitarinessTitle() {
      if (this.solitariness < 0.2) {
        return 'The More the Merrier';
      } else if (this.solitariness >= 0.2 && this.solitariness < 0.4) {
        return 'A Few Friendly Faces';
      } else if (this.solitariness >= 0.4 && this.solitariness < 0.6) {
        return 'Where is Everybody?';
      } else if (this.solitariness >= 0.6 && this.solitariness < 0.8) {
        return 'Just Me and the Squirrels';
      } else if (this.solitariness >= 0.8) {
        return 'Unplug Me from Society';
      }
    },
    solitarinessDescription() {
      if (this.solitariness < 0.2) {
        return 'You\'re here for the social experience — swap stories, share snacks, maybe form a hiking gang.';
      } else if (this.solitariness >= 0.2 && this.solitariness < 0.4) {
        return 'If you don’t see at least three dogs, five kids, and someone blasting music, its not a hike.';
      } else if (this.solitariness >= 0.4 && this.solitariness < 0.6) {
        return 'You\'re okay with crossing paths with someone else, just not every ten steps. Others can exist, as long as they don\'t cramp your zen.';
      } else if (this.solitariness >= 0.6 && this.solitariness < 0.8) {
        return 'You want open space and quiet, but a distant human or two to confirm you\'re not in a survival movie.';
      } else if (this.solitariness >= 0.8) {
        return 'You prefer to be alone in the wilderness, talking to moss. If you see footprints, you\'re walking the other way.';
      }
    },
    strenuousnessTitle() {
      if (this.strenuousness < 0.2) {
        return 'I Came for the Gift Shop';
      } else if (this.strenuousness >= 0.2 && this.strenuousness < 0.4) {
        return 'I Brake for Scenic Pullouts';
      } else if (this.strenuousness >= 0.4 && this.strenuousness < 0.6) {
        return 'The Trail Less Traveled';
      } else if (this.strenuousness >= 0.6 && this.strenuousness < 0.8) {
        return 'Let\'s Get Dirt On Our Shoes';
      } else if (this.strenuousness >= 0.8) {
        return 'Did I Just Discover a New Planet?';
      }
    },
    strenuousnessDescription() {
      if (this.strenuousness < 0.2) {
        return 'You prefer a nice view through the window, and destinations with indoor plumbing. No surprises, thanks.';
      } else if (this.strenuousness >= 0.2 && this.strenuousness < 0.4) {
        return 'You admire the wild, and want to make sure you hit the good spots. If it\'s on a postcard, you\'re there, as long as there\'s a paved path.';
      } else if (this.strenuousness >= 0.4 && this.strenuousness < 0.6) {
        return 'You\'re happy to chase petroglyphs, ghost towns, or an oddly shaped rock with a good backstory. You\'re down for exploring, as long as it doesn\'t involve ropes, scrambling, or ambiguous forks in the path.';
      } else if (this.strenuousness >= 0.6 && this.strenuousness < 0.8) {
        return 'You\'re willing to explore the local legends and secret slot canyons, and aren\'t afraid to get dirty. You\'ll possibly get lost, but you\'ll definitely get a cool story out of it.';
      } else if (this.strenuousness >= 0.8) {
        return 'You crave the uncharted. If it looks like alien terrain, requires a headlamp, and maybe a backup plan, you\'re already packing your boots.';
      }
    },
  },
  data() {
    return {
      adventurousness: this.preferencesStore.adventurousness,
      solitariness: this.preferencesStore.solitariness,
      strenuousness: this.preferencesStore.strenuousness,
    };
  },
  props: {},
};
</script>