<template>
  <div class="min-h-[150px] items-stretch w-full bg-gray-200 p-2 bg-gradient-to-r from-white to-green-100">
    <div class="lg:flex lg:justify-between">
      <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
        <div class="lg:order-1">
          <p class="mb-4" v-if="activity.description">{{ activity.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  setup() {
  },
  components: {
  },
  computed: {
  },
  methods: {
    changeActivity() {
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
  },
};
</script>