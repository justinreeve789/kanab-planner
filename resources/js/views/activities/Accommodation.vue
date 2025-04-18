<template>
  <div :data-id="resource.id" class="resource-details collapse collapse-arrow" data-type="trail">
    <input v-model="expanded" type="checkbox"/>
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-blue-100 to-cyan-100">
      <div class="title w-1/2 flex items-center space-x-2 text-left">
        <div class="icon pr-1 shrink-0">
          <img src="/images/icons/thumbs-up.svg" alt="Accommodation">
        </div>
        <div class="text-xl">{{ resource.name }}</div>
      </div>
      <div class="flex space-x-4 items-center mr-8 h-full">
        <div class="badge badge-lg bg-blue-400 border-blue-400 mr-2 text-white hidden md:flex">
          Check-in: {{ formatTime(resource.check_in) }}
        </div>
      </div>
    </div>

    <div v-if="expanded" class="collapse-content min-h-[150px] items-stretch w-full bg-gray-200 p-2 bg-gradient-to-r from-white to-cyan-100">
      <div class="lg:flex lg:justify-between">
        <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
          <div class="order-2 lg:order-1">
            <p>
              {{ resource.description }}
            </p>
          </div>
          <div class="sm:flex sm:flex-row justify-between lg:flex-col order-1 lg:order-2">
            <ActivityButtons :resource="resource" :resource_table="'accommodations'" class="w-full my-4" type="checking-in" />
          </div>
        </div>
        <div v-if="resource.images" class="w-[calc(100%+16px)] -ml-2 -mb-4 lg:w-1/3 mt-4 lg:pl-4 lg:-mt-2 lg:-mr-2">
          <Side :images="resource.images" :resource="resource" :type="'accommodation'" />
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
  },
  data() {
    return {
      expanded: false,
    }
  },
  methods: {
    formatTime(time24) {
      if (!time24) return '';
      const [hours, minutes] = time24.split(':');
      const date = new Date();
      date.setHours(hours);
      date.setMinutes(minutes);

      const formatted = date.toLocaleTimeString([], {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      });

      return formatted.replace(/AM|PM/, match => match.toLowerCase());
    },
  },
  props: {
    activity: Object,
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
