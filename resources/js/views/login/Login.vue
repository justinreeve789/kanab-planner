<template>
  <img alt="Kanab Trip Planner" class="mx-auto opacity-60 h-16 w-auto flex landscape:hidden" src="/images/icons/hummingbird.png">
  <div class="flex h-full justify-center items-center px-6 w-full flex-col landscape:flex-row gap-8 landscape:gap-16 lg:px-8">
    <div class="flex flex-col w-full sm:max-w-sm">
      <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-white">Sign in to your account</h2>

      <form class="space-y-6 mt-10" @submit.prevent="login">
        <div>
          <label class="block text-sm font-medium text-white" for="login-email">Email address</label>
          <input id="login-email" v-model="email" autocomplete="email" required type="email"
                 class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-white" for="login-password">Password</label>
          <input id="login-password" v-model="password" autocomplete="current-password" required type="password"
                 class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm">
        </div>
        <button :disabled="busy" type="submit"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 font-semibold text-sm text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-indigo-600">
          Sign in
        </button>
        <a href="/auth/google/redirect" class="flex w-full justify-center rounded-md bg-white px-3 py-1.5 font-semibold text-sm text-gray-800 shadow-xs hover:bg-gray-100">
          Continue with Google
        </a>
        <p v-if="error" class="text-sm text-white bg-red-600/70 rounded px-3 py-2" role="alert">{{ error }}</p>
      </form>
    </div>

    <div class="divider divider-primary text-white opacity-80 landscape:divider-horizontal">OR</div>

    <div class="flex flex-col sm:w-full sm:max-w-sm">
      <h2 class="text-center text-2xl font-bold tracking-tight text-white">Create an account</h2>
      <p class="mt-4 text-center text-sm text-white">Keep your itineraries on every device and come back to them any time.</p>
      <router-link :to="{ name: 'register' }" class="mt-6 flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-indigo-600">
        Register account
      </router-link>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../../stores/auth';

export default {
  name: 'Login',
  setup() {
    return { authStore: useAuthStore() };
  },
  data() {
    return { email: '', password: '', error: new URLSearchParams(window.location.search).get('error'), busy: false };
  },
  methods: {
    async login() {
      this.error = null;
      this.busy = true;
      try {
        await this.authStore.login(this.email, this.password);
        this.$router.push(this.$route.query.redirect || '/');
      } catch (err) {
        this.error = err.response?.data?.errors?.email?.[0] || err.response?.data?.message || 'Sign in failed.';
      } finally {
        this.busy = false;
      }
    },
  },
};
</script>
