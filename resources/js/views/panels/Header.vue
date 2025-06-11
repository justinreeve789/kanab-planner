<template>
  <header class="sticky top-0 z-50 w-full bg-base-100 shadow">
    <div class="navbar bg-neutral text-neutral-content w-full">
      <div class="navbar-start">
        <div class="dropdown">
          <div class="btn btn-ghost lg:hidden" role="button" tabindex="0">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 6h16M4 12h8m-8 6h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
            </svg>
          </div>
          <ul class="menu menu-lg dropdown-content bg-base-100 bg-neutral text-neutral-content rounded-b-box z-1 mt-3 w-52 p-2 shadow" tabindex="0">
            <li class="hidden md:inline"><a class="justify-between" @click="openItinerary">Itinerary<span class="badge">New</span></a></li>
            <li><router-link :to="{ name: 'accommodations'} ">Accommodations</router-link></li>
            <li><router-link :to="{ name: 'events'} ">Events</router-link></li>
            <li><router-link :to="{ name: 'locations'} ">Locations</router-link></li>
            <li><router-link :to="{ name: 'restaurants'} ">Restaurants</router-link></li>
            <li><router-link :to="{ name: 'trails'} ">Trails</router-link></li>
          </ul>
        </div>
        <span class="cursor-pointer font-bold hidden text-xl sm:block" @click="openItinerary">Trip Planner</span>
        <button class="btn btn-ghost btn-circle md:ml-6" @click="selectDates" v-if="isItineraryOpen">
          <svg class="w-6 h-6 text-white dark:text-black" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
             <path fill-rule="evenodd" d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z" clip-rule="evenodd"/>
          </svg>
        </button>
        <div class="flex items-center ml-2 relative space-x-2" v-if="isItineraryOpen">
          <div class="text-center leading-tight">
            <div class="text-xs">{{ getFormattedDate(getStartDate).month }}</div>
            <div class="text-base">{{ getFormattedDate(getStartDate).day }}</div>
          </div>
          <span class="text-xs" v-if="hasDateRange">&ndash;</span>
          <div class="text-center leading-tight">
            <div class="text-xs">{{ getFormattedDate(getEndDate).month }}</div>
            <div class="text-base">{{ getFormattedDate(getEndDate).day }}</div>
          </div>
          <div class="year" style="font-size: 40px; opacity: 0.1; margin: 0; position: absolute; top: 50%; left: 50%; transform: translateX(-50%) translateY(-50%);">
            {{ getFormattedDate(getStartDate).year }}
          </div>
        </div>
      </div>
      <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal menu-md px-1">
          <li :class="$route.name === 'itinerary' || $router.name === 'itinerary-new' ? 'underline': ''"><a @click="openItinerary">Itinerary</a></li>
          <li :class="$route.name === 'accommodations' ? 'underline' : ''"><router-link :to="{ name: 'accommodations'} ">Accommodations</router-link></li>
          <li :class="$route.name === 'events' ? 'underline' : ''"><router-link :to="{ name: 'events'} ">Events</router-link></li>
          <li :class="$route.name === 'locations' ? 'underline' : ''"><router-link :to="{ name: 'locations'} ">Locations</router-link></li>
          <li :class="$route.name === 'restaurants' ? 'underline' : ''"><router-link :to="{ name: 'restaurants'} ">Restaurants</router-link></li>
          <li :class="$route.name === 'trails' ? 'underline' : ''"><router-link :to="{ name: 'trails'} ">Trails</router-link></li>
        </ul>
      </div>
      <div class="navbar-end">
        <button class="btn btn-ghost btn-circle p-3" @click="refreshItinerary" v-if="isItineraryOpen">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" id="refresh">
            <path fill="#ffffff" d="M2.6 5.6C3.5 3.5 5.6 2 8 2c3 0 5.4 2.2 5.9 5h2c-.5-3.9-3.8-7-7.9-7-3 0-5.6 1.6-6.9 4.1L0 3v4h4L2.6 5.6zM16 9h-4.1l1.5 1.4c-.9 2.1-3 3.6-5.5 3.6C5 14 2.5 11.8 2 9H0c.5 3.9 3.9 7 7.9 7 3 0 5.6-1.7 7-4.1L16 13V9z"></path>
          </svg>
        </button>
        <button class="btn btn-ghost btn-circle hidden md:flex" @click="toggleSearch">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        </button>
        <button class="btn btn-ghost btn-circle p-2 md:flex" @click="toggleSettings">
          <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0,0,256,256" style="fill:#ffffff;">
            <g fill="#ffffff" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal"><g transform="scale(5.12,5.12)"><path d="M22.20508,2c-0.48953,0.00026 -0.90693,0.35484 -0.98633,0.83789l-0.97266,5.95508c-1.16958,0.34023 -2.28485,0.7993 -3.33594,1.37109l-4.91406,-3.50977c-0.39728,-0.28369 -0.94131,-0.23911 -1.28711,0.10547l-3.89062,3.88672c-0.3432,0.34344 -0.39015,0.88376 -0.11133,1.28125l3.45703,4.94531c-0.58061,1.05722 -1.04985,2.17878 -1.39844,3.35938l-5.92969,0.98633c-0.4815,0.0811 -0.83404,0.49805 -0.83398,0.98633v5.5c-0.00088,0.48518 0.3466,0.901 0.82422,0.98633l5.93359,1.05078c0.3467,1.17855 0.81296,2.30088 1.39453,3.35937l-3.5,4.89648c-0.28369,0.39728 -0.23911,0.94131 0.10547,1.28711l3.88867,3.89063c0.34265,0.34275 0.88175,0.39048 1.2793,0.11328l4.95508,-3.46875c1.05419,0.57517 2.17218,1.03762 3.3457,1.38086l0.99023,5.96289c0.08025,0.48228 0.49742,0.83584 0.98633,0.83594h5.5c0.4858,0.00071 0.90184,-0.34778 0.98633,-0.82617l1.06055,-5.98633c1.16868,-0.3485 2.28142,-0.8178 3.33008,-1.39648l4.98828,3.5c0.39749,0.27882 0.93781,0.23187 1.28125,-0.11133l3.88867,-3.89258c0.34612,-0.34687 0.38995,-0.89343 0.10352,-1.29102l-3.55664,-4.9375c0.56867,-1.04364 1.02681,-2.14972 1.36719,-3.31055l6.01758,-1.05469c0.47839,-0.08448 0.82689,-0.50053 0.82617,-0.98633v-5.5c-0.00026,-0.48953 -0.35484,-0.90693 -0.83789,-0.98633l-6.00781,-0.98242c-0.34266,-1.15945 -0.80206,-2.26356 -1.37109,-3.30664l3.50781,-4.99805c0.27882,-0.39749 0.23187,-0.93781 -0.11133,-1.28125l-3.89062,-3.88867c-0.34687,-0.34612 -0.89343,-0.38995 -1.29102,-0.10352l-4.92383,3.54102c-1.04908,-0.57636 -2.16255,-1.04318 -3.33398,-1.38867l-1.04687,-5.98437c-0.08364,-0.47917 -0.49991,-0.82867 -0.98633,-0.82812zM23.05664,4h3.80859l0.99609,5.68555c0.06772,0.38959 0.35862,0.70269 0.74219,0.79883c1.46251,0.36446 2.83609,0.94217 4.08984,1.70117c0.34265,0.20761 0.77613,0.1907 1.10156,-0.04297l4.67969,-3.36328l2.69336,2.69336l-3.33203,4.74805c-0.22737,0.3236 -0.24268,0.75079 -0.03906,1.08984c0.75149,1.25092 1.32146,2.61583 1.68555,4.07031c0.0969,0.38717 0.41473,0.67966 0.80859,0.74414l5.70703,0.93359v3.80859l-5.71875,1.00391c-0.3899,0.06902 -0.70237,0.36157 -0.79687,0.74609c-0.35988,1.45263 -0.93019,2.8175 -1.68164,4.06836c-0.20617,0.34256 -0.18851,0.775 0.04492,1.09961l3.37891,4.68945l-2.69336,2.69531l-4.74023,-3.32617c-0.32527,-0.22783 -0.75452,-0.24163 -1.09375,-0.03516c-1.24752,0.75899 -2.62251,1.33943 -4.08008,1.70898c-0.38168,0.09622 -0.67142,0.40737 -0.74023,0.79492l-1.00977,5.6875h-3.81445l-0.94141,-5.66211c-0.06549,-0.39365 -0.35874,-0.7107 -0.74609,-0.80664c-1.46338,-0.36069 -2.84314,-0.93754 -4.10547,-1.69531c-0.33857,-0.20276 -0.76473,-0.18746 -1.08789,0.03906l-4.70312,3.29492l-2.69531,-2.69922l3.32422,-4.64648c0.23221,-0.3254 0.24834,-0.75782 0.04102,-1.09961c-0.76602,-1.26575 -1.34535,-2.6454 -1.71094,-4.11523c-0.09555,-0.38244 -0.40684,-0.67307 -0.79492,-0.74219l-5.63086,-1v-3.81445l5.62695,-0.93555c0.39312,-0.06519 0.71002,-0.35754 0.80664,-0.74414c0.36873,-1.4749 0.94778,-2.85432 1.71094,-4.11719c0.20562,-0.33876 0.19183,-0.76697 -0.03516,-1.0918l-3.28516,-4.69531l2.69727,-2.69531l4.66211,3.33203c0.32413,0.23112 0.75447,0.248 1.0957,0.04297c1.25566,-0.75415 2.63862,-1.32636 4.10352,-1.68555c0.38927,-0.09584 0.68369,-0.41486 0.74805,-0.81055zM25,17c-4.40643,0 -8,3.59357 -8,8c0,4.40643 3.59357,8 8,8c4.40643,0 8,-3.59357 8,-8c0,-4.40643 -3.59357,-8 -8,-8zM25,19c3.32555,0 6,2.67445 6,6c0,3.32555 -2.67445,6 -6,6c-3.32555,0 -6,-2.67445 -6,-6c0,-3.32555 2.67445,-6 6,-6z"></path></g></g>
          </svg>        </button>
        <div>
          <ItineraryStats class="cursor-pointer mx-2" @click="toggleAttributes" v-if="isItineraryOpen" />
        </div>
        <button class="btn btn-ghost btn-circle" v-if="getUser">
          <div class="indicator">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /> </svg>
            <span class="badge badge-xs badge-primary indicator-item"></span>
          </div>
        </button>
        <router-link class="btn btn-sm flex ml-2" v-if="!getUser" :to="{ name: 'login'} ">Login</router-link>
        <div class="dropdown dropdown-end hidden md:flex" v-if="getUser">
          <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
            <div class="w-10 rounded-full">
              <img
                alt="Tailwind CSS Navbar component"
                src="https://img.daisyui.com/images/stock/photo-1534528741775-53994a69daeb.webp" />
            </div>
          </div>
          <ul tabindex="0" class="menu menu-lg dropdown-content bg-base-100 bg-neutral text-neutral-content rounded-b-box z-1 mt-3 w-52 p-2 shadow">
<!--            <li class="hidden md:inline" v-if="!getUser"><a @click="openLogin">Login</a></li>-->
            <li v-if="getUser"><a>Profile</a></li>
            <li v-if="getUser"><a>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </header>
  <CallyCalendar v-if="isItineraryOpen" v-show="isCalendarOpen" v-model.lazy="dateRange" />
</template>

<script>
import CallyCalendar from "../components/CallyCalendar.vue";
import { useAssistantStore } from "../../stores/assistant";
import { useAuthStore } from "../../stores/auth";
import { usePreferencesStore } from "../../stores/preferences";
import { useSearchStore } from "../../stores/search";
import ItineraryStats from "../components/ItineraryStats.vue";

export default {
  setup() {
    const assistantStore = useAssistantStore();
    const authStore = useAuthStore();
    const preferencesStore = usePreferencesStore();
    const searchStore = useSearchStore();
    return { assistantStore, authStore, preferencesStore, searchStore };
  },
  components: {
    CallyCalendar,
    ItineraryStats,
  },
  props: {
    itineraryId: {
      type: String,
      required: false,
    }
  },
  watch: {
    dateRange(newDates, oldDates) {
      if (newDates.start !== oldDates.start && newDates.end !== oldDates.end) {
        this.preferencesStore.dateRange = newDates;
        this.isCalendarOpen = false;
      }
    },
  },
  data() {
    return {
      completedQuestionnaire: false,
      openQuestionnaire: false,
      isCalendarOpen: false,
      dateRange: {
        start: '',
        end: '',
      },
    };
  },
  computed: {
    getEndDate() {
      if (this.preferencesStore.dateRange) {
        return this.preferencesStore.dateRange.end;
      }
    },
    getStartDate() {
      if (this.preferencesStore.dateRange) {
        return this.preferencesStore.dateRange.start ?? null;
      }
    },
    getUser() {
      return this.authStore.user;
    },
    hasDateRange() {
      return this.preferencesStore.dateRange;
    },
    isItineraryOpen() {
      return !this.assistantStore.isAssistantOpen && !this.preferencesStore.isPreferencesOpen && !this.searchStore.isSearchOpen && !this.authStore.isLoginOpen;
    },
    isPreferencesOpen() {
      return this.preferencesStore.isPreferencesOpen;
    }
  },
  methods: {
    getFeatureFlags() {
    },
    getFormattedDate(date) {
      if (date) {
        const dateObj = new Date(date);
        return {
          month: dateObj.toLocaleString('en-us', {
            month: 'short',
          }),
          day: dateObj.getUTCDate(),
          year: dateObj.getUTCFullYear(),
        };
      }
      return { month: '', day: '', year: ''};
    },
    openAccommodations() {
      this.searchStore.adjustSettingsForPage('accommodations');
      this.searchStore.isSearchOpen = true;
    },
    openEvents() {
      this.searchStore.adjustSettingsForPage('events');
      this.searchStore.isSearchOpen = true;
    },
    openItinerary() {
      if (this.$cookies.get('active_itinerary_id')) {
        this.$router.push('/' + this.$cookies.get('active_itinerary_id'));
      } else {
        this.$router.push('/');
      }
    },
    openLocations() {
      this.searchStore.adjustSettingsForPage('locations');
      this.searchStore.isSearchOpen = true;
    },
    openLogin() {
      this.authStore.isLoginOpen = true;
      this.$router.push('/login');
    },
    openRestaurants() {
      this.searchStore.adjustSettingsForPage('restaurants');
      this.searchStore.isSearchOpen = true;
    },
    openTrails() {
      this.searchStore.adjustSettingsForPage('trails');
      // this.searchStore.isSearchOpen = true;
      this.$router.push('/trails');
    },
    refreshItinerary() {
      // this.$cookies.remove('active_itinerary_id');
      // this.preferencesStore.refreshItinerary();
      this.preferencesStore.refresh = true;
      // this.$router.push('/');
    },
    selectDates() {
      this.isCalendarOpen = !this.isCalendarOpen;
    },
    toggleAttributes() {
      this.preferencesStore.currentPreferencesStep = 3;
      this.$router.push('/questionnaire-about');
      // this.preferencesStore.isPreferencesOpen = !this.preferencesStore.isPreferencesOpen;
    },
    toggleSettings() {
      this.$router.push('/settings');
    },
    toggleSearch() {
      this.$addHashToUrl('#modal-search');
      // this.searchStore.adjustSettingsForGeneralSearch();
      // this.assistantStore.isAssistantOpen = false;
      // this.authStore.isLoginOpen = false;
      // this.preferencesStore.isPreferencesOpen = false;
      // this.searchStore.isSearchOpen = true;
    },
  },
  mounted() {
    this.getFeatureFlags();
  },
};
</script>
