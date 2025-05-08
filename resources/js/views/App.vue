<template>
  <Header />
  <div class="m-0 p-0">
    <Itinerary :itinerary-id="itineraryId" v-show="!isLoginOpen" />
    <Login v-if="isLoginOpen" />
  </div>
  <Dock />
  <Corner />
  <AssistantModal />
  <AttributesModal />
  <QuestionnaireModal />
  <SearchModal />
  <TrailMapModal />
</template>

<script>
import Itinerary from './Itinerary.vue';
import LoginModal from "./modals/LoginModal.vue";
import QuestionnaireModal from './modals/QuestionnaireModal.vue';
import Dock from "./panels/Dock.vue";
import Header from "./panels/Header.vue";
import Assistant from "./assistant/Assistant.vue";
import AttributesModal from "./modals/AttributesModal.vue";
import SearchModal from "./modals/SearchModal.vue";
import {useAuthStore} from "../stores/auth";
import {useFeatureStore} from "../stores/feature";
import Login from "./login/Login.vue";
import TrailMapModal from "./modals/TrailMapModal.vue";
import QuestionnaireButton from "./preferences/QuestionnaireButton.vue";
import Corner from "./panels/Corner.vue";
import AssistantModal from "./modals/AssistantModal.vue";

export default {
  components: {
    Login,
    AssistantModal,
    SearchModal,
    AttributesModal,
    QuestionnaireButton,
    Assistant,
    Header,
    Dock,
    Corner,
    Itinerary,
    LoginModal,
    QuestionnaireModal,
    TrailMapModal,
  },
  props: {
    itineraryId: {
      type: String,
      required: false,
    }
  },
  data() {
    return {
      completedQuestionnaire: false,
      openQuestionnaire: false,
    };
  },
  setup() {
    const authStore = useAuthStore();
    const featureStore = useFeatureStore();
    return { authStore, featureStore };
  },
  computed: {
    isLoginOpen() {
      return this.authStore.isLoginOpen;
    },
  },
  methods: {
    getFeatureFlags() {
    },
  },
  mounted() {
    this.getFeatureFlags();
    this.openQuestionnaire = true;
  },
};
</script>
