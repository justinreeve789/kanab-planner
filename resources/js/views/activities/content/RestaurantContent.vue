<template>
  <div class="min-h-[150px] items-stretch w-full bg-gray-200 p-2 bg-gradient-to-r from-white to-green-100">
    <div class="lg:flex lg:justify-between">
      <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
        <div class="lg:order-2">
          <ActivityButtons class="text-right my-4 lg:text-left" type="restaurant" :resource_table="'restaurants'" :resource_id="resource.id" />
        </div>
        <div class="lg:order-1">
          <p class="mb-4" v-if="resource.description_alt">{{ resource.description_alt }}</p>
          <p class="mb-4" v-if="resource.description">{{ resource.description }}</p>
          <p v-if="phrase"><em>{{ phrase }}</em></p>
        </div>
      </div>
      <div class="w-[calc(100%+16px)] -ml-2 -mb-4 lg:w-1/3 mt-4 lg:pl-4 lg:-mt-2 lg:-mr-2" v-if="resource.location.images">
        <Side :resource="restaurant" :type="'restaurant'" :images="resource.location.images" />
      </div>
    </div>
  </div>
</template>

<script>
import {useSearchStore} from "../../../stores/search";
import ActivityButtons from "../../components/ActivityButtons.vue";
import RestaurantStats from "../../stats/RestaurantStats.vue";
import Side from "../../panels/Side.vue";

export default {
  setup() {
    const searchStore = useSearchStore();
    return { searchStore };
  },
  components: {
    ActivityButtons,
    RestaurantStats,
    Side,
  },
  computed: {
  },
  methods: {
    changeActivity() {
      this.searchStore.adjustSettingsForResourceListForTable('restaurants');
      this.searchStore.selectedAreaIds = [this.resource.area_id];
      this.searchStore.showAreaSelect = false;
      this.searchStore.isSearchOpen = true;
    },
  },
  created() {
  },
  data() {
    return {
      expanded: false,
    }
  },
  props: {
    activity: Object,
    phrase: String,
    resource: Object,
  },
};
</script>