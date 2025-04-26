<template>
  <div class="dropdown">
    <div tabindex="0" role="button" class="btn m-1">
      Select Areas
    </div>
    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52">
      <li v-for="area in areas" :key="area.id">
        <label
          class="flex items-center gap-2 p-2 rounded cursor-pointer"
          :class="{
            'bg-primary text-primary-content': selectedAreaIds.includes(area.id),
            'hover:bg-base-200': true
          }"
        >
          <input
            type="checkbox"
            class="checkbox checkbox-sm"
            :value="area.id"
            v-model="selectedAreaIds"
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
  name: 'AreaMultiSelect',
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
      selectedAreaIds: [],
    }
  },
  methods: {
    emitSelection() {
      this.$emit('update:selected', this.selectedAreaIds);
    }
  }
}
</script>
