<template>
  <div>
    <Login />
  </div>
</template>

<script>
import Login from "../login/Login.vue";
import {useAuthStore} from "../../stores/auth";

export default {
  components: {
    Login,
  },
  setup() {
    const authStore = useAuthStore();
    return { authStore };
  },
  computed: {
    isLoginOpen() {
      return this.authStore.isLoginOpen;
    },
  },
  data() {
    return {
    };
  },
  methods: {
  },
}
</script>

