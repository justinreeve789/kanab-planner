import { describe, expect, it } from 'vitest';
import { closureLabel, isAreaClosedDuring, isAreaClosedOn } from '../../resources/js/lib/areas';

const northRim = { closed_from: '12-01', closed_until: '05-14' };
const summerOnly = { closed_from: '06-01', closed_until: '08-31' };

describe('area closures', () => {
  it('handles closures that wrap the new year', () => {
    expect(isAreaClosedOn(northRim, '2027-12-01')).toBe(true);
    expect(isAreaClosedOn(northRim, '2027-01-15')).toBe(true);
    expect(isAreaClosedOn(northRim, '2027-05-14')).toBe(true);
    expect(isAreaClosedOn(northRim, '2027-05-15')).toBe(false);
    expect(isAreaClosedOn(northRim, '2027-11-30')).toBe(false);
  });

  it('handles closures within one year', () => {
    expect(isAreaClosedOn(summerOnly, '2027-07-04')).toBe(true);
    expect(isAreaClosedOn(summerOnly, '2027-09-01')).toBe(false);
  });

  it('treats areas without a closure as always open', () => {
    expect(isAreaClosedOn({}, '2027-01-01')).toBe(false);
    expect(isAreaClosedOn(northRim, null)).toBe(false);
  });

  it('checks both ends of a stay', () => {
    expect(isAreaClosedDuring(northRim, '2027-11-27', '2027-12-02')).toBe(true);
    expect(isAreaClosedDuring(northRim, '2027-06-01', '2027-06-07')).toBe(false);
    expect(isAreaClosedDuring(northRim, '2027-05-10', null)).toBe(true);
  });

  it('labels the closure', () => {
    expect(closureLabel(northRim)).toBe('Closed Dec 1 – May 14');
    expect(closureLabel({})).toBeNull();
  });
});
