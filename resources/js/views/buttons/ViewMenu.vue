<template>
  <label :for="'resource-' + unique + '-menu'" class="mb-2 sm:mb-0 sm:mr-2">
      <span class="block w-full sm:w-auto text-white bg-gradient-to-r from-green-400 via-green-500 to-green-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-green-300 dark:focus:ring-green-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
        View menu
      </span>
  </label>
</template>

<script>
export default {
  components: {
  },
  data() {
    return {
      unique: this.$generateUnique(),
    };
  },
  methods: {
  },
  props: {
    resource: Object,
  },
};
</script>
