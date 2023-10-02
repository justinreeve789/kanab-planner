<template>
  <div class="resource-details collapse collapse-arrow" data-type="trail" :data-id="trail.id">
    <input type="checkbox" v-model="expanded" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-orange-100 to-red-100">
        <div class="title flex items-center space-x-2">
          <div class="icon pr-1 shrink-0">
            <img src="/images/icons/hiking.svg" alt="Hiking">
          </div>
          <div class="text-xl text-left">{{ trail.name }}</div>
          <div class="difficulty hidden">
            <img :src="'/images/icons/difficulty-' + trail.difficulty + '.png'" :alt="trail.formattedDifficulty">
          </div>
        </div>
        <ActivityStats :trail="trail" :align="'right'" class="hidden lg:flex" />
    </div>

    <div class="collapse-content bg-gray-200 p-2 bg-gradient-to-r from-white to-red-50" v-if="expanded">
        <div class="lg:flex lg:justify-between">
          <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
              <div class="lg:order-2">
                <div class="flex justify-between">
                  <div class="flex-auto text-left lg:hidden">
                    <ActivityStats :trail="trail" :align="'left'" class="pt-4" />
                  </div>
                  <div class="flex-auto text-left">
                    <ActivityButtons class="text-right my-4 lg:text-left" type="trail" :hash="activity.hash" />
                  </div>
                </div>
              </div>
              <div class="lg:order-1">
                <p class="mb-4" v-if="trail.description_alt">{{ trail.description_alt }}</p>
                <p class="mb-4" v-if="trail.description">{{ trail.description }}</p>
                <p v-if="activity.phrase"><em>{{ activity.phrase }}</em></p>
              </div>
          </div>
          <div class="w-full lg:w-1/3 mt-4 lg:mt-0 lg:pl-4" v-if="trail.images">
            <Side :resource="trail" :hash="activity.hash" :type="'trail'" :images="trail.images" />
          </div>
        </div>
    </div>
  </div>
  <MapModal :activity="activity" :hash="activity.hash" />
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
      trail: this.activity.data,
    }
  },
  props: {
    activity: Object,
    day: Object,
  },
};
</script>

<script setup>
import ActivityButtons from '../panels/ActivityButtons.vue'
import ActivityStats from '../panels/ActivityStats.vue'
import MapModal from '../panels/MapModal.vue'
import Side from '../panels/Side.vue'
</script>