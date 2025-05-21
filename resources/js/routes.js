import Login from "./views/login/Login.vue";
import Itinerary from "./views/Itinerary.vue";
import Search from "./views/search/Search.vue";

const routes = [
  { path: '/', component: Itinerary },
  { path: '/:id', component: Itinerary },
  { path: '/login' , component: Login },
  { path: '/search' , component: Search },
  { path: '/accommodations' , component: Search },
  { path: '/events' , component: Search },
  { path: '/locations' , component: Search },
  { path: '/restaurants' , component: Search },
  { path: '/trails' , component: Search },
  // { path: '/questionnaire' , component: Preferences },
]