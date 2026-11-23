<template>
  <img alt="Kanab Trip Planner" class="mx-auto opacity-60 h-16 w-auto flex landscape:hidden" src="/images/icons/hummingbird.png">
  <div class="flex h-full justify-center items-center px-6 w-full flex-col lg:px-8">
    <div class="flex flex-col w-full sm:max-w-sm">
      <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-white">Choose a new password</h2>
      <p v-if="email" class="mt-4 text-center text-sm text-white">For {{ email }}</p>

      <form class="space-y-6 mt-8" @submit.prevent="save">
        <div>
          <label class="block text-sm font-medium text-white" for="reset-password">New password</label>
          <input id="reset-password" v-model="password" autocomplete="new-password" required minlength="8" type="password"
                 class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm">
          <p class="text-xs text-white/80 mt-1">At least 8 characters.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-white" for="reset-password-confirmation">Confirm new password</label>
          <input id="reset-password-confirmation" v-model="confirmation" autocomplete="new-password" required type="password"
                 class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm">
        </div>
        <button :disabled="busy || !email" type="submit"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 font-semibold text-sm text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-indigo-600">
          Save new password
        </button>
        <p v-if="error" class="text-sm text-white bg-red-600/70 rounded px-3 py-2" role="alert">
          {{ error }}
          <router-link v-if="expired" class="underline" :to="{ name: 'forgot-password', query: { email } }">Send a new link</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../../stores/auth';

export default {
  name: 'ResetPassword',
  setup() {
    return { authStore: useAuthStore() };
  },
  data() {
    const email = this.$route.query.email || '';
    return {
      email,
      password: '',
      confirmation: '',
      busy: false,
      expired: false,
      error: email ? null : 'This link is incomplete. Open the link from your email again, or ask for a new one.',
    };
  },
  methods: {
    async save() {
      this.error = null;
      this.expired = false;
      if (this.password !== this.confirmation) {
        this.error = 'The two passwords don\'t match.';
        return;
      }
      this.busy = true;
      try {
        await this.authStore.resetPassword({
          token: this.$route.params.token,
          email: this.email,
          password: this.password,
          password_confirmation: this.confirmation,
        });
        this.$router.push({ name: 'login', query: { email: this.email, reset: 1 } });
      } catch (err) {
        const errors = err.response?.data?.errors ?? {};
        // Laravel reports a used or expired token against the email field.
        this.expired = Boolean(errors.email);
        this.error = errors.password?.[0]
          || (errors.email ? 'This reset link has expired or has already been used.' : null)
          || err.response?.data?.message
          || 'We couldn\'t change your password. Please try again.';
      } finally {
        this.busy = false;
      }
    },
  },
};
</script>
