<template>
  <div class="dropdown">
    <div tabindex="0" role="button" class="btn m-1">
      Select...
    </div>
    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
      <li v-for="resourceTable in resourceTables" :key="resourceTable.table">
        <label
          class="flex items-center gap-2 p-2 rounded cursor-pointer"
          :class="{
            'bg-primary text-primary-content': resourceTables.includes(resourceTable.table),
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
