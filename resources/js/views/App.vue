<template>
    <div class="day">
        <div class="timeline"></div>
        <div class="day-content">
            <h2>Day 1</h2>
            <p class="date">September 18, 2023</p>
            <ul>
                <li class="expandable">
                    <input type="checkbox" id="item1">
                    <label for="item1">
                        <span class="time">8:00 AM</span> Breakfast at Cafe Sunrise
                        <div class="expand-content">
                            <p>Start your day with a delicious breakfast at Cafe Sunrise, known for its amazing coffee and pastries.</p>
                            <p>Address: 123 Main St, Zion</p>
                        </div>
                    </label>
                </li>
                <li class="expandable">
                    <input type="checkbox" id="item2">
                    <label for="item2">
                        <span class="time">10:00 AM</span> Explore Zion National Park
                        <div class="expand-content">
                            <p>Explore the stunning Zion National Park with its red rock formations and beautiful trails.</p>
                            <p>Don't forget to bring your camera!</p>
                        </div>
                    </label>
                </li>
                <li class="expandable">
                    <input type="checkbox" id="item3">
                    <label for="item3">
                        <span class="time">1:00 PM</span> Lunch at Canyon View Restaurant
                        <div class="expand-content">
                            <p>Enjoy a hearty lunch at Canyon View Restaurant, offering a variety of dishes with a view of the canyon.</p>
                            <p>Address: 456 Canyon Rd, Zion</p>
                        </div>
                    </label>
                </li>
                <!-- Add more expandable items as needed -->
            </ul>
        </div>
    </div>
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
import Day from './Day.vue';
import Itinerary from './Itinerary.vue';

export default {
  components: {
    DatePicker,
    Itinerary,
  },
}
const startDate = ref();
</script>