import { createApp } from 'vue'
import App from './views/App.vue'
import Vue3TouchEvents from "vue3-touch-events";
import 'flowbite';

const app = createApp(App)

app.use(Vue3TouchEvents)

app.mount('#app')