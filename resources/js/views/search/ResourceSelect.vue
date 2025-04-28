<template>
  <div class="dropdown bg-white w-full">
    <div tabindex="0" role="button" class="input input-bordered bg-white text-black w-full flex justify-between items-center cursor-pointer">
      <span class="bg-white text-black truncate">
        Select Activities
      </span>
      <svg class="w-4 h-4 ml-2 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </div>
    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-full max-h-92 overflow-y-auto">
      <li v-for="resourceTable in resourceTables" :key="resourceTable.table" class="bg-white text-black">
        <label
          class="flex items-center gap-2 p-2 rounded cursor-pointer"
          :class="{
            'bg-primary text-primary-content': localSelectedResourceTables.includes(resourceTable.table),
            'hover:bg-base-200': true
          }"
        >
          <input
            type="checkbox"
            class="checkbox checkbox-sm"
            :value="resourceTable.table"
            v-model="localSelectedResourceTables"
            @change="emitSelection"
          />
          {{ resourceTable.typeName }}
        </label>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  props: {
    selectedResourceTables: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      resourceTables: [
        { table: 'accommodations', typeName: 'Accommodations' },
        { table: 'events', typeName: 'Events' },
        { table: 'locations', typeName: 'Locations' },
        { table: 'restaurants', typeName: 'Restaurants' },
        { table: 'trails', typeName: 'Trails' },
      ],
      localSelectedResourceTables: [],
    }
  },
  mounted() {
    this.localSelectedResourceTables = [...this.selectedResourceTables];
  },
  methods: {
    emitSelection() {
      this.$emit('update:selected', this.localSelectedResourceTables);
    }
  }
}
</script>
