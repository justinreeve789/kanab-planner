<template>
  <div id="main" class="m-0 p-0">
    <router-view />
  </div>
  <Corner />
  <AssistantModal />
  <RouteModal />
  <TrailMapModal />
</template>

<script>
import AssistantModal from "./modals/AssistantModal.vue";
import Corner from "./panels/Corner.vue";
import RouteModal from "./modals/RouteModal.vue";
import TrailMapModal from "./modals/TrailMapModal.vue";
import {useAuthStore} from "../stores/auth";
import {useFeatureStore} from "../stores/feature";
import {useRouter} from "vue-router";

export default {
  setup() {
    const authStore = useAuthStore();
    const featureStore = useFeatureStore();
    const router = useRouter();
    return { authStore, featureStore, router };
  },
  components: {
    AssistantModal,
    Corner,
    RouteModal,
    TrailMapModal,
  },
  data() {
    return {
      completedQuestionnaire: false,
      openQuestionnaire: false,
    };
  },
};
</script>
