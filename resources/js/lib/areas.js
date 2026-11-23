/**
 * Seasonal closures, mirroring App\Models\Area::isClosedOn(). closed_from / closed_until are "MM-DD" and may wrap
 * the new year (the North Rim closes 12-01 to 05-14).
 */
export function isAreaClosedOn(area, date) {
  if (!area?.closed_from || !area?.closed_until || !date) {
    return false;
  }
  const monthDay = String(date).slice(5, 10);
  if (area.closed_from <= area.closed_until) {
    return monthDay >= area.closed_from && monthDay <= area.closed_until;
  }
  return monthDay >= area.closed_from || monthDay <= area.closed_until;
}

/** Closed on any day of the stay (only the ends need checking for stays shorter than the closure). */
export function isAreaClosedDuring(area, start, end) {
  return isAreaClosedOn(area, start) || isAreaClosedOn(area, end || start);
}

/** "Closed Dec 1 – May 14", or null. */
export function closureLabel(area) {
  if (!area?.closed_from || !area?.closed_until) {
    return null;
  }
  const format = (monthDay) => {
    const [month, day] = monthDay.split('-').map(Number);
    return new Date(2001, month - 1, day).toLocaleString('en-US', { month: 'short', day: 'numeric' });
  };
  return `Closed ${format(area.closed_from)} – ${format(area.closed_until)}`;
}
