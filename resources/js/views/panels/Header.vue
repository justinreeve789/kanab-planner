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
        <span class="font-bold text-xl hidden sm:block">Trip Planner</span>
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
          day: dateObj.toLocaleString('en-us', {
            day: 'numeric',
          }),
          year: dateObj.toLocaleString('en-us', {
            year: 'numeric',
          }),
        };
      }
      return { month: '', day: ''};
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
