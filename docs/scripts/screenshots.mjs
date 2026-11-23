// Retakes the screenshots used in the guide.
//
//   php artisan serve            # the app, with the database seeded
//   npx playwright install chromium
//   npm run screenshots          # or APP_URL=https://staging.example npm run screenshots
//
// Images are written to src/assets/screenshots/.
import { chromium } from 'playwright';
import sharp from 'sharp';
import { mkdir } from 'node:fs/promises';

const APP_URL = (process.env.APP_URL ?? 'http://127.0.0.1:8000').replace(/\/$/, '');
const OUT = new URL('../src/assets/screenshots/', import.meta.url).pathname;
const desktop = { width: 1280, height: 860 };
const tall = { width: 1280, height: 1400 };

await mkdir(OUT, { recursive: true });
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: desktop, deviceScaleFactor: 1 });
const page = await context.newPage();

// Photos come from third-party hosts. Where one can't load (e.g. offline), show a plain placeholder
// rather than a broken-image icon.
const patchBrokenImages = (target) =>
  target.evaluate(() => {
    const placeholder = 'data:image/svg+xml,' + encodeURIComponent(
      '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="400" height="300" fill="#e7d8c9"/></svg>');
    document.querySelectorAll('img').forEach((img) => {
      if (img.complete && img.naturalWidth === 0) img.src = placeholder;
    });
  });
const go = async (path) => {
  await page.goto(APP_URL + path, { waitUntil: 'networkidle' });
  await page.waitForTimeout(600);
};
// Palette PNGs keep the repository small; Astro converts them to WebP when it builds the guide.
const save = async (name, buffer) => {
  await sharp(buffer).png({ palette: true, quality: 90, effort: 10 }).toFile(OUT + name + '.png');
  console.log('saved', name);
};
const shot = async (name, target = page, options = {}) => {
  await patchBrokenImages(page);
  await page.waitForTimeout(100);
  await save(name, await target.screenshot(options));
};
// Part of the page, scrolled clear of the app's fixed header.
const HEADER_HEIGHT = 64;
const shotOf = async (name, locator, extraHeight = 0) => {
  await locator.evaluate((el, offset) => window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - offset), HEADER_HEIGHT + 16);
  await page.waitForTimeout(200);
  const box = await locator.boundingBox();
  await shot(name, page, { clip: { x: box.x, y: box.y, width: box.width, height: box.height + extraHeight } });
};
const clearItinerary = () =>
  page.evaluate(() => (document.cookie = 'active_itinerary_id=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/'));

// Questionnaire.
await go('/questionnaire');
await shot('questionnaire-start');
await go('/questionnaire-dates');
await page.getByText("I don't have specific dates").click();
await shot('questionnaire-dates');
await page.getByText("I don't have specific dates").click();
await go('/questionnaire-activities');
await shot('questionnaire-activities');
await go('/questionnaire-about');
await shot('questionnaire-about');
await go('/questionnaire-destinations');
await page.waitForTimeout(1500);
await shot('questionnaire-destinations');

// An itinerary with at least one hike on a full day.
let dayWithHike = null;
for (let attempt = 0; attempt < 5 && !dayWithHike; attempt++) {
  await clearItinerary();
  await go('/?adventurousness=0.8');
  await page.waitForSelector('.timeline-item', { timeout: 60000 });
  const days = page.locator('[id^="day-"]');
  for (let i = 1; i < (await days.count()) - 1; i++) {
    const hikes = days.nth(i).locator('.timeline-item[data-kind="trail"] h3');
    if ((await hikes.count()) && (await hikes.first().innerText()).trim().length > 6) {
      dayWithHike = days.nth(i);
      break;
    }
  }
}
if (!dayWithHike) throw new Error('No itinerary with a hike was generated.');

await page.evaluate(() => window.scrollTo(0, 0));
await shot('itinerary-overview');

// Close-ups: the sticky day bar would cover the top of a day, and a whole day needs a taller window.
await page.addStyleTag({ content: '.day-nav { position: static !important; }' });
await page.setViewportSize(tall);

await shotOf('itinerary-day', dayWithHike);

const hike = dayWithHike.locator('.timeline-item[data-kind="trail"]').first();
await hike.locator('[role="button"]').first().click();
await page.waitForTimeout(300);
await shotOf('timeline-menu', hike, 90);
await page.keyboard.press('Escape');
await page.mouse.click(5, 5);

await hike.locator('h3').click();
await page.waitForTimeout(800);
await shotOf('timeline-expanded', hike);
await hike.locator('h3').click();

await hike.locator('[role="button"]').first().click();
await hike.getByText('Change time').click();
await page.waitForTimeout(300);
await shotOf('timeline-change-time', hike);
await hike.getByRole('button', { name: 'Cancel' }).click();

await dayWithHike.getByRole('button', { name: 'Add item' }).click();
await dayWithHike.locator('input[placeholder="Ice cream in town"]').fill('Sunset at Coral Pink Sand Dunes');
await shotOf('day-add-item', dayWithHike);
await dayWithHike.getByRole('button', { name: 'Cancel' }).click();

await hike.locator('[role="button"]').first().click();
await hike.getByText('Swap for another').click();
await page.waitForSelector('[role="dialog"]');
await page.setViewportSize(desktop);
await page.waitForTimeout(2500);
await shot('swap-dialog');
await page.keyboard.press('Escape');
await page.locator('[role="dialog"] [aria-label="Close"]').click().catch(() => {});

// Someone else opening the link sees a read-only copy.
const viewer = await browser.newContext({ viewport: desktop });
const viewerPage = await viewer.newPage();
await viewerPage.goto(page.url(), { waitUntil: 'networkidle' });
await viewerPage.waitForSelector('.timeline-item');
await save('itinerary-view-only', await viewerPage.locator('.trip-summary').screenshot());
await viewer.close();

// Browsing.
await go('/trails');
await page.waitForTimeout(2500);
await shot('browse-trails');
await go('/restaurants');
await page.waitForTimeout(2500);
await shot('browse-restaurants');

// The assistant.
await go('/trails');
await page.locator('[data-tip="Talk to a virtual assistant"] button').click();
await page.waitForTimeout(800);
await page.getByPlaceholder('Type a message').fill('Any hotels near Kanab?');
await shot('assistant');

// Accounts.
await go('/login');
await shot('sign-in');
await context.clearCookies();
await page.evaluate(() => localStorage.clear());
await go('/register');
await page.fill('#register-name', 'Jordan Rivera');
await page.fill('#register-email', `jordan${Date.now()}@example.test`);
await page.fill('#register-password', 'correct-horse-9');
await page.fill('#register-password-confirmation', 'correct-horse-9');
await page.click('button[type=submit]');
await page.waitForSelector('.timeline-item', { timeout: 60000 });
await page.waitForTimeout(1500);
await go('/trips');
await page.waitForTimeout(1500);
await shot('my-trips');

// Phone layout.
const phone = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true });
const phonePage = await phone.newPage();
await phonePage.goto(APP_URL + '/', { waitUntil: 'networkidle' });
await phonePage.waitForSelector('.timeline-item', { timeout: 60000 });
await phonePage.waitForTimeout(600);
await save('phone', await phonePage.screenshot());
await save('phone-hero', await phonePage.screenshot({ clip: { x: 0, y: 0, width: 390, height: 560 } }));
await phone.close();

await browser.close();
