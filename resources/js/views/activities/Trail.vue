<template>
  <div class="resource-details collapse collapse-arrow" data-type="trail" :data-id="trail.id">
    <input type="checkbox" />
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
        <div class="activity-stats flex items-center justify-end text-right flex-wrap">
            <span class="distance flex">
              <img class="icon pr-1 svg-anim" src="/images/icons/distance.svg" alt="Distance">
              <span class="amount pr-1">{{ trail.distance_miles }}</span>
              <span class="measurement">mi</span>
            </span>
            <span class="incline flex justify-end ml-3 order-3 md:order-2">
              <img class="icon pr-1 svg-anim" src="/images/icons/mountain.svg" alt="Elevation Gain">
              <span class="amount pr-1">+{{ trail.incline_ft }}</span>
              <span class="measurement">ft</span>
            </span>
            <span class="average-grade flex ml-3 order-2 md:order-3">
              <img class="icon pr-1 svg-anim" src="/images/icons/average-grade.svg" alt="Average Grade">
              <span class="amount">{{ average_grade_pt }}</span>
              <span class="measurement">%</span>
            </span>
            <span class="activity-actions flex ml-3 order-4 relative z-10">
              <a class="shrink-0" href="#">
                <img class="icon" src="/images/icons/edit.svg" alt="Change Activity">
              </a>
            </span>
        </div>
    </div>

    <div class="collapse-content bg-gray-200 p-2 bg-gradient-to-r from-white to-red-50">
        <div class="lg:flex lg:justify-between">
          <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
              <div class="lg:order-2">
                <ActivityButtons class="text-right my-4 lg:text-left" type="trail" :hash="activity.hash" />
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
<!--
    <div class="trail-map">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d96522.36423741286!2d-112.9271579498936!3d37.29713303136258!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zN7CsMTUwJzE3LjEiTiAxMTLCsDQyJzA2LjMiVw!5e0!3m2!1sen!2sus!4v1628604616011!5m2!1sen!2sus" width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>
-->
  <MapModal :activity="activity" :hash="activity.hash"/>
</template>

<script>
export default {
  components: {
    ActivityButtons,
    MapModal,
  },
  computed: {
      average_grade_pt() {
      return Math.round(this.trail.average_grade_pt * 100);
    }
  },
  methods: {
  },
  created() {
  },
  data() {
    return {
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
import MapModal from '../panels/MapModal.vue'
import Side from '../panels/Side.vue'
</script>