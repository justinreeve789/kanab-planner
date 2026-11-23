import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

// Frontend unit tests (npm test). Laravel Mix still builds the app; Vitest only runs tests/js.
export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    include: ['tests/js/**/*.spec.js'],
  },
});
