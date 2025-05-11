<template>
  <div :data-id="resource.id" class="resource-details collapse collapse-arrow" data-type="trail">
    <input v-model="expanded" type="checkbox"/>
    <div
      class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-orange-100 to-red-100">
      <div class="title w-1/2 flex items-center space-x-2 text-left">
        <div class="icon pr-1 shrink-0">
          <img alt="Hiking" src="/images/icons/hiking.svg">
        </div>
        <div class="text-lg md:text-xl">{{ resource.name }}</div>
        <div class="difficulty hidden">
          <img :alt="resource.formattedDifficulty" :src="'/images/icons/difficulty-' + resource.difficulty + '.png'">
        </div>
      </div>
      <div class="flex space-x-4 items-center mr-8 h-full">
        <TrailStats :align="'right'" :trail="resource" class="hidden lg:flex"/>
        <label for="trail-list">
          <span @click="changeActivity" class="icon btn btn-sm btn-ghost relative z-10 h-full">
            <img alt="Change Activity" src="/images/icons/edit.svg">
          </span>
        </label>
      </div>
    </div>

    <div class="collapse-content p-0" v-if="expanded">
      <TrailContent :phrase="phrase" :resource="resource" />
    </div>
  </div>
</template>

<script>
import TrailContent from "./content/TrailContent.vue";
import TrailStats from "../stats/TrailStats.vue";
import {useSearchStore} from "../../stores/search";

export default {
  setup() {
    const searchStore = useSearchStore();
    return { searchStore };
  },
  components: {
    TrailContent,
    TrailStats,
  },
  data() {
    return {
      expanded: false,
    }
  },
  methods: {
    changeActivity() {
      this.searchStore.adjustSettingsForResourceListForTable('trails');
      this.searchStore.title += ' @ ' + this.resource.area.short_name;
      this.searchStore.selectedAreaIds = [this.resource.area_id];
      this.searchStore.showAreaSelect = false;
      this.searchStore.disabledResources = [{ type: 'trail', id: this.resource.id }];
      this.searchStore.isSearchOpen = true;
    },
    zoom() {
      // TODO: We'll want to include their base area on the map, so they have an idea where the trail is in relation to
      // it. If it's far away, zoom out. If it's close, zoom in.
      return 15;
    },
  },
  props: {
    activity: Object,
    phrase: String,
    resource: Object,
  },
};
</script>