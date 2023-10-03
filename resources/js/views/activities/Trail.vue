<template>
  <div class="resource-details collapse collapse-arrow" data-type="trail" :data-id="resource.id">
    <input type="checkbox" v-model="expanded" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-orange-100 to-red-100">
      <div class="title flex items-center space-x-2 text-left">
        <div class="icon pr-1 shrink-0">
          <img src="/images/icons/hiking.svg" alt="Hiking">
        </div>
        <div class="text-xl">{{ resource.name }}</div>
        <div class="difficulty hidden">
          <img :src="'/images/icons/difficulty-' + resource.difficulty + '.png'" :alt="resource.formattedDifficulty">
        </div>
      </div>
      <div class="flex space-x-4 items-center mr-8 h-full">
        <ActivityStats :trail="resource" :align="'right'" class="hidden lg:flex" />
        <label for="trail-list">
          <span @click="select" class="icon btn btn-sm btn-ghost relative z-10 h-full">
            <img src="/images/icons/edit.svg" alt="Change Activity">
          </span>
        </label>
      </div>
    </div>

    <div class="collapse-content bg-gray-200 p-2 bg-gradient-to-r from-white to-red-50" v-if="expanded">
        <div class="lg:flex lg:justify-between">
          <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
              <div class="lg:order-2">
                <div class="flex justify-between">
                  <div class="flex-auto text-left lg:hidden">
                    <ActivityStats :trail="resource" :align="'left'" class="pt-4" />
                  </div>
                  <div class="flex-auto text-left">
                    <ActivityButtons class="text-right my-4 lg:text-left" type="trail" :unique="unique" />
                  </div>
                </div>
              </div>
              <div class="lg:order-1">
                <p class="mb-4" v-if="resource.description_alt">{{ resource.description_alt }}</p>
                <p class="mb-4" v-if="resource.description">{{ resource.description }}</p>
                <p v-if="phrase"><em>{{ phrase }}</em></p>
              </div>
          </div>
          <div class="w-full lg:w-1/3 mt-4 lg:mt-0 lg:pl-4" v-if="resource.images">
            <Side :resource="resource" :unique="unique" :type="'trail'" :images="resource.images" />
          </div>
        </div>
    </div>
  </div>
  <MapModal :resource="resource" :unique="unique" />
</template>

<script>
export default {
  components: {
    ActivityButtons,
    ActivityStats,
    MapModal,
  },
  data() {
    return {
      expanded: false,
    }
  },
  methods: {
    select() {
      //this.$root.$emit('select-trail', this.activity);
    },
  },
  props: {
    activity: Object,
    phrase: String,
    resource: Object,
    unique: String,
  },
};
</script>

<script setup>
import { ref, onMounted, onUnmounted, defineProps, watch } from 'vue';
import ActivityButtons from '../panels/ActivityButtons.vue'
import ActivityStats from '../panels/ActivityStats.vue'
import MapModal from '../panels/MapModal.vue'
import Side from '../panels/Side.vue'
</script>