import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('../../resources/js/axiosHelper', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
}));

import axiosHelper from '../../resources/js/axiosHelper';
import { TOKEN_HEADER, TOKENS_STORAGE_KEY, useItineraryStore } from '../../resources/js/stores/itinerary';

const day = (id, periodId) => ({ id, area: { id: 3 }, periods: [{ id: periodId, activities: [] }] });

describe('itinerary store', () => {
  beforeEach(() => {
    window.localStorage.clear();
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('keeps the edit token from creation and sends it on changes', async () => {
    axiosHelper.post.mockResolvedValue({ data: { id: 'trip-1', edit_token: 'secret', can_edit: true, days: [day('d1', 'p1')] } });
    const store = useItineraryStore();

    await store.create({ duration_days: 3 });

    expect(store.itinerary.edit_token).toBeUndefined();
    expect(JSON.parse(window.localStorage.getItem(TOKENS_STORAGE_KEY))).toEqual({ 'trip-1': 'secret' });

    axiosHelper.put.mockResolvedValue({ data: { name: 'Zion week' } });
    await store.rename('Zion week');
    expect(axiosHelper.put).toHaveBeenCalledWith('/itinerary/trip-1', { name: 'Zion week' }, { headers: { [TOKEN_HEADER]: 'secret' } });
    expect(store.itinerary.name).toBe('Zion week');
  });

  it('sends the stored token when loading, so the API can say whether we may edit', async () => {
    window.localStorage.setItem(TOKENS_STORAGE_KEY, JSON.stringify({ 'trip-2': 'tok' }));
    setActivePinia(createPinia());
    axiosHelper.get.mockResolvedValue({ data: { id: 'trip-2', can_edit: true, days: [] } });
    const store = useItineraryStore();

    await store.load('trip-2');

    expect(axiosHelper.get).toHaveBeenCalledWith('/itinerary/trip-2', { headers: { [TOKEN_HEADER]: 'tok' } });
    expect(store.canEdit).toBe(true);
  });

  it('refreshes only the changed day after an activity edit', async () => {
    const store = useItineraryStore();
    store.itinerary = { id: 'trip-3', days: [day('d1', 'p1'), day('d2', 'p2')] };
    axiosHelper.put.mockResolvedValue({ data: {} });
    axiosHelper.get.mockResolvedValue({ data: { id: 'd2', preamble: 'fresh', periods: [] } });

    await store.updateActivity({ id: 'a1', period_id: 'p2' }, { start_time: '09:00' });

    expect(axiosHelper.get).toHaveBeenCalledWith('/day/d2', { persistent: true });
    expect(store.itinerary.days[1].preamble).toBe('fresh');
    expect(store.itinerary.days[0].preamble).toBeUndefined();
  });

  it('explains a refused change', async () => {
    const store = useItineraryStore();
    store.itinerary = { id: 'trip-4', days: [day('d1', 'p1')] };
    axiosHelper.delete.mockRejectedValue({ response: { status: 403 } });

    await expect(store.removeActivity({ id: 'a1', period_id: 'p1' })).rejects.toBeTruthy();
    expect(store.error).toMatch(/browser that created it/);
  });

  it('forgets the token when the itinerary is deleted', async () => {
    window.localStorage.setItem(TOKENS_STORAGE_KEY, JSON.stringify({ 'trip-5': 'tok' }));
    setActivePinia(createPinia());
    const store = useItineraryStore();
    store.itinerary = { id: 'trip-5', days: [] };
    axiosHelper.delete.mockResolvedValue({ status: 204 });

    await store.remove();

    expect(store.itinerary).toBeNull();
    expect(JSON.parse(window.localStorage.getItem(TOKENS_STORAGE_KEY))).toEqual({});
  });
});
