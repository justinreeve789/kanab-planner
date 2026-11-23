// User guide for Trip Planner, built with Astro Starlight.
// `npm run build` writes static files to ../public/docs, which Laravel serves at /docs (DocsController).
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

export default defineConfig({
  base: '/docs',
  outDir: '../public/docs',
  trailingSlash: 'ignore',
  integrations: [
    starlight({
      title: 'Trip Planner Guide',
      description: 'How to plan, read and edit a Kanab-area trip with Trip Planner.',
      logo: { src: './src/assets/logo.svg', alt: 'Trip Planner' },
      favicon: '/favicon.svg',
      customCss: ['./src/styles/theme.css'],
      lastUpdated: false,
      sidebar: [
        {
          label: 'Start here',
          items: [
            { label: 'Welcome', slug: 'index' },
            { label: 'Quick start', slug: 'guide/quick-start' },
            { label: 'Finding your way around', slug: 'guide/layout' },
          ],
        },
        {
          label: 'Planning a trip',
          items: [
            { label: 'The trip questionnaire', slug: 'planning/questionnaire' },
            { label: 'Your preferences explained', slug: 'planning/preferences' },
            { label: 'Destinations and areas', slug: 'planning/areas' },
            { label: 'How itineraries are built', slug: 'planning/how-it-works' },
          ],
        },
        {
          label: 'Your itinerary',
          items: [
            { label: 'Reading your itinerary', slug: 'itinerary/reading' },
            { label: 'Editing a day', slug: 'itinerary/editing' },
            { label: 'Drive times and road alerts', slug: 'itinerary/drives' },
            { label: 'Sharing and who can edit', slug: 'itinerary/sharing' },
          ],
        },
        {
          label: 'Accounts',
          items: [
            { label: 'Signing in', slug: 'accounts/sign-in' },
            { label: 'My trips', slug: 'accounts/my-trips' },
          ],
        },
        {
          label: 'Exploring the area',
          items: [
            { label: 'Trails', slug: 'explore/trails' },
            { label: 'Restaurants, events and places', slug: 'explore/places' },
            { label: 'Search', slug: 'explore/search' },
            { label: 'The virtual assistant', slug: 'explore/assistant' },
          ],
        },
        { label: 'FAQ', slug: 'faq' },
      ],
    }),
  ],
});
