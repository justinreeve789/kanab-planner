import { describe, expect, it } from 'vitest';
import { activityKind, activityTitle, buildDaySchedule, summarizeDay } from '../../resources/js/lib/schedule';

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
});
