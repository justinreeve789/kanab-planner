import { describe, expect, it } from 'vitest';
import { durationBetween, formatDateRange, formatDuration, formatTime, parseDate, toMinutes } from '../../resources/js/lib/time';

describe('time helpers', () => {
  it('formats times of day', () => {
    expect(formatTime('00:15:00')).toBe('12:15 AM');
    expect(formatTime('08:00:00')).toBe('8:00 AM');
    expect(formatTime('12:00')).toBe('12:00 PM');
    expect(formatTime('17:30:00')).toBe('5:30 PM');
    expect(formatTime(null)).toBe('');
  });

  it('formats durations', () => {
    expect(formatDuration(45)).toBe('45 min');
    expect(formatDuration(120)).toBe('2 h');
    expect(formatDuration(95)).toBe('1 h 35 min');
    expect(formatDuration(0)).toBe('');
  });

  it('measures the time between two times', () => {
    expect(toMinutes('07:45:00')).toBe(465);
    expect(durationBetween('08:00:00', '12:00:00')).toBe(240);
    expect(durationBetween('12:00:00', '12:00:00')).toBe(0);
  });

  it('parses dates as local days, not UTC midnight', () => {
    const date = parseDate('2027-06-01');
    expect(date.getFullYear()).toBe(2027);
    expect(date.getMonth()).toBe(5);
    expect(date.getDate()).toBe(1);
  });

  it('formats date ranges', () => {
    expect(formatDateRange('2027-06-01', '2027-06-06')).toBe('Jun 1 – Jun 6, 2027');
    expect(formatDateRange('2026-12-30', '2027-01-02')).toBe('Dec 30, 2026 – Jan 2, 2027');
    expect(formatDateRange('2027-06-01', '2027-06-01')).toBe('Jun 1, 2027');
  });
});
