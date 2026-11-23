<template>
  <div class="progress w-full h-[calc(100vh-64px)] flex justify-center items-center text-6xl relative" role="status" aria-live="polite">
    <span class="text text-center text-4xl sm:text-6xl text-stone-700 font-bold relative">
      Building your itinerary
      <span class="loading loading-dots loading-lg text-orange-600 absolute ml-2 bottom-0"></span>
    </span>
    <div class="opacity-75 text-center absolute w-full top-1/2 -translate-1/2">
      <div class="px-8 py-16 italic text-base text-center text-stone-600">
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
        "Calculating optimal angle for admiring hoodoos...",
        "Training squirrel guides...",
        "Convincing slot canyons to open wider...",
        "Downloading sounds of nature...",
        "Negotiating with the desert for cooler temperatures...",
        "Initializing starry skies...",
        "Analyzing s'mores recipes...",
        "Reminding red rocks to stay extra red...",
        "Dispatching drones to scout viewpoints...",
        "Wrangling tumbleweeds...",
        "Priming water bottles...",
        "Searching for hidden oases...",
        "Deploying carrier pigeons in case Wi-Fi is down...",
        "Amplifying sense of wonder...",
        "Increasing \"Wow\" factor...",
        "Setting adventure to maximum...",
        "Cataloging desert wildflowers...",
        "Cleaning mud off hiking boots...",
        "Reinforcing hiking poles...",
        "Generating epic sunset palettes...",
        "Calibrating scenic coefficients...",
        "Optimizing trail vectors...",
        "Compiling Instagrammability index...",
        "Assembling trail mix...",
        "Resisting urge to feed wildlife...",
        "Magnetizing compass array...",
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
      }, Math.random() * 500 + 850);
    },
    stopCyclingPhrases() {
      clearInterval(this.intervalId);
      this.intervalId = null;
    },
  },
};
</script>