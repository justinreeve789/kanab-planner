import { describe, expect, it } from 'vitest';
import { activityKind, activityTitle, buildDaySchedule, dayDriveMinutes, summarizeDay } from '../../resources/js/lib/schedule';

const activity = (id, start, end, extra = {}) => ({ id, start_time: start, end_time: end, period_id: 'p', ...extra });

const zionDay = {
  id: 'day-1',
  drive_minutes: 48,
  area: { name: 'Zion', short_name: 'Zion' },
  periods: [
    {
      id: 'p-evening',
      activities: [activity('dinner', '17:00:00', '18:30:00', { resource_table: 'restaurants', resource: { name: 'Zion Diner' } })],
    },
    {
      id: 'p-morning',
      activities: [
        activity('hike', '08:30:00', '12:00:00', { resource_table: 'trails', resource: { name: 'Angels Landing' } }),
        activity('breakfast', '07:00:00', '07:30:00', { name: 'Breakfast', activity_type: { name: 'cooking' } }),
      ],
    },
  ],
};

describe('schedule', () => {
  it('classifies activities', () => {
    expect(activityKind({ resource_table: 'trails' })).toBe('trail');
    expect(activityKind({ resource_table: 'restaurants' })).toBe('dining');
    expect(activityKind({ resource_table: 'events' })).toBe('event');
    expect(activityKind({ resource_table: 'locations' })).toBe('place');
    expect(activityKind({ resource_table: 'accommodations' })).toBe('stay');
    expect(activityKind({ activity_type: { name: 'cooking' }, start_time: '07:00:00', end_time: '07:30:00' })).toBe('meal');
    expect(activityKind({ activity_type: { name: 'cooking' }, start_time: '12:00:00', end_time: '12:00:00' })).toBe('note');
  });

  it('titles check-in and check-out', () => {
    const resource = { name: 'Tranquility Trails' };
    expect(activityTitle({ resource_table: 'accommodations', resource, activity_type: { name: 'checking-in' } })).toBe('Check in at Tranquility Trails');
    expect(activityTitle({ resource_table: 'accommodations', resource, activity_type: { name: 'checking-out' } })).toBe('Check out of Tranquility Trails');

    // Their own lodging: a task named by the API.
    const own = { name: 'Check in at Cliffrose Lodge', activity_type: { name: 'checking-in' } };
    expect(activityKind(own)).toBe('stay');
    expect(activityTitle(own)).toBe('Check in at Cliffrose Lodge');
    expect(activityTitle({ activity_type: { name: 'checking-out' } })).toBe('Check out of your lodging');
    expect(activityTitle({ name: 'Pack a lunch' })).toBe('Pack a lunch');
  });

  it('orders the day by time and wraps the area\'s activities in drives', () => {
    const items = buildDaySchedule(zionDay, 'Kanab');
    expect(items.map((i) => i.key)).toEqual(['breakfast', 'day-1-drive-out', 'hike', 'dinner', 'day-1-drive-back']);
    expect(items[1]).toMatchObject({ kind: 'drive', title: 'Drive to Zion', minutes: 48 });
    expect(items[4]).toMatchObject({ kind: 'drive', title: 'Drive back to Kanab' });
  });

  it('adds no drives on days at base', () => {
    const items = buildDaySchedule({ ...zionDay, drive_minutes: 0 }, 'Kanab');
    expect(items.some((i) => i.kind === 'drive')).toBe(false);
  });

  it('summarizes a day', () => {
    expect(summarizeDay(zionDay)).toEqual({ hikes: 1, hikeMinutes: 210, mealsOut: 1, events: 0 });
  });

  it('puts a drive before each stop and the drive home at the end', () => {
    const day = {
      id: 'day-2', drive_minutes: 130, return_minutes: 52, return_miles: 41.2, return_notes: null,
      periods: [{ activities: [
        { id: 'breakfast', name: 'Breakfast', start_time: '07:00:00', end_time: '07:30:00', activity_type: { name: 'cooking' }, travel_minutes: 0, travel_source: null },
        { id: 'hike', resource_table: 'trails', resource: { name: 'Angels Landing' }, start_time: '08:30:00', end_time: '12:30:00',
          travel_minutes: 52, travel_miles: 41.2, travel_source: 'router',
          travel_notes: [{ type: 'roadwork', closure: false, text: 'Roadwork on SR-9' }] },
        { id: 'lunch', name: 'Pack a lunch', start_time: '12:00:00', end_time: '12:00:00', activity_type: { name: 'cooking' }, travel_minutes: null },
        { id: 'dinner', resource_table: 'restaurants', resource: { name: 'Oscar\'s' }, start_time: '17:00:00', end_time: '18:30:00', travel_minutes: 26, travel_miles: 3, travel_source: 'router' },
      ] }],
    };
    const items = buildDaySchedule(day, 'Cliffrose Lodge');

    expect(items.map((i) => i.key)).toEqual(['breakfast', 'hike-drive', 'hike', 'lunch', 'dinner-drive', 'dinner', 'day-2-drive-back']);
    expect(items[1]).toMatchObject({ kind: 'drive', title: 'Drive to Angels Landing', minutes: 52, miles: 41.2, estimated: false });
    expect(items[1].notes[0].text).toBe('Roadwork on SR-9');
    expect(items[6]).toMatchObject({ kind: 'drive', title: 'Drive back to Cliffrose Lodge', minutes: 52 });
    expect(dayDriveMinutes(day)).toBe(130);
  });

  it('labels drives to home meals as the drive home, and estimates as approximate', () => {
    const day = {
      id: 'day-3', drive_minutes: 90, return_minutes: 0,
      periods: [{ activities: [
        { id: 'hike', resource_table: 'trails', resource: { name: 'Wire Pass' }, start_time: '09:00:00', end_time: '13:00:00', travel_minutes: 45, travel_source: 'estimate' },
        { id: 'dinner', name: 'Dinner', start_time: '18:00:00', end_time: '19:00:00', activity_type: { name: 'cooking' }, travel_minutes: 45, travel_source: 'estimate' },
      ] }],
    };
    const items = buildDaySchedule(day, 'Kanab');
    expect(items.map((i) => i.title ?? i.key)).toEqual(['Drive to Wire Pass', 'hike', 'Drive back to Kanab', 'dinner']);
    expect(items[0].estimated).toBe(true);
  });

  it('doubles the one-way drive on older itineraries', () => {
    expect(dayDriveMinutes(zionDay)).toBe(96);
  });
});
