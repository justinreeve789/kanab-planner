<template>
  <input type="checkbox" id="route-modal" class="modal-toggle" v-model="expanded" />
  <div class="modal max-h-[calc(100vh-64px)] md:max-h-full" id="search-modal">
    <div class="modal-container bg-white max-w-full w-full h-calc(100vh-64px) -translate-x-1/2 md:-translate-y-1/2 top-0 md:w-[90vw] md:h-[90vh] md:top-1/2 md:rounded-[15px] md:border-[5px] md:border-[#d0e0f0]">
      <div class="flex justify-between items-center mb-0 bg-gradient-to-r from-blue-100 to-cyan-200 p-4 pr-2 md:rounded-t-xl w-full">
        <h2 class="text-black text-lg">{{ title }}</h2>
        <div class="flex align-right">
          <button class="cursor-pointer btn btn-sm btn-ghost text-black hover:bg-cyan-100 md:flex" @click="$removeHashFromUrl()">
            <label for="search-modal">
              <span>✕</span>
            </label>
          </button>
        </div>
      </div>
      <div class="modal-box overflow-x-hidden bg-white text-black w-full max-w-full h-[calc(100vh-64px)] max-h-[calc(100vh-128px)] md:w-full md:h-[calc(100%-4rem)]">
        <component :is="getComponent(component)" v-if="component !== ''"></component>
      </div>
    </div>
  </div>
  <label for="route-modal" class="modal-backdrop hidden md:block"></label>
</template>

<script>
import {defineAsyncComponent} from "vue";
import {useSearchStore} from "../../stores/search";

export default {
  setup() {
    const searchStore = useSearchStore();
    const getComponent = (component) => {
      return defineAsyncComponent(() => import('../' + component)) || null;
    };
    return { getComponent, searchStore };
  },
  watch: {
    '$route.hash'(newVal) {
      if (typeof newVal === 'string') {
        if (newVal.startsWith('#modal-')) {
          const parts = newVal.split('-');
          if (parts.length > 1 && parts[0] === '#modal') {
            switch (parts[1]) {
              case 'search': {
                // Keep whoever opened the search (e.g. the questionnaire's wish list) as the add target.
                const target = this.searchStore.target;
                this.component = 'search/Search';
                this.title = 'Search';
                this.searchStore.adjustSettingsForGeneralSearch();

                if (parts[2] === 'events') {
                  this.title = 'Events';
                  this.searchStore.adjustSettingsForResourceListForTable('events');
                } else if (parts[2] === 'trails') {
                  // TODO: Add ability to include target and closeAfterAddToItinerary parameters.
                  this.title = 'Trails';
                  this.searchStore.adjustSettingsForResourceListForTable('trails');
                }
                this.searchStore.target = target;
                break;
              }

              default:
                this.component = '';
            }

            if (this.component !== '') {
              this.expanded = true;
              return;
            }
          }
        }
        this.expanded = false;
      }
    },
  },
  props: {
    title: String,
  },
  data() {
    return {
      component: '',
      expanded: false,
      title: '',
    };
  },
}
</script>
