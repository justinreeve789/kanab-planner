import { beforeEach, describe, expect, it } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { DEFAULT_BASE_AREA_ID, usePreferencesStore } from '../../resources/js/stores/preferences';

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

describe('home base preferences', () => {
  beforeEach(() => setActivePinia(createPinia()));

  it('sends the base and pin, and clears the pin when the area changes', () => {
    const store = usePreferencesStore();

    expect(store.baseParams()).toEqual({ base_area_id: DEFAULT_BASE_AREA_ID, lodging_name: null, lat: null, lng: null });

    store.setBaseArea(7);
    store.lodgingName = 'Zion Canyon Lodge';
    store.lodgingLat = 37.2;
    store.lodgingLng = -112.99;
    expect(store.baseParams()).toEqual({ base_area_id: 7, lodging_name: 'Zion Canyon Lodge', lat: 37.2, lng: -112.99 });

    store.setBaseArea(1);
    expect(store.baseParams()).toEqual({ base_area_id: 1, lodging_name: 'Zion Canyon Lodge', lat: null, lng: null });
  });

  it('restores the base from a saved itinerary', () => {
    const store = usePreferencesStore();

    store.setAttributesFromItineraryData({ base_area_id: 6, lodging_name: null, latitude: '37.1', longitude: '-113.5' });
    expect(store.baseParams()).toEqual({ base_area_id: 6, lodging_name: null, lat: 37.1, lng: -113.5 });
  });
});
