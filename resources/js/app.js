import { register } from 'swiper/element/bundle';
register();

import { createApp } from 'vue'
import App from './views/App.vue'
import 'flowbite';

const app = createApp(App)

app.mount('#app')