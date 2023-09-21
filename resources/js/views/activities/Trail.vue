<template>
    <div class="resource-details" data-accordion="collapse">
        <div class="md:flex md:justify-between">
            <button :id="'trail-heading-' + trail.id"
                class="flex items-center p-2 justify-between w-full"
                data-active-classes="bg-blue-100 dark:bg-gray-800 text-blue-600 dark:text-white"
                :data-accordion-target="'#trail-body-' + trail.id"
                aria-expanded="false"
                :aria-controls="'trail-body-' + trail.id">
                <div class="title flex items-center space-x-2">
                    <div class="arrow">
                        <svg data-accordion-icon class="w-3 h-3 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                        </svg>
                    </div>
                    <div class="text-xl">{{ trail.name }}</div>
                    <div class="difficulty hidden">
                        <img :src="'/images/icons/difficulty-' + trail.difficulty + '.png'" :alt="trail.formattedDifficulty">
                    </div>
                </div>
                <div class="stats flex items-center text-right">
                    <span class="distance flex">
                        <img class="icon pr-1 svg-anim" src="/images/icons/distance.svg" alt="Distance" />
                        <span class="amount pr-1">{{ trail.distance_miles }}</span>
                        <span class="measurement">mi</span>
                    </span>
                    <span class="incline flex ml-3">
                        <img class="icon pr-1 svg-anim" src="/images/icons/mountain.svg" alt="Elevation Gain" />
                        <span class="amount pr-1">+{{ trail.incline_ft }}</span>
                        <span class="measurement">ft</span>
                    </span>
                    <span class="average-grade flex ml-3">
                        <img class="icon pr-1 svg-anim" src="/images/icons/average-grade.svg" alt="Average Grade" />
                        <span class="amount">{{ trail.average_grade_pt * 100 }}</span>
                        <span class="measurement">%</span>
                    </span>
                </div>
            </button>
        </div>

        <div :id="'trail-body-' + trail.id" class="hidden p-2" :aria-labelledby="'trail-heading-' + trail.id">
            <div class="md:flex md:justify-between">
                <div class="w-2/3">
                    <p class="mb-4" v-if="trail.description_alt">{{ trail.description_alt }}</p>
                    <p class="mb-4" v-if="trail.description">{{ trail.description }}</p>
                    <p v-if="activity.phrase"><em>{{ activity.phrase }}</em></p>
                </div>
                <div class="w-1/3 pl-4" v-if="trail.images">
                    <Side :resource="trail" :type="'trail'" v-if="trail.images" />
                </div>
            </div>
        </div>
    </div>
<!--
    <div class="trail-map">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d96522.36423741286!2d-112.9271579498936!3d37.29713303136258!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zN7CsMTUwJzE3LjEiTiAxMTLCsDQyJzA2LjMiVw!5e0!3m2!1sen!2sus!4v1628604616011!5m2!1sen!2sus" width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>
-->
</template>

<script>
import tailwindConfig from 'tailwindcss/defaultConfig';

export default {
  data() {
    return {
      trail: null,
      tailwindConfig: tailwindConfig.theme.screens,
    };
  },
  created() {
    this.trail = this.activity.data;
  },
  computed: {
    isMd() {
      const windowWidth = window.innerWidth;
      return windowWidth >= this.tailwindConfig['md']; // Check if the window width is greater than or equal to the md breakpoint.
    },
  },
  props: {
    activity: Object,
  },
};
</script>

<script setup>
import { onMounted } from 'vue'
import {
    initAccordions,
} from 'flowbite'
import Side from '../panels/Side.vue'

// initialize components based on data attribute selectors
onMounted(() => {
    initAccordions();
})
</script>