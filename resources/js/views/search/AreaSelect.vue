<template>
  <div class="dropdown w-full">
    <div tabindex="0" role="button" class="input input-bordered w-full flex justify-between items-center cursor-pointer">
      <span class="truncate">
        Select Areas
      </span>
      <svg class="w-4 h-4 ml-2 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </div>
    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-full max-h-92 overflow-y-auto">
      <li v-for="area in areas" :key="area.id">
        <label
          class="flex items-center gap-2 p-2 rounded cursor-pointer"
          :class="{
            'bg-primary text-primary-content': localSelectedAreaIds.includes(area.id),
            'hover:bg-base-200': true,
          }">
          <input
            type="checkbox"
            class="checkbox checkbox-sm"
            :value="area.id"
            v-model="localSelectedAreaIds"
            @change="emitSelection"
          />
          {{ area.name }}
        </label>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  props: {
    selectedAreaIds: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      areas: [
        { id: 1, name: 'Bryce Canyon' },
        { id: 2, name: 'Grand Canyon (North Rim)' },
        { id: 3, name: 'Kanab, UT' },
        { id: 4, name: 'Lake Powell' },
        { id: 5, name: 'Page, AZ' },
        { id: 6, name: 'St. George, UT' },
        { id: 7, name: 'Zion National Park' },
      ],
      localSelectedAreaIds: [],
    }
  },
  mounted() {
    this.localSelectedAreaIds = [...this.selectedAreaIds];
  },
  methods: {
    emitSelection() {
      this.$emit('update:selected', this.localSelectedAreaIds);
    }
  },
}
</script>
