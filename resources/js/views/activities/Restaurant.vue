<template>
    <div class="resource-details bg-gray-200" data-accordion="collapse" data-type="restaurant" :data-id="restaurant.id">
        <div class="md:flex md:justify-between">
            <button :id="'restaurant-heading-' + activity.hash"
                class="flex items-center p-2 justify-between w-full"
                data-active-classes="bg-blue-100 dark:bg-gray-800 text-blue-600 dark:text-white"
                :data-accordion-target="'#restaurant-body-' + activity.hash"
                aria-expanded="false"
                :aria-controls="'restaurant-body-' + restaurant.id">
                <div class="title flex items-center space-x-2">
                    <div class="icon"><img src="/images/icons/hiking.svg" alt="Hiking"></div>
                    <div class="arrow">
                        <svg data-accordion-icon class="w-3 h-3 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                        </svg>
                    </div>
                    <div class="text-xl text-left">{{ restaurant.name }}</div>
                    <div class="difficulty hidden">
                        <img :src="'/images/icons/difficulty-' + restaurant.difficulty + '.png'" :alt="restaurant.formattedDifficulty">
                    </div>
                </div>
                <div class="stats flex items-center text-right">
                    <span class="distance flex">
                        <img class="icon pr-1 svg-anim" src="/images/icons/distance.svg" alt="Distance" />
                        <span class="amount pr-1">{{ restaurant.distance_miles }}</span>
                        <span class="measurement">mi</span>
                    </span>
                    <span class="incline flex ml-3">
                        <img class="icon pr-1 svg-anim" src="/images/icons/mountain.svg" alt="Elevation Gain" />
                        <span class="amount pr-1">+{{ restaurant.incline_ft }}</span>
                        <span class="measurement">ft</span>
                    </span>
                    <span class="average-grade flex ml-3">
                        <img class="icon pr-1 svg-anim" src="/images/icons/average-grade.svg" alt="Average Grade" />
                        <span class="amount">{{ restaurant.average_grade_pt * 100 }}</span>
                        <span class="measurement">%</span>
                    </span>
                </div>
            </button>
        </div>

        <div :id="'restaurant-body-' + activity.hash" class="hidden bg-gray-200 p-2" :aria-labelledby="'restaurant-heading-' + restaurant.id + '-day-' + day.dayNum + '-activity-'">
            <div class="md:flex md:justify-between">
                <div class="w-full md:w-2/3">
                    <p class="mb-4" v-if="restaurant.description_alt">{{ restaurant.description_alt }}</p>
                    <p class="mb-4" v-if="restaurant.description">{{ restaurant.description }}</p>
                    <p v-if="activity.phrase"><em>{{ activity.phrase }}</em></p>
                </div>
                <div class="w-full md:w-1/3 mt-4 md:mt-0 md:pl-4" v-if="restaurant.images">
                    <Side :resource="restaurant" :type="'restaurant'" :images="restaurant.images" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  created() {
    this.restaurant = this.activity.data;
  },
  props: {
    activity: Object,
    day: Object,
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