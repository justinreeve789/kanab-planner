<template>
    <section class="resource-details">
        <div class="resource-info">
            <h2>{{ resource.name }}</h2>
            <p v-if="resource.reservation">Reservation required</p>
            <p class="description">{{ resource.description }}</p>
        </div>
        <div class="trail-map">
            <img src="https://marketplace.canva.com/EAEfNjcKK9Q/1/0/1131w/canva-simple-food-menu-2_2u7yeZ_VM.jpg" style="max-width: 150px;" />
        </div>
    </section>
</template>

<script>
export default {
  props: {
    resource: Object,
  },
};
</script>