<template>
  <div class="carousel max-w-6xl w-full rounded-box" v-if="imageUrls.length > 0">
    <div v-for="(imageUrl, index) in imageUrls" :key="index" :id="'slide-' + hash + '-' + index" class="carousel-item relative w-full">
        <img :src="'/image/' + type + '/' + imageUrl.id" class="w-full object-cover" />
        <div class="absolute flex justify-between transform -translate-y-1/2 left-5 right-5 top-1/2" v-if="imageUrls.length > 1">
          <a :href="'#slide-' + hash + '-' + Math.max(0, (index - 1))" :class="'slide-nav hidden md:flex btn btn-circle' + (index > 0 ? '' : ' invisible')">&lt;</a>
          <a :href="'#slide-' + hash + '-' + Math.min(imageUrls.length - 1, (index + 1))" :class="'slide-nav hidden md:flex btn btn-circle' + (index < imageUrls.length - 1 ? '' : ' invisible')">&gt;</a>
          <span class="left-arrow text-white flex md:hidden" v-if="index <= 0">&lt; &lt;</span>
          <span class="right-arrow text-white flex md:hidden" v-if="index <= 0">&gt; &gt;</span>
        </div>
      </div>
    </div>
</template>

<script>
export default {
  components: {
  },
  data() {
    return {
      imageUrls: [],
    };
  },
  methods: {
    preventJump(event) {
      event.preventDefault();
      const targetSlideId = event.target.getAttribute('href').substring(1);
      const targetSlide = document.getElementById(targetSlideId);
      if (targetSlide) {
        targetSlide.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }
    },
  },
  mounted() {
    this.imageUrls = this.images;
  },
  props: {
    hash: String,
    images: Object,
    type: String,
  },
};
</script>
