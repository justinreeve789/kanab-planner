<template>
  <Header />
  <Main />
  <Dock />
</template>

<script>
import Dock from "./panels/Dock.vue";
import Header from "./panels/Header.vue";
import Main from "./Main.vue";
import {useAuthStore} from "../stores/auth";
import {useFeatureStore} from "../stores/feature";
import {useRouter} from "vue-router";

export default {
  components: {
    Main,
    Header,
    Dock,
  },
  props: {
  },
  data() {
    return {
    };
  },
  setup() {
    const authStore = useAuthStore();
    const featureStore = useFeatureStore();
    const router = useRouter();
    return { authStore, featureStore, router };
  },
  computed: {
  },
  methods: {
  },
  mounted() {
  },
};
</script>
