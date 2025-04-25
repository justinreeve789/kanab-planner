import { register } from 'swiper/element/bundle';
register();

import { createApp } from 'vue'
import App from './views/App.vue'
import { createPinia } from 'pinia';

const app = createApp({});
app.component('app', App);
const pinia = createPinia();

app.config.globalProperties.$generateUnique = function() {
  const length = 32;
  return [...Array(length)].map(() => Math.random().toString(36).substring(2)).join('').substring(0, length);
};

app.use(pinia);

app.mount('#app');