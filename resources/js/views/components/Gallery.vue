<template>
  <div class="carousel carousel-center max-w-md p-4 space-x-4 bg-neutral rounded-box" v-if="imageUrls.length > 0">
    <div v-for="(imageUrl, index) in imageUrls" :key="index" :id="'slide-' + hash + '-' + index" class="carousel-item relative w-full">
        <img :src="'/image/' + type + '/' + imageUrl.id" class="w-full" />
        <div class="absolute flex justify-between transform -translate-y-1/2 left-5 right-5 top-1/2">
          <a :href="'#slide-' + hash + '-' + (index - 1)" class="btn btn-circle">&lt;</a>
          <a :href="'#slide-' + hash + '-' + (index + 1)" class="btn btn-circle">&gt;</a>
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

<script setup>
</script>