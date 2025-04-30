<template>
  <Header />
  <div class="m-0 p-0">
    <Itinerary :itinerary-id="itineraryId" v-show="!isLoginOpen" />
    <Login v-if="isLoginOpen" />
  </div>
  <Dock />
  <Assistant />
  <PreferencesModal />
  <SearchModal />
  <MapModal />
</template>

<script>
import Itinerary from './Itinerary.vue';
import LoginModal from "./modals/LoginModal.vue";
import QuestionnaireModal from './modals/QuestionnaireModal.vue';
import Dock from "./panels/Dock.vue";
import Header from "./panels/Header.vue";
import Assistant from "./assistant/Assistant.vue";
import PreferencesModal from "./modals/PreferencesModal.vue";
import SearchModal from "./modals/SearchModal.vue";
import {useAuthStore} from "../stores/auth";
import {useFeatureStore} from "../stores/feature";
import Login from "./login/Login.vue";
import MapModal from "./modals/MapModal.vue";

export default {
  components: {
    Login,
    SearchModal,
    PreferencesModal,
    Assistant,
    Header,
    Dock,
    Itinerary,
    LoginModal,
    QuestionnaireModal,
    MapModal,
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
