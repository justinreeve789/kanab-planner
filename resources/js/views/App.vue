<template>
    <h1>
        Plan Your Experience
    </h1>
    <section id="dates">
        <div class="date-select">
            <DatePicker v-model="startDate" 		:enable-time-picker     = "false"
                                            		:clearable              = "false"
                                            		:month-change-on-scroll = "false"
                                            		:format                 = "dateFormat"
                                            		auto-apply />
        </div>
        <div class="date-select">
            <DatePicker v-model="endDate" 		:enable-time-picker     = "false"
                                          		:clearable              = "false"
                                          		:month-change-on-scroll = "false"
                                          		:format                 = "dateFormat"
                                          		auto-apply />
        </div>
    </section>
</template>

<script>
import { ref } from 'vue';
import DatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import Itinerary from './Itinerary.vue';

export default {
  components: {
    DatePicker,
    Itinerary,
  },
}
const startDate = ref();
</script>