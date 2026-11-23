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
  return 'note';
}

/** The activity's display title. */
export function activityTitle(activity) {
  const kind = activityKind(activity);
  if (kind === 'stay') {
    const verb = activity.activity_type?.name === 'checking-out' ? 'Check out of' : 'Check in at';
    return `${verb} ${activity.resource?.name ?? 'your lodging'}`;
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

/**
 * The day as a list of timeline items: its activities in time order, with "drive to" and "drive back" legs
 * around everything that happens in the day's area when the day is away from base.
 */
export function buildDaySchedule(day, baseAreaName = 'base') {
  const activities = dayActivities(day);
  const items = activities.map((activity) => ({
    key: activity.id,
    kind: activityKind(activity),
    activity,
    start: activity.start_time,
    end: activity.end_time,
  }));

  const drive = day?.drive_minutes ?? 0;
  if (drive > 0) {
    const inArea = items.filter((item) => item.activity.resource_table);
    if (inArea.length) {
      const first = items.indexOf(inArea[0]);
      items.splice(first, 0, {
        key: `${day.id}-drive-out`,
        kind: 'drive',
        title: `Drive to ${day.area?.short_name ?? day.area?.name ?? 'the area'}`,
        minutes: drive,
      });
      const last = items.indexOf(inArea[inArea.length - 1]);
      items.splice(last + 1, 0, {
        key: `${day.id}-drive-back`,
        kind: 'drive',
        title: `Drive back to ${baseAreaName}`,
        minutes: drive,
      });
    }
  }
  return items;
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
