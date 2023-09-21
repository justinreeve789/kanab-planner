<template>
    <div class="resource-details" data-accordion="collapse">
        <div class="md:flex md:justify-between">
            <button :id="'restaurant-heading-' + restaurant.id"
                class="flex items-center p-2 justify-between w-full"
                data-active-classes="bg-blue-100 dark:bg-gray-800 text-blue-600 dark:text-white"
                :data-accordion-target="'#restaurant-body-' + restaurant.id"
                aria-expanded="false"
                :aria-controls="'restaurant-body-' + restaurant.id">
                <div class="title flex items-center space-x-2">
                    <div class="arrow">
                        <svg data-accordion-icon class="w-3 h-3 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                        </svg>
                    </div>
                    <div class="text-xl text-left">{{ restaurant.name }}</div>
                </div>
                <div class="stats flex items-center text-right">
                    <span class="distance flex">
                        <img class="icon pr-1 svg-anim" src="/images/icons/distance.svg" alt="Distance" />
                        <span class="amount pr-1">{{ restaurant.distance_miles }}</span>
                        <span class="measurement">mi</span>
                    </span>
                    <span class="incline flex ml-3">
                        <img class="icon pr-1 svg-anim" src="/images/icons/mountain.svg" alt="Elevation Gain" />
                        <span class="amount pr-1">{{ restaurant.incline_ft }}</span>
                        <span class="measurement">$</span>
                    </span>
                </div>
            </button>
        </div>

        <div :id="'restaurant-body-' + restaurant.id" class="hidden p-2" :aria-labelledby="'restaurant-heading-' + restaurant.id">
            <div class="md:flex md:justify-between">
                <div class="w-2/3">
                    {{ restaurant.description }}
                </div>
                <div class="w-1/3 pl-4" v-if="restaurant.location.images">
                    <Side :resource="restaurant" :type="'restaurant'" :images="restaurant.location.images" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import tailwindConfig from 'tailwindcss/defaultConfig';

export default {
  data() {
    return {
      tailwindConfig: tailwindConfig.theme.screens,
    };
  },
  computed: {
    isMd() {
      const windowWidth = window.innerWidth;
      return windowWidth >= this.tailwindConfig['md']; // Check if the window width is greater than or equal to the md breakpoint.
    },
  },
  props: {
    restaurant: Object,
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