import { defineStore } from 'pinia';
import axiosHelper from '../axiosHelper';

/**
 * The signed-in user. Auth is the Laravel session (Sanctum's first-party SPA mode), so there's no token to keep:
 * the browser's cookies carry it, and axios sends the CSRF header automatically.
 */
export const useAuthStore = defineStore('auth', {
  state: () => ({
    isLoginOpen: false,
    user: null,
    checked: false,
  }),
  getters: {
    currentUser: (state) => state.user,
  },
  actions: {
    set_user(user) {
      this.user = user;
    },
    async attempt_user() {
      try {
        const { data } = await axiosHelper.get('/auth/user', { persistent: true });
        this.user = data && data.id ? data : null;
      } catch (error) {
        this.user = null;
      } finally {
        this.checked = true;
      }
      return this.user;
    },
    async login(email, password, remember = true) {
      await axiosHelper.post('/auth/login', { email, password, remember });
      return this.attempt_user();
    },
    async register(fields) {
      await axiosHelper.post('/auth/register', fields);
      return this.attempt_user();
    },
    /** Always resolves with the same message whether or not the address has an account. */
    async requestPasswordReset(email) {
      const { data } = await axiosHelper.post('/auth/forgot-password', { email });
      return data.message;
    },
    async resetPassword({ token, email, password, password_confirmation }) {
      await axiosHelper.post('/auth/reset-password', { token, email, password, password_confirmation });
    },
    async logout() {
      await axiosHelper.post('/auth/logout');
      this.$reset();
      this.checked = true;
    },
  },
});
