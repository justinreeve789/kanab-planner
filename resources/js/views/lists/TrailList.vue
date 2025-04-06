<template>
  <h3>Trails</h3>
  <div class="list-container">
      <div v-for="(resource, index) in resources">
        <div class="resource-details collapse collapse-arrow" data-type="trail" :data-id="resource.id">
          <input type="radio" name="trails" />
          <div class="collapse-title flex items-center justify-between w-full bg-gradient-to-r from-orange-100 to-red-100">
            <div class="title flex items-center space-x-2 text-left">
              <div class="pr-1 shrink-0">
                <img :src="'/featured-resource-image/trail/' + resource.id + '?size=thumbnail'" alt="">
              </div>
              <div class="name">{{ resource.name }}</div>
              <div class="difficulty hidden">
                <img :src="'/images/icons/difficulty-' + resource.difficulty + '.png'" :alt="resource.formattedDifficulty">
              </div>
            </div>
            <div class="flex space-x-4 items-center mr-8 h-full">
              <ActivityStats :trail="resource" :align="'right'" />
            </div>
          </div>

          <div class="collapse-content bg-gray-200 p-2 bg-gradient-to-r from-white to-red-50" v-if="expanded">
            <div class="lg:flex 2xl:justify-between">
              <div class="w-full 2xl:w-2/3 2xl:flex 2xl:flex-col">
                <div class="2xl:order-2">
                  <div class="flex justify-between">
                    <div class="flex-auto text-left lg:hidden">
                      <ActivityStats :trail="resource" :align="'left'" class="pt-4" />
                    </div>
                    <div class="flex-auto text-left">
<!--                  <ActivityButtons class="text-right my-4 lg:text-left" type="trail" :unique="unique" />-->
                    </div>
                  </div>
                </div>
                <div class="2xl:order-1">
                  <p class="mb-4" v-if="resource.description_alt">{{ resource.description_alt }}</p>
                  <p class="mb-4" v-if="resource.description">{{ resource.description }}</p>
                </div>
              </div>
              <div class="w-full 2xl:w-1/3 mt-4 2xl:mt-0 lg:pl-4" v-if="resource.images">
                <Side :resource="resource" :unique="resource.id" :type="'trail'" :images="resource.images" />
              </div>
            </div>
          </div>
        </div>
      </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      area: {},
      expanded: [],
      resources: [],
    }
  },
  methods: {
    fetch() {
      axios
        .get('/api/v1/area/' + this.selected.area.id + '/trail')
        .then((response) => {
          this.resources = response.data;
        })
        .catch((error) => {
          console.error('Error fetching data:', error);
        });
    },
  },
  mounted() {
    this.fetch();
  },
  props: {
    selected: Object,
  },
};
</script>

<script setup>
import axios from 'axios';
import ActivityStats from '../panels/ActivityStats.vue';
import Side from '../panels/Side.vue';
import Trail from '../activities/Trail.vue';
</script>
