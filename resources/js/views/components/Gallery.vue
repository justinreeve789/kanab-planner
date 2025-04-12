<template>
  <div class="carousel max-w-6xl flex h-full w-full items-stretch" v-if="imageUrls.length > 0">
    <div
      v-for="(imageUrl, index) in imageUrls"
      :key="index"
      :id="'slide-' + unique + '-' + index"
      class="carousel-item relative h-full w-full"
    >
      <img
        :src="'/image/' + type + '/' + imageUrl.id + '?size=medium'"
        class="w-full object-cover cursor-pointer"
        @click="toggleFullImage(imageUrl)"
      />

      <div
        class="absolute flex justify-between transform -translate-y-1/2 left-5 right-5 top-1/2 opacity-90 md:opacity-25 xl:opacity-75"
        v-if="imageUrls.length > 1"
      >
        <a
          :href="'#slide-' + unique + '-' + Math.max(0, index - 1)"
          :class="'slide-nav hidden md:flex btn btn-circle' + (index > 0 ? '' : ' invisible')"
          @click.prevent="preventJump"
        >
          &lt;
        </a>
        <a
          :href="'#slide-' + unique + '-' + Math.min(imageUrls.length - 1, index + 1)"
          :class="'slide-nav hidden md:flex btn btn-circle' + (index < imageUrls.length - 1 ? '' : ' invisible')"
          @click.prevent="preventJump"
        >
          &gt;
        </a>
        <span class="left-arrow text-white flex md:hidden" v-if="index <= 0">&lt; &lt;</span>
        <span class="right-arrow text-white flex md:hidden" v-if="index <= 0">&gt; &gt;</span>
      </div>
    </div>
  </div>

  <!-- Fullscreen Image Overlay -->
  <div
    v-if="fullImage"
    class="fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center cursor-pointer"
    @click="toggleFullImage(null)"
  >
    <img
      :src="'/image/' + type + '/' + fullImage.id + '?size=full'"
      class="max-w-full max-h-full"
      alt="Expanded"
    />
  </div>
</template>

<script>
export default {
  data() {
    return {
      imageUrls: [],
      unique: this.$generateUnique(),
      fullImage: null,
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
    toggleFullImage(image) {
      this.fullImage = this.fullImage ? null : image;
    },
  },
  mounted() {
    this.imageUrls = this.images;
  },
  props: {
    images: Object,
    type: String,
  },
};
</script>