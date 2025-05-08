<template>
  <div class="ass mini sliders -rotate-90">
    <input class="adventurousness cursor-ns-resize xs:cursor-ew-resize h-3 max-w-[40px] w-[40px] border-none" type="range" min="0" max="1" step="0.1" readonly style="width: 100%;" v-model="preferencesStore.adventurousness" />
    <input class="solitariness cursor-ns-resize xs:cursor-ew-resize h-3 max-w-[40px] w-[40px] border-none" type="range" min="0" max="1" step="0.1" readonly style="width: 100%;" v-model="preferencesStore.solitariness" />
    <input class="strenuousness cursor-ns-resize xs:cursor-ew-resize h-3 max-w-[40px] w-[40px] border-none" type="range" min="0" max="1" step="0.1" readonly style="width: 100%;" v-model="preferencesStore.strenuousness" />
  </div>
</template>

<script>
import {usePreferencesStore} from "../../stores/preferences";

export default {
  setup() {
    const preferencesStore = usePreferencesStore();
    return {preferencesStore};
  }
};
</script>