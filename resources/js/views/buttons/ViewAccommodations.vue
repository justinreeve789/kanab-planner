<template>
  <label :for="'resource-' + unique + '-accommodations'" class="mb-2 sm:mb-0 sm:mr-2">
    <a :href="resource.url" target="_blank" rel="noopener noreferrer" class="mb-2 sm:mb-0 sm:mr-2 block w-full sm:w-auto">
      <span class="block w-full sm:w-auto text-white bg-gradient-to-r from-blue-400 via-blue-500 to-blue-600 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
        View accommodations
      </span>
    </a>
  </label>
</template>

<script>
export default {
  components: {
  },
  data() {
  },
  methods: {
  },
  props: {
    resource: Object,
    unique: String,
  },
};
</script>
