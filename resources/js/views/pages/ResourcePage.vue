<template>
  <div class="bg-white overflow-x-hidden px-6 pt-2 sm:pt-3 md:mx-8 md:mb-8 md:px-16 md:py-8">
    <h1 class="mb-4 text-black text-center text-xl md:mb-8 md:text-3xl">{{ title }}</h1>
    <Search />
  </div>
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
  props: {
    page: String,
    title: String,
  },
  data() {
    return {
      validPages: ['accommodations', 'events', 'locations', 'restaurants', 'search', 'trails'],
    };
  },
  watch: {
    '$route'(newVal) {
      this.adjustSettingsForPageOrRedirect();
    },
  },
  mounted() {
    this.adjustSettingsForPageOrRedirect();
  },
  methods: {
    adjustSettingsForPageOrRedirect() {
      if (this.validPages.includes(this.page)) {
        if (this.page === 'search') {
          this.searchStore.adjustSettingsForGeneralSearch();
        } else {
          this.searchStore.adjustSettingsForPage(this.page);
        }
      } else {
        this.$router.push('/');
      }
    },
  },
}
</script>