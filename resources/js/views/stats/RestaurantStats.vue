<template>
  <div v-if="restaurant" :class="'activity-stats flex align-baseline items-center ' + justify + ' ' + text_align + ' flex-wrap'">
    <span class="price flex">
     <img class="icon pr-1 svg-anim" src="/images/icons/dollar-circle.svg" v-for="n in price_range" />
    </span>
    <span class="incline flex justify-end ml-3 order-3 md:order-2">
      <img v-if="restaurant.dine_in" class="icon pr-1 svg-anim" src="/images/icons/dine-in.svg" alt="Dine-In Available">
    </span>
  </div>
</template>

<script>
export default {
    computed: {
        price_range() {
            const avg = this.restaurant.average_price;
            const dvn = this.restaurant.deviation_percent;
            // const low = Math.floor(avg - (avg * dvn));
            const high = Math.ceil(avg + (avg * dvn));
            if (high <= 10) {
                return 1;
            } else if (high <= 18) {
                return 2;
            } else if (high <= 30) {
                return 3;
            } else {
                return 4;
            }
        },
        justify() {
            return (this.align === 'right') ? 'justify-end' : '';
        },
        text_align() {
            return 'text-' + this.align;
        },
    },
    data() {
        return '';
    },
    props: {
        align: String,
        restaurant: Object,
    },
};
</script>
