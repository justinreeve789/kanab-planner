<template>
  <div class="min-h-[150px] items-stretch w-full bg-gray-200 p-2 bg-gradient-to-r from-white to-green-100">
    <div class="lg:flex lg:justify-between">
      <div class="w-full lg:w-2/3 lg:flex lg:flex-col">
        <div class="lg:order-1">
          <p class="mb-4" v-if="activity.description">{{ activity.description }}</p>
          <TravelContent :activity="activity" v-if="activity.activity_type.name === 'travel'" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import TravelContent from "./tasks/TravelContent.vue";

export default {
  setup() {
  },
  components: {
    TravelContent,
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