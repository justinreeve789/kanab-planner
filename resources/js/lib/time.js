/**
 * Time helpers for the itinerary. The API sends times of day as "HH:MM:SS".
 */

export function toMinutes(time) {
  if (!time) {
    return null;
  }
  const [h, m] = String(time).split(':').map(Number);
  return h * 60 + (m || 0);
}

/** "17:30:00" → "5:30 PM" */
export function formatTime(time) {
  const minutes = toMinutes(time);
  if (minutes === null) {
    return '';
  }
  const h = Math.floor(minutes / 60) % 24;
  const m = minutes % 60;
  const suffix = h < 12 ? 'AM' : 'PM';
  const hour12 = h % 12 === 0 ? 12 : h % 12;
  return `${hour12}:${String(m).padStart(2, '0')} ${suffix}`;
}

/** 95 → "1 h 35 min", 45 → "45 min", 120 → "2 h" */
export function formatDuration(minutes) {
  if (!minutes || minutes <= 0) {
    return '';
  }
  const h = Math.floor(minutes / 60);
  const m = Math.round(minutes % 60);
  if (h === 0) {
    return `${m} min`;
  }
  return m === 0 ? `${h} h` : `${h} h ${m} min`;
}

export function durationBetween(start, end) {
  const s = toMinutes(start);
  const e = toMinutes(end);
  return s === null || e === null ? 0 : Math.max(0, e - s);
}

/** "2027-06-01" → Date at local midnight (avoids the UTC shift of new Date('2027-06-01')). */
export function parseDate(date) {
  if (!date) {
    return null;
  }
  const [y, m, d] = String(date).slice(0, 10).split('-').map(Number);
  return new Date(y, m - 1, d);
}

/** "Jun 1 – Jun 6, 2027" (or "Jun 28 – Jul 2, 2027", or across years) */
export function formatDateRange(start, end) {
  const s = parseDate(start);
  const e = parseDate(end);
  if (!s) {
    return '';
  }
  const md = (d) => d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
  if (!e || s.getTime() === e.getTime()) {
    return `${md(s)}, ${s.getFullYear()}`;
  }
  if (s.getFullYear() !== e.getFullYear()) {
    return `${md(s)}, ${s.getFullYear()} – ${md(e)}, ${e.getFullYear()}`;
  }
  return `${md(s)} – ${md(e)}, ${e.getFullYear()}`;
}
