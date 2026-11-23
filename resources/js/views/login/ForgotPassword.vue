<template>
  <img alt="Kanab Trip Planner" class="mx-auto opacity-60 h-16 w-auto flex landscape:hidden" src="/images/icons/hummingbird.png">
  <div class="flex h-full justify-center items-center px-6 w-full flex-col lg:px-8">
    <div class="flex flex-col w-full sm:max-w-sm">
      <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-white">Reset your password</h2>
      <p class="mt-4 text-center text-sm text-white">Enter the email address you signed up with and we'll send you a link to choose a new password.</p>

      <form class="space-y-6 mt-8" @submit.prevent="send">
        <div>
          <label class="block text-sm font-medium text-white" for="forgot-email">Email address</label>
          <input id="forgot-email" v-model="email" autocomplete="email" required type="email"
                 class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm">
        </div>
        <button :disabled="busy" type="submit"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 font-semibold text-sm text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-indigo-600">
          Email me a reset link
        </button>
        <p v-if="sent" class="text-sm text-white bg-green-700/70 rounded px-3 py-2" role="status">{{ sent }} Check your inbox, and your spam folder.</p>
        <p v-if="error" class="text-sm text-white bg-red-600/70 rounded px-3 py-2" role="alert">{{ error }}</p>
        <p class="text-center text-sm text-white">
          Remembered it? <router-link class="underline" :to="{ name: 'login' }">Sign in</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../../stores/auth';

export default {
  name: 'ForgotPassword',
  setup() {
    return { authStore: useAuthStore() };
  },
  data() {
    return { email: this.$route.query.email || '', sent: null, error: null, busy: false };
  },
  methods: {
    async send() {
      this.error = null;
      this.sent = null;
      this.busy = true;
      try {
        this.sent = await this.authStore.requestPasswordReset(this.email);
      } catch (err) {
        this.error = err.response?.data?.errors?.email?.[0] || err.response?.data?.message || 'We couldn\'t send the email. Please try again.';
      } finally {
        this.busy = false;
      }
    },
  },
};
</script>
