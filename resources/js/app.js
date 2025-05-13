import { register } from 'swiper/element/bundle';
register();

import { createApp } from 'vue'
import App from './views/App.vue'
import { createPinia } from 'pinia';
import VueCookies from 'vue-cookies';

const app = createApp({});
app.component('app', App);
const pinia = createPinia();

app.config.globalProperties.$generateUnique = function() {
  const length = 32;
  return [...Array(length)].map(() => Math.random().toString(36).substring(2)).join('').substring(0, length);
};

app.config.globalProperties.$setCookie = function(id, value) {
  document.cookie = id + '=' + value;
}

app.config.globalProperties.$getCookie = function(id) {
  let value = document.cookie.match('(^|;)?' + id + '=([^;]*)(;|$)');
  return value ? unescape(value[2]) : null;
}

app.config.globalProperties.$deleteCookie = function(id) {
  document.cookie = id + '=;';
}

app.use(pinia);
app.use(VueCookies, { expires: '1d' });

app.mount('#app');