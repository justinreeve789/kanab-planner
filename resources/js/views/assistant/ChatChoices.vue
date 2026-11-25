<template>
  <div class="flex flex-col gap-2" role="group" :aria-label="question || 'Choose one'">
    <button
      v-for="(option, i) in options"
      :key="i"
      type="button"
      class="btn btn-sm h-auto min-h-8 py-1 justify-start text-left normal-case bg-white text-black border-cyan-200 hover:bg-cyan-50"
      :disabled="disabled"
      @click="$emit('pick', option)"
    >
      <span class="flex flex-col items-start">
        <span class="font-medium">{{ option.label }}</span>
        <span v-if="option.detail" class="text-xs opacity-70">{{ option.detail }}</span>
      </span>
    </button>
  </div>
</template>

<script>
/** Buttons for a question the assistant asked with present_options; picking one sends its reply text. */
export default {
  props: {
    question: { type: String, default: '' },
    options: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
  },
  emits: ['pick'],
};
</script>
