<template>
  <input type="checkbox" :id="'day-stats-' + day.dayNum" class="modal-toggle" />
  <div class="modal">
    <div class="modal-container">
      <div class="modal-box ass">
<!--      <input class="adventurousness absolute bottom-20 left-0 origin-top -rotate-90" type="range" orient="vertical" min="0" max="100" :value="adventurousness" />-->
<!--      <input class="solitariness absolute bottom-20 left-20 origin-top -rotate-90" type="range" min="0" max="100" orient="vertical" :value="solitariness" />-->
<!--      <input class="strenuousness absolute bottom-20 left-40 origin-top -rotate-90" type="range" orient="vertical" min="0" max="100" :value="strenuousness" />-->
        <div class="flex mb-4">
          <span class="name text-right">Adventurousness</span>
          <input class="adventurousness ml-2 mr-2" type="range" min="0" max="100" v-model="adventurousness" />
          <span class="value badge badge-neutral badge-md">{{ adventurousness }}%</span>
        </div>
        <div class="flex mb-4">
          <span class="name text-right">Solitariness</span>
          <input class="solitariness ml-2 mr-2" type="range" min="0" max="100" v-model="solitariness" />
          <span class="value badge badge-neutral badge-md">{{ solitariness }}%</span>
        </div>
        <div class="flex mb-4">
          <span class="name text-right">Strenuousness</span>
          <input class="strenuousness ml-2 mr-2" type="range" min="0" max="100" v-model="strenuousness" />
          <span class="value badge badge-neutral badge-md">{{ strenuousness }}%</span>
        </div>
        <div class="">
          <p class="mb-4">Adjust the sliders and this day will automatically update with activities as close to your specifications as possible.</p>
          <ul>
            <li class="mb-2"><strong>Adventurousness</strong> is the uniqueness of the experience, and how outside an average person's comfort zone it is. Set this higher if you're feeling like exploring, spending more time outdoors, or maybe trying something new.</li>
            <li class="mb-2"><strong>Solitariness</strong> is how much weight you place on less-crowded areas or non-human-interacting experiences would enjoy it. Set this higher if you want to be alone, or lower if you enjoy the interaction.</li>
            <li class="mb-2"><strong>Strenuousness</strong> is a measure of how physically exerting the experience is. Set this higher if you want more challenging hikes or you're the sporty type.</li>
          </ul>
        </div>
      </div>
    </div>
    <label class="modal-backdrop" :for="'day-stats-' + day.dayNum"></label>
  </div>
</template>

<script>
export default {
  components: {
  },
  computed: {
    //adventurousness() {
    //  return Math.round(this.adventurousness / 100, 2);
    //},
    //solitariness() {
    //  return Math.round(this.solitariness / 100, 2);
    //},
    //strenuousness() {
    //  return Math.round(this.strenuousness / 100, 2);
    //},
  },
  data() {
    return {
      adventurousness: Math.round(this.day.stats.adventurousness * 100),
      solitariness: Math.round(this.day.stats.solitariness * 100),
      strenuousness: Math.round(this.day.stats.strenuousness * 100),
    };
  },
  props: {
    day: Object,
  },
};
</script>