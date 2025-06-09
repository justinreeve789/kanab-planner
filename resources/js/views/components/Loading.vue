<template>
  <div class="progress w-full h-screen -mt-[68px] flex justify-center items-center text-6xl relative">
    <span class="text text-center text-6xl text-white font-bold relative">
      Loading
      <span class="loading loading-dots loading-lg text-white absolute ml-2 bottom-0"></span>
    </span>
    <div class="opacity-75 text-center absolute w-full top-1/2 -translate-1/2">
      <div class="px-8 py-16 italic text-base text-center text-white">
        {{ currentPhrase }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      currentPhrase: '',
      loadingPhraseIntervalId: null,
      loadingPhrases: [
        "Calculating optimal angle for hoodoo admiration...",
        "Training squirrel guides...",
        "Convincing slot canyons to open wider...",
        "Downloading sounds of nature...",
        "Negotiating cooler temperatures...",
        "Initializing starry skies...",
        "Compiling s'mores recipes...",
        "Colorizing red rocks...",
        "Dispatching drones to scout viewpoints...",
        "Wrangling tumbleweeds...",
        "Hydrating water bottles...",
        "Pressure washing hiking boots...",
        "Geolocating hidden oases...",
        "Deploying carrier pigeons in case wifi is down...",
        "Amplifying sense of wonder...",
        "Increasing \"wow\" factor...",
        "Maximizing adventure...",
        "Cataloging desert wildflowers...",
        "Navigating travel trajectories...",
        "Reinforcing hiking poles...",
        "Generating epic sunset palettes...",
        "Calibrating scenic coefficients...",
        "Optimizing trail vectors...",
        "Refactoring instagrammability index...",
        "Nutrientizing trail mix...",
        "Resisting urge to feed wildlife...",
        "Magnetizing compasses...",
        "Assembling park ranger eco-mech...",
        "Translating coyote howls...",
        "Recharging camera batteries...",
        "Combobulating panoramas..."
      ],
      shuffledPhrase: [],
    };
  },
  mounted() {
    this.startCyclingPhrases();
  },
  beforeUnmount() {
    this.stopCyclingPhrases();
  },
  methods: {
    shufflePhrases() {
      this.shuffledPhrases = [...this.loadingPhrases].sort(() => Math.random() - 0.5);
    },
    startCyclingPhrases() {
      this.shufflePhrases();

      this.currentPhrase = this.shuffledPhrases[0];
      let index = 1;
      this.intervalId = setInterval(() => {
        this.currentPhrase = this.shuffledPhrases[index];
        index = (index + 1) % this.shuffledPhrases.length;
      }, Math.random() * 850 + 750);
    },
    stopCyclingPhrases() {
      clearInterval(this.intervalId);
      this.intervalId = null;
    },
  },
};
</script>