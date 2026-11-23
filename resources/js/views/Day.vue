<template>
  <article :id="'day-' + day.day_num" class="day-card scroll-mt-32 rounded-2xl bg-white shadow-sm border border-stone-200 overflow-hidden">
    <header class="flex items-center gap-3 p-3 sm:p-4 cursor-pointer select-none" @click="open = !open">
      <div class="date-badge w-14 shrink-0 text-center rounded-xl bg-stone-900 text-white py-1.5 leading-none">
        <div class="text-[10px] uppercase tracking-wider text-stone-300">{{ weekday }}</div>
        <div class="text-2xl font-bold my-0.5">{{ dayOfMonth }}</div>
        <div class="text-[10px] uppercase tracking-wider text-stone-300">{{ month }}</div>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-stone-500">
          Day {{ day.day_num + 1 }}
          <span v-if="day.drive_minutes" class="font-normal normal-case tracking-normal">· {{ formatDuration(day.drive_minutes) }} drive each way</span>
        </p>
        <h2 class="text-lg sm:text-xl font-semibold text-stone-900 truncate">{{ day.area.name }}</h2>
        <p class="text-xs sm:text-sm text-stone-500 truncate">{{ summaryLine }}</p>
      </div>
      <Weather class="hidden sm:flex text-stone-600" :weather="day.weather" />
      <svg class="w-5 h-5 text-stone-400 transition-transform shrink-0" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M5.2 7.2a.75.75 0 0 1 1.06.02L10 11.1l3.74-3.88a.75.75 0 1 1 1.08 1.04l-4.28 4.44a.75.75 0 0 1-1.08 0L5.18 8.26a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
      </svg>
    </header>

    <div v-show="open" class="border-t border-stone-100">
      <div v-if="day.preamble || editingNote || canEdit" class="px-4 pt-3">
        <form v-if="editingNote" class="flex flex-col gap-2" @submit.prevent="saveNote">
          <textarea v-model="note" class="textarea textarea-sm w-full" rows="2" maxlength="2000" placeholder="A note for this day"></textarea>
          <div class="flex gap-2">
            <button class="btn btn-sm btn-primary" type="submit">Save note</button>
            <button class="btn btn-sm btn-ghost" type="button" @click="editingNote = false">Cancel</button>
          </div>
        </form>
        <p v-else-if="day.preamble" class="text-stone-600 italic">
          {{ day.preamble }}
          <button v-if="canEdit" class="link link-hover text-xs not-italic ml-1" @click="startNote">Edit</button>
        </p>
        <button v-else class="link link-hover text-xs text-stone-500" @click="startNote">+ Add a note</button>
      </div>

      <ol v-if="schedule.length" class="py-2">
        <TimelineItem
          v-for="item in schedule"
          :key="item.key"
          :item="item"
          :can-edit="canEdit"
          @swap="swap"
          @remove="remove"
          @update-time="updateTime"
        />
      </ol>
      <p v-else class="px-4 py-6 text-center text-stone-500">Nothing planned. Enjoy a free day.</p>

      <form v-if="addingItem" class="flex flex-wrap items-end gap-2 px-4 pb-3" @submit.prevent="addItem">
        <label class="text-xs text-stone-600 flex-1 min-w-[12rem]">What
          <input v-model="newItem.name" required maxlength="255" class="input input-sm block w-full" placeholder="Ice cream in town" />
        </label>
        <label class="text-xs text-stone-600">Start
          <input v-model="newItem.start_time" type="time" required class="input input-sm block" />
        </label>
        <label class="text-xs text-stone-600">End
          <input v-model="newItem.end_time" type="time" class="input input-sm block" />
        </label>
        <button class="btn btn-sm btn-primary" type="submit">Add</button>
        <button class="btn btn-sm btn-ghost" type="button" @click="addingItem = false">Cancel</button>
      </form>

      <footer class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 bg-stone-50 border-t border-stone-100">
        <div v-if="canEdit" class="flex flex-wrap gap-2">
          <button class="btn btn-sm" :disabled="busy" @click="startAddItem">Add item</button>
          <button class="btn btn-sm" :disabled="busy" @click="regenerate">
            <span v-if="busy" class="loading loading-spinner loading-xs"></span>
            Shuffle this day
          </button>
          <button v-if="schedule.length" class="btn btn-sm btn-ghost" :disabled="busy" @click="clear">Clear day</button>
        </div>
        <div v-else></div>
        <DayStats :stats="day.stats" />
      </footer>
    </div>
  </article>
</template>

<script>
import DayStats from './components/DayStats.vue';
import TimelineItem from './components/TimelineItem.vue';
import Weather from './components/Weather.vue';
import { buildDaySchedule, summarizeDay } from '../lib/schedule';
import { formatDuration, parseDate } from '../lib/time';
import { useItineraryStore } from '../stores/itinerary';
import { useSearchStore } from '../stores/search';

export default {
  components: { DayStats, TimelineItem, Weather },
  props: {
    day: { type: Object, required: true },
    baseArea: { type: Object, default: null },
    canEdit: { type: Boolean, default: false },
  },
  setup() {
    return { itineraryStore: useItineraryStore(), searchStore: useSearchStore() };
  },
  data() {
    return {
      open: true,
      busy: false,
      editingNote: false,
      note: '',
      addingItem: false,
      newItem: { name: '', start_time: '', end_time: '' },
    };
  },
  computed: {
    date() {
      return parseDate(this.day.date);
    },
    weekday() {
      return this.date?.toLocaleDateString('en-US', { weekday: 'short' });
    },
    dayOfMonth() {
      return this.date?.getDate();
    },
    month() {
      return this.date?.toLocaleDateString('en-US', { month: 'short' });
    },
    schedule() {
      return buildDaySchedule(this.day, this.baseArea?.short_name ?? 'base');
    },
    summaryLine() {
      const s = summarizeDay(this.day);
      const parts = [];
      if (s.hikes) {
        parts.push(`${s.hikes} ${s.hikes === 1 ? 'hike' : 'hikes'} (${formatDuration(s.hikeMinutes)})`);
      }
      if (s.events) {
        parts.push(`${s.events} ${s.events === 1 ? 'event' : 'events'}`);
      }
      if (s.mealsOut) {
        parts.push(`${s.mealsOut} ${s.mealsOut === 1 ? 'meal' : 'meals'} out`);
      }
      return parts.length ? parts.join(' · ') : 'A relaxed day';
    },
  },
  methods: {
    formatDuration,
    async run(action) {
      this.busy = true;
      try {
        await action();
      } catch (e) {
        // The store shows the error.
      } finally {
        this.busy = false;
      }
    },
    startNote() {
      this.note = this.day.preamble ?? '';
      this.editingNote = true;
    },
    async saveNote() {
      await this.run(() => this.itineraryStore.updateDayNote(this.day, this.note.trim() || null));
      this.editingNote = false;
    },
    startAddItem() {
      this.newItem = { name: '', start_time: '', end_time: '' };
      this.addingItem = true;
    },
    async addItem() {
      const payload = { ...this.newItem, end_time: this.newItem.end_time || this.newItem.start_time };
      await this.run(() => this.itineraryStore.addActivity(this.day, payload));
      this.addingItem = false;
    },
    regenerate() {
      return this.run(() => this.itineraryStore.regenerateDay(this.day));
    },
    clear() {
      if (window.confirm(`Remove everything from Day ${this.day.day_num + 1}?`)) {
        return this.run(() => this.itineraryStore.clearDay(this.day));
      }
    },
    remove(activity) {
      return this.run(() => this.itineraryStore.removeActivity(activity));
    },
    updateTime(activity, times) {
      return this.run(() => this.itineraryStore.updateActivity(activity, times));
    },
    swap(activity) {
      this.searchStore.title = `Swap ${activity.resource?.name ?? 'activity'}`;
      this.searchStore.target = { component: 'swap', activity, areaId: this.day.area.id, date: this.day.date };
      this.searchStore.isResourceListOpen = true;
    },
  },
};
</script>
