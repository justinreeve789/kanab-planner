<template>
  <div :data-id="resource.id" class="resource-details collapse collapse-arrow" data-type="trail">
    <input v-model="expanded" type="checkbox"/>
    <div
      class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-orange-100 to-red-100">
      <div class="title w-1/2 flex items-center space-x-2 text-left">
        <div class="icon pr-1 shrink-0">
          <img alt="Hiking" src="/images/icons/hiking.svg">
        </div>
        <div class="text-xl">{{ resource.name }}</div>
        <div class="difficulty hidden">
          <img :alt="resource.formattedDifficulty" :src="'/images/icons/difficulty-' + resource.difficulty + '.png'">
        </div>
      </div>
      <div class="flex space-x-4 items-center mr-8 h-full">
        <TrailStats :align="'right'" :trail="resource" class="hidden lg:flex"/>
        <label for="trail-list">
          <span class="icon btn btn-sm btn-ghost relative z-10 h-full" @click="select">
            <img alt="Change Activity" src="/images/icons/edit.svg">
          </span>
        </label>
      </div>
    </div>

    <div
      v-if="expanded"
      class="collapse-content min-h-[150px] items-stretch w-full bg-gray-200 p-2 bg-gradient-to-r from-white to-red-50"
    >
      <div class="lg:flex lg:justify-between">
        <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
          <div class="sm:flex sm:flex-row justify-between lg:flex-col lg:order-2">
            <ActivityButtons
              :resource="resource"
              :resource_table="'trails'"
              class="w-full order-2 sm:w-1/2 text-right my-4 justify-end lg:justify-start lg:w-full"
              type="trail"
            />
            <TrailStats
              :align="'center'"
              :trail="resource"
              class="w-full sm:w-1/2 order-1 my-4 sm:pt-2 justify-center sm:justify-start lg:w-full lg:mb-4 flex lg:hidden xs:space-x-4"
            />
          </div>
          <div class="lg:order-1">
            <p v-if="resource.description_short" class="mb-4">
              {{ resource.description_short }}
            </p>
            <p
              v-if="
            !resource.description_short ||
            (resource.description &&
              resource.description.length >
                resource.description_short.length + 25)
          "
              class="mb-4"
            >
              {{ resource.description }}
            </p>
            <p v-if="resource.description_alt || phrase" class="mb-4">
              <em>{{ resource.description_alt }} <span v-if="phrase">{{ phrase }}</span></em>
            </p>
          </div>
        </div>
        <div
          v-if="resource.images"
          class="w-[calc(100%+16px)] -ml-2 -mb-4 lg:w-1/3 mt-4 lg:pl-4 lg:-mt-2 lg:-mr-2"
        >
          <Side :images="resource.images" :resource="resource" :type="'trail'"/>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  components: {
    ActivityButtons,
    MapModal,
    TrailStats,
  },
  data() {
    return {
      expanded: false,
    }
  },
  methods: {
    zoom() {
      // TODO: We'll want to include their base area on the map, so they have an idea where the trail is in relation to
      // it. If it's far away, zoom out. If it's close, zoom in.
      return 15;
    },
    select() {
      // this.sendResource();
    },
    sendResource() {
      // const store = useListStore();
      // store.setResource(this.resource);
    },
  },
  props: {
    activity: Object,
    phrase: String,
    resource: Object,
  },
};
</script>

<script setup>
import {ref, onMounted, onUnmounted, defineProps, watch} from 'vue';
import {useListStore} from '../../stores/list.js';
import ActivityButtons from '../components/ActivityButtons.vue'
import MapModal from '../modals/MapModal.vue'
import Side from '../panels/Side.vue'
import TrailStats from '../stats/TrailStats.vue'
</script>
