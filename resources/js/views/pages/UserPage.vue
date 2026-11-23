<template>
  <div class="absolute z-10 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 overflow-y-scroll pb-[64px] max-h-[calc(100vh-128px)] w-full md:max-h-auto">
    <Login v-if="$route.name === 'login'" />
    <Register v-if="$route.name === 'register'" />
    <ForgotPassword v-if="$route.name === 'forgot-password'" />
    <ResetPassword v-if="$route.name === 'reset-password'" />
  </div>
  <ul class="bg-bubbles">
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
  </ul>
</template>

<script>
import Login from "../login/Login.vue";
import Register from "../login/Register.vue";
import ForgotPassword from "../login/ForgotPassword.vue";
import ResetPassword from "../login/ResetPassword.vue";

export default {
  components: {
    Login,
    Register,
    ForgotPassword,
    ResetPassword,
  },
};
</script>