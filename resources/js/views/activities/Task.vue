<template>
    <div class="resource-details bg-gray-200" data-accordion="collapse" data-type="task" :data-hash="activity.hash">
        <div class="md:flex md:justify-between">
            <button :id="'task-heading-' + activity.hash"
                class="flex items-center p-2 justify-between w-full"
                data-active-classes="bg-blue-100 dark:bg-gray-800 text-blue-600 dark:text-white"
                :data-accordion-target="'#task-body-' + activity.hash"
                aria-expanded="true"
                :aria-controls="'task-body-' + task.id">
                <div class="title flex items-center space-x-2">
                    <div class="icon"><img src="/images/icons/hiking.svg" alt="Hiking"></div>
                    <div class="arrow">
                        <svg data-accordion-icon class="w-3 h-3 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5 5 1 1 5"/>
                        </svg>
                    </div>
                    <div class="text-xl text-left">{{ activity.name }}</div>
                </div>
                <div class="stats flex items-center text-right">
                </div>
            </button>
        </div>

        <div :id="'task-body-' + activity.hash" class="hidden bg-gray-200 p-2" :aria-labelledby="'task-heading-' + task.id + '-day-' + day.dayNum + '-activity-'">
            <div class="md:flex md:justify-between">
                <div class="w-full md:w-2/3">
                    <p class="mb-4" v-if="activity.description">{{ activity.description }}</p>
                    <p v-if="activity.phrase"><em>{{ activity.phrase }}</em></p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  created() {
    this.task = this.activity.data;
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