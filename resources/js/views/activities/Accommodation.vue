<template>
  <div class="resource-details collapse collapse-arrow" data-type="trail" :data-id="resource.id">
    <input type="checkbox" v-model="expanded" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-orange-100 to-red-100">
      <div class="title w-1/2 flex items-center space-x-2 text-left">
        <div class="icon pr-1 shrink-0">
          <img src="/images/icons/hiking.svg" alt="Hiking">
        </div>
        <div class="text-xl">{{ resource.name }}</div>
      </div>
      <div class="flex space-x-4 items-center mr-8 h-full">
        <label for="accommodation-list">
          <span @click="select" class="icon btn btn-sm btn-ghost relative z-10 h-full">
            <img src="/images/icons/edit.svg" alt="Change Accommodations">
          </span>
        </label>
      </div>
    </div>

    <div class="collapse-content min-h-[150px] items-stretch w-full bg-gray-200 p-2 bg-gradient-to-r from-white to-red-50" v-if="expanded">
      <div class="lg:flex lg:justify-between">
        <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
          <div class="lg:order-2">
            <div class="flex justify-between">
              <div class="flex-auto text-left lg:hidden">
              </div>
              <div class="flex-right">
                <ActivityButtons class="text-right my-4 lg:text-left" type="checking-in" :unique="unique" :resource="resource" />
              </div>
            </div>
          </div>
          <div class="lg:order-1">
            Test
          </div>
        </div>
        <div class="w-[calc(100%+16px)] -ml-2 -mb-4 lg:w-1/3 mt-4 lg:pl-4 lg:-mt-2 lg:-mr-2" v-if="resource.images">
          <Side :resource="resource" :unique="unique" :type="'accommodation'" :images="resource.images" />
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
import { ref, onMounted, onUnmounted, defineProps, watch } from 'vue';
import { useListStore } from '../../stores/list.js';
import ActivityButtons from '../components/ActivityButtons.vue'
import MapModal from '../modals/MapModal.vue'
import Side from '../panels/Side.vue'
import TrailStats from '../stats/TrailStats.vue'
</script>
