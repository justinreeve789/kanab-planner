<template>
  <div class="dropdown w-full">
    <div tabindex="0" role="button" class="input input-bordered w-full flex justify-between items-center cursor-pointer">
      <span class="truncate">
        Select Activities
      </span>
      <svg class="w-4 h-4 ml-2 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </div>
    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-full max-h-92 overflow-y-auto">
      <li v-for="resourceTable in resourceTables" :key="resourceTable.table">
        <label
          class="flex items-center gap-2 p-2 rounded cursor-pointer"
          :class="{
            'bg-primary text-primary-content': selectedResourceTables.includes(resourceTable.table),
            'hover:bg-base-200': true
          }"
        >
          <input
            type="checkbox"
            class="checkbox checkbox-sm"
            :value="resourceTable.table"
            v-model="selectedResourceTables"
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
  data() {
    return {
      resourceTables: [
        { table: 'accommodations', typeName: 'Accommodations' },
        { table: 'events', typeName: 'Events' },
        { table: 'locations', typeName: 'Locations' },
        { table: 'restaurants', typeName: 'Restaurants' },
        { table: 'trails', typeName: 'Trails' },
      ],
      selectedResourceTables: [],
    }
  },
  methods: {
    emitSelection() {
      this.$emit('update:selected', this.selectedResourceTables);
    }
  }
}
</script>
