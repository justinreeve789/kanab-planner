<template>
  <img alt="Kanab Trip Planner" class="mx-auto opacity-60 h-16 w-auto flex landscape:hidden" src="/images/icons/hummingbird.png">
  <div class="flex h-full justify-center items-center px-6 w-full flex-col landscape:flex-row gap-8 landscape:gap-16 lg:px-8">
    <div class="flex flex-col w-full sm:max-w-sm">
      <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-white">Register a new account</h2>

      <form class="space-y-5 mt-8" @submit.prevent="register">
        <div v-for="field in fields" :key="field.id">
          <label class="block text-sm font-medium text-white" :for="field.id">{{ field.label }}</label>
          <input :id="field.id" v-model="form[field.model]" :type="field.type" :autocomplete="field.autocomplete" required
                 class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm">
          <p v-if="errors[field.model]" class="text-xs text-white bg-red-600/70 rounded px-2 py-1 mt-1">{{ errors[field.model][0] }}</p>
        </div>
        <button :disabled="busy" type="submit"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-indigo-600">
          Create account
        </button>
        <p v-if="error" class="text-sm text-white bg-red-600/70 rounded px-3 py-2" role="alert">{{ error }}</p>
        <p class="text-center text-sm text-white">
          Already have an account? <router-link class="underline" :to="{ name: 'login' }">Sign in</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../../stores/auth';

export default {
  name: 'Register',
  setup() {
    return { authStore: useAuthStore() };
  },
  data() {
    return {
      form: { name: '', email: '', password: '', password_confirmation: '' },
      fields: [
        { id: 'register-name', label: 'Name', model: 'name', type: 'text', autocomplete: 'name' },
        { id: 'register-email', label: 'Email address', model: 'email', type: 'email', autocomplete: 'email' },
        { id: 'register-password', label: 'Password', model: 'password', type: 'password', autocomplete: 'new-password' },
        { id: 'register-password-confirmation', label: 'Confirm password', model: 'password_confirmation', type: 'password', autocomplete: 'new-password' },
      ],
      errors: {},
      error: null,
      busy: false,
    };
  },
  methods: {
    async register() {
      this.error = null;
      this.errors = {};
      this.busy = true;
      try {
        await this.authStore.register(this.form);
        this.$router.push('/');
      } catch (err) {
        this.errors = err.response?.data?.errors ?? {};
        this.error = Object.keys(this.errors).length ? null : (err.response?.data?.message || 'Registration failed.');
      } finally {
        this.busy = false;
      }
    },
  },
};
</script>
