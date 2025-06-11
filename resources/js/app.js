// import { register } from 'swiper/element/bundle';
// register();

import { createApp } from 'vue'
import App from './views/App.vue'
import { createPinia } from 'pinia';
import VueCookies from 'vue-cookies';
import Vue3TouchEvents from 'vue3-touch-events';
import { createRouter, createWebHistory } from 'vue-router';
import './routes';
import './axiosHelper';
import Itinerary from "./views/Itinerary.vue";
import axiosHelper from "./axiosHelper";
import AssistantPage from "./views/pages/AssistantPage.vue";
import QuestionnairePage from './views/pages/QuestionnairePage.vue';
import ResourcePage from "./views/pages/ResourcePage.vue";
import SettingsPage from "./views/pages/SettingsPage.vue";
import UserPage from "./views/pages/UserPage.vue";

const app = createApp({});
app.component('app', App);
const pinia = createPinia();

const routes = [
  { path: '/', component: Itinerary, name: 'itinerary-new' },
  { path: '/login', component: UserPage, name: 'login' },
  { path: '/register', component: UserPage, name: 'register' },
  { path: '/assistant', component: AssistantPage, name: 'assistant' },
  { path: '/search' , component: ResourcePage, name: 'search', props: { page: 'search', title: 'Search' } },
  { path: '/accommodations', component: ResourcePage, name: 'accommodations', props: { page: 'accommodations', title: 'Accommodations' } },
  { path: '/events', component: ResourcePage, name: 'events', props: { page: 'events', title: 'Events' } },
  { path: '/locations', component: ResourcePage, name: 'locations', props: { page: 'locations', title: 'Locations' } },
  { path: '/restaurants', component: ResourcePage, name: 'restaurants', props: { page: 'restaurants', title: 'Restaurants' } },
  { path: '/trails', component: ResourcePage, name: 'trails', props: { page: 'trails', title: 'Trails' } },
  { path: '/questionnaire', component: QuestionnairePage, name: 'questionnaire', props: { page: 'start', step: 0 } },
  { path: '/questionnaire-dates', component: QuestionnairePage, name: 'questionnaire-dates', props: { page: 'dates', step: 1 } },
  { path: '/questionnaire-activities', component: QuestionnairePage, name: 'questionnaire-activities', props: { page: 'activities', step: 2 } },
  { path: '/questionnaire-about', component: QuestionnairePage, name: 'questionnaire-about', props: { page: 'about', step: 3 } },
  { path: '/questionnaire-destinations', component: QuestionnairePage, name: 'questionnaire-destinations', props: { page: 'destinations', step: 4 } },
  { path: '/questionnaire-complete', component: QuestionnairePage, name: 'questionnaire-complete', props: { page: 'complete', step: 5 } },
  { path: '/settings' , component: SettingsPage, name: 'settings' },
  { path: '/:itineraryId', component: Itinerary, name: 'itinerary', props: false },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from) => {
  try {
    const parsedUrl1 = new URL(to.fullPath, window.location.origin); // Use window.location.origin for relative URLs
    const parsedUrl2 = new URL(from.fullPath, window.location.origin); // Use window.location.origin for relative URLs

    parsedUrl1.hash = '';
    parsedUrl2.hash = '';

    if (parsedUrl1.toString() !== parsedUrl2.toString()) {
      axiosHelper.cancelAllGetRequests();
    }
  } catch(error) {
    axiosHelper.cancelAllGetRequests();
  }
  // No need to call next() here, Vue Router will handle the navigation flow
});

app.config.globalProperties.$axiosHelper = axiosHelper;

app.config.globalProperties.$generateUnique = function() {
  const length = 32;
  return [...Array(length)].map(() => Math.random().toString(36).substring(2)).join('').substring(0, length);
};

app.config.globalProperties.$addHashToUrl = function(hash) {
  const cleanPath = window.location.pathname + window.location.search + hash;
  this.$router.push(cleanPath);
};

app.config.globalProperties.$removeHashFromUrl = function() {
  if (window.location.hash) {
    const cleanPath = window.location.pathname + window.location.search;
    this.$router.push(cleanPath);
  }
};

// app.config.globalProperties.$setCookie = function(id, value) {
//   document.cookie = id + '=' + value;
// }
//
// app.config.globalProperties.$getCookie = function(id) {
//   let value = document.cookie.match('(^|;)?' + id + '=([^;]*)(;|$)');
//   return value ? unescape(value[2]) : null;
// }
//
// app.config.globalProperties.$deleteCookie = function(id) {
//   document.cookie = id + '=;';
// }

app.use(pinia);
app.use(router);
app.use(VueCookies, { expires: '1d' });
app.use(Vue3TouchEvents, {
  disableClick: true,
});

app.mount('#app');