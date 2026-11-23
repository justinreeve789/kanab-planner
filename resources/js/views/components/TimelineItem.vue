<template>
  <li v-if="item.kind === 'drive'" class="timeline-drive flex items-center gap-3 px-3 sm:px-4 py-1 text-sm text-stone-500">
    <div class="w-16 sm:w-20 shrink-0"></div>
    <div class="w-8 flex justify-center shrink-0">
      <img src="/images/icons/car.svg" alt="" class="w-4 h-4 opacity-60" />
    </div>
    <div class="flex-1 border-l-2 border-dashed border-stone-300 pl-3 py-1">
      {{ item.title }} <span class="text-stone-400">· about {{ formatDuration(item.minutes) }}</span>
    </div>
  </li>

  <li v-else class="timeline-item flex gap-3 px-3 sm:px-4 py-1.5" :data-kind="item.kind">
    <div class="w-16 sm:w-20 shrink-0 text-right pt-3 leading-tight">
      <div class="text-sm font-semibold text-stone-800">{{ formatTime(activity.start_time) }}</div>
      <div v-if="minutes > 0" class="text-xs text-stone-500">{{ formatTime(activity.end_time) }}</div>
    </div>

    <div class="w-8 shrink-0 flex flex-col items-center">
      <div class="mt-2 w-8 h-8 rounded-full flex items-center justify-center ring-4 ring-white" :class="style.dot">
        <img :src="meta.icon" alt="" class="w-4 h-4 invert" />
      </div>
      <div class="flex-1 w-0.5 bg-stone-200 mt-1"></div>
    </div>

    <div class="flex-1 min-w-0 rounded-xl border mb-1" :class="style.card">
      <div class="flex items-start gap-2 p-3">
        <button class="flex-1 min-w-0 text-left" :aria-expanded="expanded" :disabled="!hasDetails" @click="expanded = !expanded">
          <p class="text-[11px] font-semibold uppercase tracking-wide" :class="style.label">
            {{ meta.label }}<span v-if="minutes > 0"> · {{ formatDuration(minutes) }}</span>
          </p>
          <h3 class="font-semibold text-stone-900 leading-snug">{{ title }}</h3>
          <p v-if="subtitle" class="text-sm text-stone-600 mt-0.5">{{ subtitle }}</p>
          <p v-if="item.kind === 'note' || item.kind === 'meal'" class="text-sm text-stone-600 mt-0.5">{{ activity.description }}</p>
        </button>

        <div v-if="canEdit" class="dropdown dropdown-end shrink-0">
          <div tabindex="0" role="button" class="btn btn-ghost btn-xs btn-square" :aria-label="'Change ' + title">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
          </div>
          <ul tabindex="0" class="dropdown-content menu menu-sm bg-base-100 rounded-box z-20 w-44 p-2 shadow text-stone-800">
            <li><a @click="startEditTime">Change time</a></li>
            <li v-if="swappable"><a @click="$emit('swap', activity)">Swap for another</a></li>
            <li><a class="text-error" @click="$emit('remove', activity)">Remove</a></li>
          </ul>
        </div>
        <span v-else-if="hasDetails" class="text-stone-400 pt-1" aria-hidden="true">{{ expanded ? '−' : '+' }}</span>
      </div>

      <form v-if="editingTime" class="flex flex-wrap items-end gap-2 px-3 pb-3" @submit.prevent="saveTime">
        <label class="text-xs text-stone-600">Start
          <input v-model="startTime" type="time" required class="input input-sm block" />
        </label>
        <label class="text-xs text-stone-600">End
          <input v-model="endTime" type="time" required class="input input-sm block" />
        </label>
        <button class="btn btn-sm btn-primary" type="submit">Save</button>
        <button class="btn btn-sm btn-ghost" type="button" @click="editingTime = false">Cancel</button>
      </form>

      <div v-if="expanded && hasDetails" class="border-t bg-white rounded-b-xl" :class="style.border">
        <TrailContent v-if="item.kind === 'trail'" :resource="activity.resource" :phrase="activity.phrase" hide-add />
        <RestaurantContent v-else-if="item.kind === 'dining'" :resource="activity.resource" :phrase="activity.phrase" hide-add />
        <PlaceContent v-else-if="item.kind === 'event'" :resource="activity.resource" resource-table="events" :phrase="activity.phrase" hide-add />
        <PlaceContent v-else-if="item.kind === 'place'" :resource="activity.resource" resource-table="locations" :phrase="activity.phrase" hide-add />
        <div v-else-if="item.kind === 'stay'" class="p-3 text-sm space-y-2">
          <p>{{ activity.resource.description }}</p>
          <p class="text-stone-500">Check-in {{ formatTime(activity.resource.check_in) }} · Check-out {{ formatTime(activity.resource.check_out) }}</p>
        </div>
      </div>
    </div>
  </li>
</template>

<script>
import PlaceContent from '../activities/content/PlaceContent.vue';
import RestaurantContent from '../activities/content/RestaurantContent.vue';
import TrailContent from '../activities/content/TrailContent.vue';
import { KINDS, activityTitle } from '../../lib/schedule';
import { durationBetween, formatDuration, formatTime } from '../../lib/time';

/** Literal class names (Tailwind only generates classes it can see in the source). */
const STYLES = {
  trail: { dot: 'bg-orange-500', card: 'bg-orange-50 border-orange-200', label: 'text-orange-700', border: 'border-orange-200' },
  dining: { dot: 'bg-emerald-500', card: 'bg-emerald-50 border-emerald-200', label: 'text-emerald-700', border: 'border-emerald-200' },
  meal: { dot: 'bg-amber-500', card: 'bg-amber-50 border-amber-200', label: 'text-amber-700', border: 'border-amber-200' },
  event: { dot: 'bg-violet-500', card: 'bg-violet-50 border-violet-200', label: 'text-violet-700', border: 'border-violet-200' },
  place: { dot: 'bg-sky-500', card: 'bg-sky-50 border-sky-200', label: 'text-sky-700', border: 'border-sky-200' },
  stay: { dot: 'bg-indigo-500', card: 'bg-indigo-50 border-indigo-200', label: 'text-indigo-700', border: 'border-indigo-200' },
  note: { dot: 'bg-slate-400', card: 'bg-slate-50 border-slate-200', label: 'text-slate-600', border: 'border-slate-200' },
};

export default {
  components: { PlaceContent, RestaurantContent, TrailContent },
  emits: ['swap', 'remove', 'update-time'],
  props: {
    item: { type: Object, required: true },
    canEdit: { type: Boolean, default: false },
  },
  data() {
    return { expanded: false, editingTime: false, startTime: '', endTime: '' };
  },
  computed: {
    activity() {
      return this.item.activity;
    },
    meta() {
      return KINDS[this.item.kind] ?? KINDS.note;
    },
    style() {
      return STYLES[this.item.kind] ?? STYLES.note;
    },
    title() {
      return activityTitle(this.activity);
    },
    minutes() {
      return durationBetween(this.activity.start_time, this.activity.end_time);
    },
    hasDetails() {
      return Boolean(this.activity.resource);
    },
    swappable() {
      return ['trail', 'dining', 'event', 'place'].includes(this.item.kind);
    },
    subtitle() {
      const r = this.activity.resource;
      if (!r) {
        return '';
      }
      if (this.item.kind === 'trail') {
        return [r.distance_miles && `${Number(r.distance_miles)} mi`, r.incline_ft && `+${r.incline_ft} ft`, r.difficulty]
          .filter(Boolean).join(' · ');
      }
      if (this.item.kind === 'dining') {
        return r.average_price ? `About $${r.average_price} per person` : '';
      }
      return '';
    },
  },
  methods: {
    formatDuration,
    formatTime,
    startEditTime() {
      this.startTime = (this.activity.start_time ?? '').slice(0, 5);
      this.endTime = (this.activity.end_time ?? '').slice(0, 5);
      this.editingTime = true;
      document.activeElement?.blur();
    },
    saveTime() {
      this.$emit('update-time', this.activity, { start_time: this.startTime, end_time: this.endTime });
      this.editingTime = false;
    },
  },
};
</script>
