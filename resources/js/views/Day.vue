<template>
  <div class="day">
    <p class="date">{{ day.formattedDate }}</p>
    <div class="day-header">
        <h2>Day {{ day.dayNum + 1 }} - {{ day.area.name }}</h2>
        <div class="graph">
            <div class="gradient-line adventurousness" style="height: 50%;"></div>
            <div class="gradient-line solitariness" style="height: 30%;"></div>
            <div class="gradient-line strenuousness" style="height: 70%;"></div>
        </div>
    </div>

    <section class="itinerary">
        <div class="timeline"></div>
        <div class="day-content morning" v-for="(activity, index) in day.periods['morning'].activities" :key="index">
            <ul>
                <li class="day-activity expandable">
                    <div v-if="activity.activityType == 'dining'">
                        <Restaurant :resource="activity.data" />
                    </div>
                    <div v-else-if="activity.activityType == 'hiking'">
                        <Trail :trail="activity.data" />
                    </div>
                    <div v-else>
                        <p><strong>{{ activity.name }}</strong></p>
                        <p>{{ activity.phrase }}</p>
                        <p>{{ activity.description }}</p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="day-content afternoon" v-for="(activity, index) in day.periods['afternoon'].activities" :key="index">
            <ul>
                <li class="day-activity expandable">
                    <div v-if="activity.activityType == 'dining'">
                        <Restaurant :resource="activity.data" />
                    </div>
                    <div v-else-if="activity.activityType == 'hiking'">
                        <Trail :trail="activity.data" />
                    </div>
                    <div v-else>
                        <p><strong>{{ activity.name }}</strong></p>
                        <p>{{ activity.phrase }}</p>
                        <p>{{ activity.description }}</p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="day-content evening" v-for="(activity, index) in day.periods['evening'].activities" :key="index">
            <ul>
                <li class="day-activity expandable">
                    <div v-if="activity.activityType == 'dining'">
                        <Restaurant :resource="activity.data" />
                    </div>
                    <div v-else-if="activity.activityType == 'hiking'">
                        <Trail :trail="activity.data" />
                    </div>
                    <div v-else>
                        <p><strong>{{ activity.name }}</strong></p>
                        <p>{{ activity.phrase }}</p>
                        <p>{{ activity.description }}</p>
                    </div>
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
import Event from './activities/Event.vue';
import Location from './activities/Location.vue';
import Restaurant from './activities/Restaurant.vue';
import Trail from './activities/Trail.vue';

export default {
  components: {
    Event,
    Location,
    Restaurant,
    Trail,
  },
  props: {
    day: Object, // The day data passed as a prop
  },
};
</script>