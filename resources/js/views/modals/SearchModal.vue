<template>
  <input type="checkbox" id="search-modal" class="modal-toggle" v-model="searchStore.isSearchOpen" />
  <div class="modal max-h-[calc(100vh-64px)] md:max-h-full" id="search-modal">
    <div class="modal-container bg-white max-w-full w-full h-calc(100vh-64px) -translate-x-1/2 md:-translate-y-1/2 top-0 md:w-[90vw] md:h-[90vh] md:top-1/2 md:rounded-[15px] md:border-[5px] md:border-[#d0e0f0]">
      <div class="flex justify-between items-center mb-0 bg-gradient-to-r from-blue-100 to-cyan-200 p-4 pr-2 md:rounded-t-xl w-full">
        <h2 class="text-black text-lg">Search</h2>
        <div class="flex align-right">
          <button class="btn btn-sm btn-ghost hover:bg-cyan-100 text-black hidden md:flex">
            <label for="search-modal">
              <span>✕</span>
            </label>
          </button>
        </div>
      </div>
      <div class="modal-box overflow-x-hidden bg-white text-black w-full max-w-full h-[calc(100vh-64px)] max-h-[calc(100vh-128px)] md:w-full md:h-[calc(100%-4rem)]">
        <Search />
      </div>
    </div>
  </div>
  <label for="search-modal" class="modal-backdrop hidden md:block"></label>
</template>

<script>
import Search from "../search/Search.vue";
import { useSearchStore } from "../../stores/search";

export default {
  components: {
    Search,
  },
  setup() {
    const searchStore = useSearchStore();
    return { searchStore };
  },
  mounted() {
  },
  data() {
    return {
    };
  },
  methods: {
  },
}
</script>

