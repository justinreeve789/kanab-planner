<template>
  <div v-if="sources.length" class="mt-2 text-xs opacity-80">
    <span class="mr-1">Sources:</span>
    <template v-for="(source, i) in sources" :key="i">
      <a
        v-if="safeUrl(source.url)"
        :href="source.url"
        target="_blank"
        rel="noopener noreferrer"
        class="underline"
      >{{ label(source) }}</a>
      <span v-else>{{ label(source) }}</span><span v-if="i < sources.length - 1"> · </span>
    </template>
  </div>
</template>

<script>
const KINDS = { live: 'live', official: 'official', blog: 'blog', review: 'reviews', our_data: '' };

/** The sources behind an assistant answer: our records, official pages, live feeds and blog posts. */
export default {
  props: {
    sources: { type: Array, default: () => [] },
  },
  methods: {
    label(source) {
      const kind = KINDS[source.kind] ?? '';
      const extra = [kind, source.as_of].filter(Boolean).join(', ');
      return extra ? `${source.title} (${extra})` : source.title;
    },
    safeUrl(url) {
      return typeof url === 'string' && /^https?:\/\//i.test(url);
    },
  },
};
</script>
