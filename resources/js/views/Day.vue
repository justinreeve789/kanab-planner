<template>
  <div class="day">
    <!-- Display day-specific data here -->
    <h2>Day {{ day.dayNum }} - {{ day.area.name }}</h2>
    <p>Date: {{ day.date }}</p>

    <section class="itinerary">
        <div class="timeline"></div>
        <div class="day-content morning" v-for="(activity, index) in day.periods['morning'].activities" :key="index">
            <ul>
                <li class="expandable">
                    <div v-if="activity.activityType == 'hiking'">
                        <Trail :trail="activity" />
                    </div>
                    <p><strong>{{ activity.name }}</strong></p>
                    <p>{{ activity.description }}</p>
                </li>
            </ul>
        </div>

        <div class="day-content afternoon" v-for="(activity, index) in day.periods['afternoon'].activities" :key="index">
            <ul>
                <li class="expandable">
                    <div v-if="activity.activityType == 'hiking'">
                        <Trail :trail="activity" />
                    </div>
                    <p><strong>{{ activity.name }}</strong></p>
                    <p>{{ activity.description }}</p>
                </li>
            </ul>
        </div>

        <div class="day-content evening" v-for="(activity, index) in day.periods['evening'].activities" :key="index">
            <ul>
                <li class="expandable">
                    <div v-if="activity.activityType == 'hiking'">
                        <Trail :trail="activity" />
                    </div>
                    <p><strong>{{ activity.name }}</strong></p>
                    <p>{{ activity.description }}</p>
                </li>
            </ul>
        </div>

        <div class="day-content night" v-for="(activity, index) in day.periods['night'].activities" :key="index">
            <ul>
                <li class="expandable">
                    <p><strong>{{ activity.name }}</strong></p>
                    <p>{{ activity.description }}</p>
                </li>
            </ul>
        </div>
    </section>
  </div>
</template>

<script>
export default {
  props: {
    day: Object, // The day data passed as a prop
  },
};
</script>