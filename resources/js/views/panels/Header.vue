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
            <li><a @click="openAccommodations">Accommodations</a></li>
            <li><a @click="openEvents">Events</a></li>
            <li><a @click="openLocations">Locations</a></li>
            <li><a @click="openRestaurants">Restaurants</a></li>
            <li><a @click="openTrails">Trails</a></li>
          </ul>
        </div>
        <span class="font-bold text-xl hidden sm:block">Trip Planner</span>
        <button class="btn btn-ghost btn-circle" @click="selectDates">
          <svg class="w-6 h-6 text-white dark:text-black" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M5 5a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1h1a1 1 0 0 0 1-1 1 1 0 1 1 2 0 1 1 0 0 0 1 1 2 2 0 0 1 2 2v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a2 2 0 0 1 2-2ZM3 19v-7a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm6.01-6a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm-10 4a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm6 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0Zm2 0a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z" clip-rule="evenodd"/>
          </svg>
        </button>
      </div>
      <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal menu-md px-1">
          <li><a @click="openItinerary">Itinerary</a></li>
          <li><a @click="openAccommodations">Accommodations</a></li>
          <li><a @click="openEvents">Events</a></li>
          <li><a @click="openLocations">Locations</a></li>
          <li><a @click="openRestaurants">Restaurants</a></li>
          <li><a @click="openTrails">Trails</a></li>
        </ul>
      </div>
      <div class="navbar-end">
        <button class="btn btn-ghost btn-circle hidden md:flex" @click="toggleSearch">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        </button>
        <button class="btn btn-ghost btn-circle">
          <div class="indicator">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /> </svg>
            <span class="badge badge-xs badge-primary indicator-item"></span>
          </div>
        </button>
        <button class="btn btn-ghost btn-circle hidden md:flex" @click="togglePreferences">
          <svg class="w-6 h-6 text-gray-800 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M10.83 5a3.001 3.001 0 0 0-5.66 0H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17ZM4 11h9.17a3.001 3.001 0 0 1 5.66 0H20a1 1 0 1 1 0 2h-1.17a3.001 3.001 0 0 1-5.66 0H4a1 1 0 1 1 0-2Zm1.17 6H4a1 1 0 1 0 0 2h1.17a3.001 3.001 0 0 0 5.66 0H20a1 1 0 1 0 0-2h-9.17a3.001 3.001 0 0 0-5.66 0Z"/>
          </svg>
        </button>
        <button class="btn btn-sm flex ml-2" v-if="!getUser" @click="openLogin">
          Login
        </button>
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
  <CallyCalendar v-show="isCalendarOpen" v-model.lazy="dateRange" />
</template>

<script>
import CallyCalendar from "../components/CallyCalendar.vue";
import { useAuthStore } from "../../stores/auth";
import { usePreferencesStore } from "../../stores/preferences";
import { useSearchStore } from "../../stores/search";

export default {
  setup() {
    const authStore = useAuthStore();
    const preferencesStore = usePreferencesStore();
    const searchStore = useSearchStore();
    return { authStore, preferencesStore, searchStore };
  },
  components: {
    CallyCalendar,
  },
  props: {
    itineraryId: {
      type: String,
      required: false,
    }
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
    getUser() {
      return this.authStore.user;
    },
  },
  methods: {
    getFeatureFlags() {
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
      this.authStore.isLoginOpen = false;
    },
    openLocations() {
      this.searchStore.adjustSettingsForPage('locations');
      this.searchStore.isSearchOpen = true;
    },
    openLogin() {
      this.authStore.isLoginOpen = true;
    },
    openRestaurants() {
      this.searchStore.adjustSettingsForPage('restaurants');
      this.searchStore.isSearchOpen = true;
    },
    openTrails() {
      this.searchStore.adjustSettingsForPage('trails');
      this.searchStore.isSearchOpen = true;
    },
    selectDates() {
      this.isCalendarOpen = !this.isCalendarOpen;
    },
    togglePreferences() {
      this.preferencesStore.isPreferencesOpen = !this.preferencesStore.isPreferencesOpen;
    },
    toggleSearch() {
      this.searchStore.isSearchOpen = !this.searchStore.isSearchOpen;
    },
  },
  mounted() {
    this.getFeatureFlags();
    this.openQuestionnaire = true;
  },
};
</script>
