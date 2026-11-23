<template>
  <dl v-if="stats" class="day-stats flex items-center gap-3 text-[11px] text-stone-500" title="How this day matches the adventurousness, solitariness and strenuousness scale">
    <div v-for="meter in meters" :key="meter.key" class="flex items-center gap-1.5">
      <dt>{{ meter.label }}</dt>
      <dd class="w-12 h-1.5 rounded-full bg-stone-200 overflow-hidden" :aria-label="meter.label + ' ' + percent(meter.key) + '%'">
        <div class="h-full rounded-full" :class="meter.color" :style="{ width: percent(meter.key) + '%' }"></div>
      </dd>
    </div>
  </dl>
</template>

<script>
export default {
  props: {
    stats: { type: Object, default: null },
  },
  data() {
    return {
      meters: [
        { key: 'adventurousness', label: 'Adventure', color: 'bg-orange-500' },
        { key: 'solitariness', label: 'Solitude', color: 'bg-cyan-500' },
        { key: 'strenuousness', label: 'Effort', color: 'bg-red-500' },
      ],
    };
  },
  methods: {
    percent(key) {
      return Math.round(Math.max(0, Math.min(1, Number(this.stats?.[key] ?? 0))) * 100);
    },
  },
};
</script>
