<template>
  <img alt="Kanab Trip Planner" class="mx-auto opacity-60 h-16 w-auto flex landscape:hidden" src="images/icons/hummingbird.png">
  <div
    class="flex h-full justify-center items-center px-6 w-full flex-col landscape:flex-row gap-8 landscape:gap-16 lg:px-8">

    <div class="flex flex-col w-full sm:max-w-sm">
      <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-white">Sign in to your account</h2>

      <form action="#" class="space-y-6 mt-10" method="POST">
        <div>
          <label class="block text-sm font-medium text-white" for="email">Email address</label>
          <input id="email" autocomplete="email" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" name="email" required
                 type="email">
        </div>
        <div>
          <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-white" for="password">Password</label>
            <div class="text-sm">
              <a class="font-semibold text-underline text-white" href="#">Forgot password?</a>
            </div>
          </div>
          <input id="password" autocomplete="current-password" class="mt-2 block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-indigo-600 sm:text-sm" name="password" required
                 type="password">
        </div>
        <div>
          <button class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 font-semibold text-sm text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-indigo-600"
                  type="submit">
            Sign in
          </button>
        </div>
      </form>
    </div>

    <div class="divider divider-primary text-white opacity-80 landscape:divider-horizontal">OR</div>

    <div class="flex flex-col sm:w-full sm:max-w-sm">
      <h2 class="text-center text-2xl font-bold tracking-tight text-white">Create an account</h2>
      <p class="mt-4 text-center text-sm text-white">Save your itineraries, view trail maps, and get help from the AI assistant</p>
      <router-link :to="{ name: 'register' }" class="mt-6 flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-indigo-600">
        Register account
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Login',
  data() {
    return {
      email: '',
      password: '',
      error: null
    };
  },
  methods: {
    async login() {
      this.error = null;

      try {
        await this.$axiosHelper.post('/login', {
          email: this.email,
          password: this.password
        });
        this.$router.push('/');
      } catch (err) {
        this.error = err.response?.data?.message || 'Login failed.';
      }
    }
  }
};
</script>