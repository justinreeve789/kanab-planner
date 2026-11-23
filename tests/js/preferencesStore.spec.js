import { beforeEach, describe, expect, it } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { usePreferencesStore } from '../../resources/js/stores/preferences';

describe('preferences store', () => {
  beforeEach(() => setActivePinia(createPinia()));

  it('remembers wished-for resources of every type', () => {
    const store = usePreferencesStore();
    store.addPreferredResource({ type: 'trail', id: 12 });
    store.addPreferredResource({ type: 'trail', id: 12 });
    store.addPreferredResource({ type: 'restaurant', id: 4 });

    expect(store.trailIds).toEqual([12]);
    expect(store.hasResource('trail', 12)).toBe(true);
    expect(store.hasResource('restaurant', 4)).toBe(true);
    expect(store.hasResource('location', 12)).toBe(false);
  });

  it('picks a month from the preferred ones', () => {
    const store = usePreferencesStore();
    store.preferredMonths = [9, 10];
    for (let i = 0; i < 20; i++) {
      expect([9, 10]).toContain(store.getRandomPreferredMonth());
    }
  });
});
