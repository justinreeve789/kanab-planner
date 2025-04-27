<template>
  <div class="card bg-white shadow-xl px-0 py-4 flex flex-col lg:px-4 md:flex-row">
    <div class="w-full h-auto md:w-24 md:relative">
      <img src="https://img.daisyui.com/images/stock/photo-1635805737707-575885ab0820.webp" class="absolute inset-0 w-full h-full object-cover opacity-30 md:opacity-100 md:rounded-r-lg lg:rounded-lg" />
    </div>

    <div class="card-body md:w-2/3 flex flex-col justify-between p-4 md:rounded-lg">
      <h2 class="card-title text-xl font-bold">{{ resource.name }}</h2>
      <p class="text-gray-600 line-clamp-3">{{ resource.description }}</p>
      <div class="features mt-2 text-sm text-gray-500">
        <p><strong>Features:</strong></p>
      </div>
      <div class="card-actions mt-4 space-x-2 flex-wrap text-left md:justify-end">
        <button class="btn btn-primary">View Map</button>
        <button class="btn btn-secondary">Add to Itinerary</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  components: {
  },
  setup() {
  },
  mounted() {
  },
  data() {
    return {
    };
  },
  props: {
    resource: Object,
  },
}
</script>