<template>
    <section class="trail-details">
        <div class="trail-info">
            <h2>{{ trail.name }}</h2>
            <p>{{ trail.description }}</p>
            <p class="stats">Distance: {{ trail.distance_miles }}</p>
            <p class="stats" v-if="trail.difficulty">Difficulty: {{ trail.difficulty }}</p>
            <p class="stats" v-if="trail.incline_ft">Elevation Gain: {{ trail.incline_ft }}'</p>
            <p class="stats" v-if="trail.average_grade_pt">Average Grade: {{ trail.average_grade_pt }}%</p>
            <p v-if="trail.reservation">Reservation required</p>
        </div>
        <div class="trail-map">
            <!-- Embedded map (you can use Google Maps, Mapbox, or another mapping service) -->
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d96522.36423741286!2d-112.9271579498936!3d37.29713303136258!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zN7CsMTUwJzE3LjEiTiAxMTLCsDQyJzA2LjMiVw!5e0!3m2!1sen!2sus!4v1628604616011!5m2!1sen!2sus" width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </section>
</template>

<script>
export default {
  props: {
    trail: Object,
  },
};
</script>