<template>
  <img alt="Kanab Trip Planner" class="mx-auto opacity-60 h-16 w-auto flex landscape:hidden" src="images/icons/hummingbird.png">
  <div
    class="flex h-full justify-center items-center px-6 w-full flex-col landscape:flex-row gap-8 landscape:gap-16 lg:px-8">

    <div class="flex flex-col w-full sm:max-w-sm">
      <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-white">Register a new account</h2>

      <form class="space-y-6 mt-10" @submit.prevent="register">
        <div>
          <label class="block text-sm font-medium text-white" for="email">Email address</label>
          <input id="email" autocomplete="email" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" name="email" required
                 type="email" v-model="email">
        </div>
        <div>
          <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-white" for="password">Password</label>
          </div>
          <input id="password" autocomplete="current-password" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" name="password" required
                 type="password" v-model="password">
        </div>
        <div>
          <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-white" for="password">Confirm password</label>
          </div>
          <input id="password" autocomplete="current-password" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" name="password" required
                 type="password" v-model="password_confirmation">
        </div>
        <div>
          <button class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-indigo-600"
                  type="submit" @click="register()">
            Create account
          </button>
        </div>

        <div class="h-[64px]">
          <span class="text-white" v-if="error">{{ error }}</span>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Register',
  data() {
    return {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      error: null
    };
  },
  methods: {
    async register() {
      this.error = null;

      try {
        await this.$axiosHelper.post('/auth/register', {
          name: this.name,
          email: this.email,
          password: this.password,
          password_confirmation: this.password_confirmation,
        });
        this.$router.push('/');
      } catch (err) {
        this.error = err.response?.data?.message || 'Registration failed.';
      }
    }
  }
};
</script>
