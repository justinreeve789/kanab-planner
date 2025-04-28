<template>
  <div class="resource-details collapse collapse-arrow" data-type="restaurant" :data-id="resource.id">
    <input type="checkbox" v-model="expanded" />
    <div class="collapse-title flex items-center p-2 justify-between w-full bg-gradient-to-r from-green-100 to-green-200">
      <div class="title w-1/2 flex items-center space-x-2 text-left">
        <div class="icon pr-1 shrink-0">
          <img src="/images/icons/dining.svg" alt="Dining">
        </div>
        <div class="text-xl">{{ resource.name }}</div>
      </div>
      <div class="flex space-x-4 items-center mr-8 h-full">
        <RestaurantStats :restaurant="resource" :align="'right'" class="hidden lg:flex" />
        <label for="restaurant-list">
          <span @click="changeActivity" class="icon btn btn-sm btn-ghost relative z-10 h-full">
            <img src="/images/icons/edit.svg" alt="Change Activity">
          </span>
        </label>
      </div>
    </div>

    <div class="collapse-content p-0" v-if="expanded">
      <RestaurantContent :phrase="phrase" :resource="resource" />
    </div>
  </div>
</template>

<script>
import RestaurantContent from "./content/RestaurantContent.vue";
import RestaurantStats from "../stats/RestaurantStats.vue";
import {useSearchStore} from "../../stores/search";

export default {
  setup() {
    const searchStore = useSearchStore();
    return { searchStore };
  },
  components: {
    RestaurantContent,
    RestaurantStats,
  },
  computed: {
  },
  methods: {
    changeActivity() {
      this.searchStore.adjustSettingsForResourceListForTable('restaurants');
      this.searchStore.title += ' @ ' + this.resource.area.short_name;
      this.searchStore.selectedAreaIds = [this.resource.area_id];
      this.searchStore.showAreaSelect = false;
      this.searchStore.disabledResources = [{ type: 'restaurant', id: this.resource.id }];
      this.searchStore.isSearchOpen = true;
    },
  },
  created() {
  },
  data() {
    return {
      expanded: false,
      restaurant: this.activity.data,
      unique: this.$generateUnique(),
    }
  },
  props: {
      activity: Object,
      phrase: String,
      resource: Object,
  },
};
</script>