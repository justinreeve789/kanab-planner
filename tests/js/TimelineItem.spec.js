import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import TimelineItem from '../../resources/js/views/components/TimelineItem.vue';

const trailItem = {
  key: 'a1',
  kind: 'trail',
  activity: {
    id: 'a1',
    start_time: '08:30:00',
    end_time: '12:00:00',
    resource_table: 'trails',
    resource: { id: 7, name: 'Angels Landing', distance_miles: '5.4', incline_ft: 1500, difficulty: 'Difficult' },
  },
};

const mountItem = (props) => mount(TimelineItem, {
  props,
  global: { plugins: [createPinia()], stubs: { TrailContent: true, RestaurantContent: true, PlaceContent: true } },
});

describe('TimelineItem', () => {
  it('shows the time, kind, length and trail stats', () => {
    const text = mountItem({ item: trailItem }).text();
    expect(text).toContain('8:30 AM');
    expect(text).toContain('12:00 PM');
    expect(text).toContain('Hike · 3 h 30 min');
    expect(text).toContain('Angels Landing');
    expect(text).toContain('5.4 mi · +1500 ft · Difficult');
  });

  it('offers edits only when the itinerary can be edited', async () => {
    expect(mountItem({ item: trailItem }).text()).not.toContain('Swap for another');

    const editable = mountItem({ item: trailItem, canEdit: true });
    expect(editable.text()).toContain('Swap for another');

    await editable.findAll('a').find((a) => a.text() === 'Swap for another').trigger('click');
    expect(editable.emitted('swap')[0][0].id).toBe('a1');
  });

  it('edits times', async () => {
    const wrapper = mountItem({ item: trailItem, canEdit: true });
    await wrapper.findAll('a').find((a) => a.text() === 'Change time').trigger('click');
    const [start, end] = wrapper.findAll('input[type="time"]');
    await start.setValue('09:00');
    await end.setValue('12:30');
    await wrapper.find('form').trigger('submit');
    expect(wrapper.emitted('update-time')[0][1]).toEqual({ start_time: '09:00', end_time: '12:30' });
  });

  it('renders drives', () => {
    const wrapper = mountItem({ item: { key: 'd', kind: 'drive', title: 'Drive to Zion', minutes: 48 } });
    expect(wrapper.text()).toContain('Drive to Zion · about 48 min');
  });
});
