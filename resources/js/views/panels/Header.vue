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
            <li><a>Accommodations</a></li>
            <li><a>Events</a></li>
            <li><a>Locations</a></li>
            <li><a>Restaurants</a></li>
            <li><a>Trails</a></li>
          </ul>
        </div>
        <span class="font-bold text-xl">Trip Planner</span>
      </div>
      <div class="navbar-center hidden lg:flex">
        <ul class="menu menu-horizontal menu-md px-1">
          <li><a>Accommodations</a></li>
          <li><a>Events</a></li>
          <li><a>Locations</a></li>
          <li><a>Restaurants</a></li>
          <li><a>Trails</a></li>
        </ul>
      </div>
      <div class="navbar-end">
        <button class="btn btn-ghost btn-circle">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /> </svg>
        </button>
        <button class="btn btn-ghost btn-circle">
          <div class="indicator">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /> </svg>
            <span class="badge badge-xs badge-primary indicator-item"></span>
          </div>
        </button>
        <button class="btn btn-ghost btn-circle" @click="togglePreferences">
          <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.75 4H19M7.75 4a2.25 2.25 0 0 1-4.5 0m4.5 0a2.25 2.25 0 0 0-4.5 0M1 4h2.25m13.5 6H19m-2.25 0a2.25 2.25 0 0 1-4.5 0m4.5 0a2.25 2.25 0 0 0-4.5 0M1 10h11.25m-4.5 6H19M7.75 16a2.25 2.25 0 0 1-4.5 0m4.5 0a2.25 2.25 0 0 0-4.5 0M1 16h2.25"/>
          </svg>
        </button>
        <div class="dropdown dropdown-end">
          <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
            <div class="w-10 rounded-full">
              <img
                alt="Tailwind CSS Navbar component"
                src="https://img.daisyui.com/images/stock/photo-1534528741775-53994a69daeb.webp" />
            </div>
          </div>
          <ul tabindex="0" class="menu menu-lg dropdown-content bg-base-100 bg-neutral text-neutral-content rounded-b-box z-1 mt-3 w-52 p-2 shadow">
            <li>
              <a class="justify-between">
                Profile
                <span class="badge">New</span>
              </a>
            </li>
            <li><a>Logout</a></li>
          </ul>
        </div>
      </div>
    </div>
  </header>
  <CallyCalendar v-model.lazy="dateRange" />
</template>

<script>
import { ref } from "vue";
import { usePreferencesStore } from "../../stores/preferences";
import CallyCalendar from "../components/CallyCalendar.vue";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return { preferencesStore };
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
      dateRange: {
        start: '',
        end: '',
      },
    };
  },
  methods: {
    getFeatureFlags() {
    },
    togglePreferences() {
      this.preferencesStore.isPreferencesOpen = !this.preferencesStore.isPreferencesOpen;
    },
  },
  mounted() {
    this.getFeatureFlags();
    this.openQuestionnaire = true;
  },
};
</script>
