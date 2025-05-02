<template>
  <div class="min-h-[150px] items-stretch w-full bg-gray-200 p-2 bg-gradient-to-r from-white to-red-50">
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
</template>

<script>
import {useSearchStore} from "../../../stores/search";
import ActivityButtons from "../../components/ActivityButtons.vue";
import Side from "../../panels/Side.vue";
import TrailStats from "../../stats/TrailStats.vue";

export default {
  setup() {
    const searchStore = useSearchStore();
    return { searchStore };
  },
  components: {
    ActivityButtons,
    Side,
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
      this.searchStore.selectedAreaIds = [this.resource.area_id];
      this.searchStore.showAreaSelect = false;
      this.searchStore.isSearchOpen = true;
    },
    zoom() {
      // TODO: We'll want to include their base area on the map, so they have an idea where the trail is in relation to
      // it. If it's far away, zoom out. If it's close, zoom in.
      return 15;
    },
  },
  props: {
    phrase: String,
    resource: Object,
  },
};
</script>