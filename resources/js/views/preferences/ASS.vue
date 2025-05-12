<template>
  <div class="p-4 text-center text-lg">
    <p class="font-semibold mb-2">What kind of adventure-seeker are you?</p>
    <p class="mb-4">Adjust the sliders, and your activities will match as close to your preferences as possible.</p>
  </div>
  <div class="sliders origin-center">
    <div class="-mx-6 px-6 pt-4 pb-2 bg-gradient-to-r from-red-50 to-orange-100">
      <div class="flex justify-between items-center -mx-6 px-6 -mt-4 mb-2 py-2 bg-gradient-to-r from-red-100 to-orange-200">
        <div class="font-bold text-black">
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
      <div class="flex mb-4 relative w-full">
        <input class="adventurousness cursor-ns-resize xs:cursor-ew-resize" type="range" min="0" max="1" step="0.1" style="width: 100%;" v-model="preferencesStore.adventurousness" />
        <img class="thumb" src="/images/icons/hiking.svg" :style="adventurousnessIconStyle" />
      </div>
      <div class="h-32">
        <p class="font-bold text-sm">"{{ adventurousnessTitle }}"</p>
        <p class="text-sm">{{ adventurousnessDescription }}</p>
      </div>
    </div>

    <div class="-mx-6 px-6 pt-4 pb-2 bg-gradient-to-r from-green-50 to-cyan-100">
      <div class="flex justify-between items-center -mx-6 px-6 -mt-4 mb-2 py-2 bg-gradient-to-r from-green-100 to-cyan-200">
        <div class="font-bold text-black">
          Solitariness
        </div>
        <div class="tooltip tooltip-left">
          <div class="tooltip-content max-w-[250px] md:max-w-[400px]">
            <p class="text-left">
              <span class="font-bold">Solitariness</span> is how much weight you place on less-crowded areas and non-human interactions. Set this higher if you want to be alone, or lower if you don't mind when an area is a little busy.
            </p>
          </div>
          <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.529 9.988a2.502 2.502 0 1 1 5 .191A2.441 2.441 0 0 1 12 12.582V14m-.01 3.008H12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
          </svg>
        </div>
      </div>
      <div class="flex mb-4 relative w-full">
        <input class="solitariness cursor-ns-resize xs:cursor-ew-resize" type="range" min="0" max="1" step="0.1" style="width: 100%;" v-model="preferencesStore.solitariness" />
        <img class="thumb" src="/images/icons/hiking.svg" :style="solitarinessIconStyle" />
      </div>
      <div class="h-32">
        <p class="font-bold text-sm">"{{ solitarinessTitle }}"</p>
        <p class="text-sm">{{ solitarinessDescription }}</p>
      </div>
    </div>

    <div class="-mx-6 -mb-6 px-6 pt-4 pb-2 bg-gradient-to-r from-blue-50 to-purple-100">
      <div class="flex justify-between items-center -mx-6 px-6 -mt-4 mb-2 py-2 bg-gradient-to-r from-blue-100 to-purple-200">
        <div class="font-bold text-black">
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
      <div class="flex mb-4 relative w-full">
        <input class="strenuousness cursor-ns-resize xs:cursor-ew-resize" type="range" min="0" max="1" step="0.1" style="width: 100%;" v-model="preferencesStore.strenuousness" />
        <img class="thumb" src="/images/icons/hiking.svg" :style="strenuousnessIconStyle" />
      </div>
      <div class="h-32">
        <p class="font-bold text-sm">"{{ strenuousnessTitle }}"</p>
        <p class="text-sm">{{ strenuousnessDescription }}</p>
      </div>
    </div>
  </div>
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
    adventurousnessIconStyle() {
      return 'left: calc(1.5rem + 0.85 * ' + this.adventurousnessPercent + '%);';
    },
    solitarinessIconStyle() {
      return 'left: calc(1.5rem + 0.85 * ' + this.solitarinessPercent + '%);';
    },
    strenuousnessIconStyle() {
      return 'left: calc(1.5rem + 0.85 * ' + this.strenuousnessPercent + '%);';
    },
    adventurousnessPercent() {
      return Math.max(0.01, Math.min(100, Math.round(this.preferencesStore.adventurousness * 100)));
    },
    solitarinessPercent() {
      return Math.max(0.01, Math.min(100, Math.round(this.preferencesStore.solitariness * 100)));
    },
    strenuousnessPercent() {
      return Math.max(0.01, Math.min(100, Math.round(this.preferencesStore.strenuousness * 100)));
    },
    adventurousnessTitle() {
      if (this.preferencesStore.adventurousness < 0.2) {
        return 'I Came for the Gift Shop';
      } else if (this.preferencesStore.adventurousness >= 0.2 && this.preferencesStore.adventurousness < 0.4) {
        return 'I Brake for Scenic Pullouts';
      } else if (this.preferencesStore.adventurousness >= 0.4 && this.preferencesStore.adventurousness < 0.6) {
        return 'The Trail Less Traveled';
      } else if (this.preferencesStore.adventurousness >= 0.6 && this.preferencesStore.adventurousness < 0.8) {
        return 'Let\'s Get Dirty';
      } else if (this.preferencesStore.adventurousness >= 0.8) {
        return 'Did I Just Discover a New Planet?';
      }
    },
    adventurousnessDescription() {
      if (this.preferencesStore.adventurousness < 0.2) {
        return 'You prefer a nice view through the window, and destinations with indoor plumbing. No surprises, thanks.';
      } else if (this.preferencesStore.adventurousness >= 0.2 && this.preferencesStore.adventurousness < 0.4) {
        return 'You admire the wild, and want to make sure you hit the good spots. If it\'s on a postcard, you\'re there, as long as there\'s a paved path.';
      } else if (this.preferencesStore.adventurousness >= 0.4 && this.preferencesStore.adventurousness < 0.6) {
        return 'You\'re happy to chase petroglyphs, ghost towns, or an oddly shaped rock with a good backstory. You\'re down for exploring, as long as it doesn\'t involve ropes, scrambling, or ambiguous forks in the path.';
      } else if (this.preferencesStore.adventurousness >= 0.6 && this.preferencesStore.adventurousness < 0.8) {
        return 'You\'re willing to explore the secret slot canyons and places only the locals know, and aren\'t afraid to get dirty. You\'ll possibly get lost, but you\'ll definitely get a cool story out of it.';
      } else if (this.preferencesStore.adventurousness >= 0.8) {
        return 'You crave the uncharted. If it looks like alien terrain, requires a headlamp, and maybe a backup plan, you\'re already packing your boots.';
      }
    },
    solitarinessTitle() {
      if (this.preferencesStore.solitariness < 0.2) {
        return 'The More the Merrier';
      } else if (this.preferencesStore.solitariness >= 0.2 && this.preferencesStore.solitariness < 0.4) {
        return 'A Few Friendly Faces';
      } else if (this.preferencesStore.solitariness >= 0.4 && this.preferencesStore.solitariness < 0.6) {
        return 'Where is Everybody?';
      } else if (this.preferencesStore.solitariness >= 0.6 && this.preferencesStore.solitariness < 0.8) {
        return 'Just Me and the Squirrels';
      } else if (this.preferencesStore.solitariness >= 0.8) {
        return 'Unplug Me from Society';
      }
    },
    solitarinessDescription() {
      if (this.preferencesStore.solitariness < 0.2) {
        return 'You\'re here for the social experience — swap stories, share snacks, maybe form a hiking gang.';
      } else if (this.preferencesStore.solitariness >= 0.2 && this.preferencesStore.solitariness < 0.4) {
        return 'If you don’t see at least three dogs, five kids, and someone blasting music, its not a hike.';
      } else if (this.preferencesStore.solitariness >= 0.4 && this.preferencesStore.solitariness < 0.6) {
        return 'You\'re okay with crossing paths with someone else, just not every ten steps. Others can exist, as long as they don\'t cramp your zen.';
      } else if (this.preferencesStore.solitariness >= 0.6 && this.preferencesStore.solitariness < 0.8) {
        return 'You want open space and quiet, and maybe a distant human or two to confirm you\'re not in a survival movie.';
      } else if (this.preferencesStore.solitariness >= 0.8) {
        return 'You prefer to be alone in the wilderness, talking to moss. If you see footprints, you\'re walking the other way.';
      }
    },
    strenuousnessTitle() {
      if (this.preferencesStore.strenuousness < 0.2) {
        return 'If It Ain\'t Flat, I Ain\'t Doing That';
      } else if (this.preferencesStore.strenuousness >= 0.2 && this.preferencesStore.strenuousness < 0.4) {
        return 'A Bit of a Puff, Nothing Rough';
      } else if (this.preferencesStore.strenuousness >= 0.4 && this.preferencesStore.strenuousness < 0.6) {
        return 'The Altitude Achiever';
      } else if (this.preferencesStore.strenuousness >= 0.6 && this.preferencesStore.strenuousness < 0.8) {
        return 'Panting with Scenic Interruptions';
      } else if (this.preferencesStore.strenuousness >= 0.8) {
        return 'I Didn\'t Know I Could Sweat There';
      }
    },
    strenuousnessDescription() {
      if (this.preferencesStore.strenuousness < 0.2) {
        return 'If you can do it in flip-flops while holding an iced latte, that’s your pace. The only thing climbing here is your Instagram likes.';
      } else if (this.preferencesStore.strenuousness >= 0.2 && this.preferencesStore.strenuousness < 0.4) {
        return 'You might breathe heavy, but you\’ll still look great doing it.';
      } else if (this.preferencesStore.strenuousness >= 0.4 && this.preferencesStore.strenuousness < 0.6) {
        return 'You want to feel like you did something today, but you also want to walk tomorrow.';
      } else if (this.preferencesStore.strenuousness >= 0.6 && this.preferencesStore.strenuousness < 0.8) {
        return 'You expect to breathe hard, sweat considerably, and maybe even question your life choices.';
      } else if (this.preferencesStore.strenuousness >= 0.8) {
        return 'You don\'t hike mountains, you summit them. Your thighs are forged in stone.';
      }
    },
  },
  data() {
    return {
      // adventurousness: this.preferencesStore.adventurousness,
      // solitariness: this.preferencesStore.solitariness,
      // strenuousness: this.preferencesStore.strenuousness,
    };
  },
  props: {},
};
</script>