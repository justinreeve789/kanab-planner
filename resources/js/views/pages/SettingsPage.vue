<template>
  <div class="bg-white h-[calc(100vh-128px)] overflow-hidden pt-2 md:py-8">
    <h1 class="mb-4 text-black text-center text-xl md:mb-8 md:text-3xl">Settings</h1>
    <Settings />
  </div>
</template>

<script>
import Settings from "../preferences/Settings.vue";

export default {
  components: {
    Settings,
  },
  mounted() {
  },
};
</script>