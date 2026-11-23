import { durationBetween, toMinutes } from './time';

/**
 * What kind of thing an activity is, for icons, colors and labels.
 */
export const KINDS = {
  trail: { label: 'Hike', icon: '/images/icons/hiking.svg', color: 'orange' },
  dining: { label: 'Eat out', icon: '/images/icons/dining.svg', color: 'emerald' },
  meal: { label: 'At home', icon: '/images/icons/dine-in.svg', color: 'amber' },
  event: { label: 'Event', icon: '/images/icons/balloons.svg', color: 'violet' },
  place: { label: 'Sightseeing', icon: '/images/icons/map.svg', color: 'sky' },
  stay: { label: 'Lodging', icon: '/images/icons/thumbs-up.svg', color: 'indigo' },
  note: { label: 'Note', icon: '/images/icons/light-bulb.svg', color: 'slate' },
  drive: { label: 'Drive', icon: '/images/icons/car.svg', color: 'gray' },
};

export function activityKind(activity) {
  const type = activity?.activity_type?.name;
  switch (activity?.resource_table) {
    case 'trails':
      return 'trail';
    case 'restaurants':
      return 'dining';
    case 'events':
      return 'event';
    case 'locations':
      return 'place';
    case 'accommodations':
      return 'stay';
    default:
      break;
  }
  if (type === 'cooking') {
    return durationBetween(activity.start_time, activity.end_time) > 0 ? 'meal' : 'note';
  }
  // Checking in or out of the traveller's own lodging has no listed accommodation behind it.
  if (type === 'checking-in' || type === 'checking-out') {
    return 'stay';
  }
  return 'note';
}

/** The activity's display title. */
export function activityTitle(activity) {
  const kind = activityKind(activity);
  if (kind === 'stay') {
    const verb = activity.activity_type?.name === 'checking-out' ? 'Check out of' : 'Check in at';
    return activity.resource ? `${verb} ${activity.resource.name}` : (activity.name ?? `${verb} your lodging`);
  }
  return activity.resource?.name ?? activity.name ?? 'Untitled';
}

/** Every activity of a day, sorted by start time. */
export function dayActivities(day) {
  const activities = [];
  for (const period of day?.periods ?? []) {
    for (const activity of period.activities ?? []) {
      activities.push(activity);
    }
  }
  return activities.sort((a, b) => (toMinutes(a.start_time) ?? 0) - (toMinutes(b.start_time) ?? 0));
}

/** Activities carry their own drives (travel_minutes) on itineraries built since street routing. */
function hasTimedDrives(activities) {
  return activities.some((activity) => activity.travel_minutes !== null && activity.travel_minutes !== undefined);
}

function driveItem(key, title, minutes, miles, notes, source) {
  return { key, kind: 'drive', title, minutes, miles: miles ?? null, notes: notes ?? [], estimated: source !== 'router' };
}

/**
 * The day as a list of timeline items: its activities in time order, with a drive before every stop that needs
 * one and the drive home at the end. Older itineraries only know the drive to the day's area, so they get a drive
 * there and back around everything that happens in the area.
 */
export function buildDaySchedule(day, homeName = 'base') {
  const activities = dayActivities(day);
  const toItem = (activity) => ({
    key: activity.id,
    kind: activityKind(activity),
    activity,
    start: activity.start_time,
    end: activity.end_time,
  });

  if (!hasTimedDrives(activities)) {
    return legacySchedule(day, homeName, activities.map(toItem));
  }

  const items = [];
  let lastStop = -1;
  for (const activity of activities) {
    const item = toItem(activity);
    if (activity.travel_minutes > 0) {
      const title = item.kind === 'stay' || item.kind === 'meal' ? `Drive back to ${homeName}` : `Drive to ${activityTitle(activity)}`;
      items.push(driveItem(`${activity.id}-drive`, title, activity.travel_minutes, activity.travel_miles, activity.travel_notes, activity.travel_source));
    }
    items.push(item);
    if (activity.travel_minutes !== null && activity.travel_minutes !== undefined) {
      lastStop = items.length - 1;
    }
  }
  if (day?.return_minutes > 0 || day?.return_notes?.length) {
    const back = driveItem(`${day.id}-drive-back`, `Drive back to ${homeName}`, day.return_minutes ?? 0, day.return_miles, day.return_notes,
      activities.some((a) => a.travel_source === 'router') ? 'router' : 'estimate');
    items.splice(lastStop + 1, 0, back);
  }
  return items;
}

function legacySchedule(day, homeName, items) {
  const drive = day?.drive_minutes ?? 0;
  if (drive > 0) {
    const inArea = items.filter((item) => item.activity.resource_table);
    if (inArea.length) {
      const first = items.indexOf(inArea[0]);
      items.splice(first, 0, driveItem(`${day.id}-drive-out`, `Drive to ${day.area?.short_name ?? day.area?.name ?? 'the area'}`, drive));
      const last = items.indexOf(inArea[inArea.length - 1]);
      items.splice(last + 1, 0, driveItem(`${day.id}-drive-back`, `Drive back to ${homeName}`, drive));
    }
  }
  return items;
}

/** Minutes of driving in a day: every leg on newer itineraries, there-and-back on older ones. */
export function dayDriveMinutes(day) {
  const activities = dayActivities(day);
  if (hasTimedDrives(activities)) {
    return day?.drive_minutes ?? 0;
  }
  return 2 * (day?.drive_minutes ?? 0);
}

/** Hours spent on trails and the number of meals out, for a day's summary line. */
export function summarizeDay(day) {
  const activities = dayActivities(day);
  const hikeMinutes = activities
    .filter((a) => activityKind(a) === 'trail')
    .reduce((sum, a) => sum + durationBetween(a.start_time, a.end_time), 0);
  return {
    hikes: activities.filter((a) => activityKind(a) === 'trail').length,
    hikeMinutes,
    mealsOut: activities.filter((a) => activityKind(a) === 'dining').length,
    events: activities.filter((a) => activityKind(a) === 'event').length,
  };
}
