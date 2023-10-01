<template>
  <div class="mt-4">
    <button v-if="type == 'trail'" type="button" class="text-white bg-gradient-to-r from-orange-400 via-orange-500 to-orange-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-orange-300 dark:focus:ring-orange-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2">View map</button>
    <button v-if="type == 'restaurant'" type="button" class="text-white bg-gradient-to-r from-green-400 via-green-500 to-green-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-green-300 dark:focus:ring-green-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center mr-2 mb-2">View menu</button>
  </div>
</template>

<script>
export default {
  components: {
  },
  data() {
  },
  props: {
    hash: String,
    resource: Object,
    images: Object,
    map: Object,
    type: String,
  },
};
</script>

<script setup>
</script>