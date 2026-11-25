import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import axios from 'axios';
import { useAssistantStore } from '../../resources/js/stores/assistant';
import { useItineraryStore } from '../../resources/js/stores/itinerary';
import ChatChoices from '../../resources/js/views/assistant/ChatChoices.vue';
import ChatSources from '../../resources/js/views/assistant/ChatSources.vue';

vi.mock('axios');

describe('assistant store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.resetAllMocks();
    window.localStorage.clear();
  });

  it('sends the message with the itinerary, its edit token and the open record', async () => {
    const itinerary = useItineraryStore();
    itinerary.itinerary = { id: 'trip-1' };
    itinerary.rememberToken('trip-1', 'secret');
    const store = useAssistantStore();
    store.setCurrentResource('trails', 12, { name: 'Navajo Loop' });
    axios.post.mockResolvedValue({ data: { assistant_messages: [{ is_assistant: true, content: 'Yes, with a catch.', content_html: 'Yes, with a catch.' }] } });

    await store.send('  Can a 5-year-old do it?  ');

    expect(axios.post).toHaveBeenCalledWith('/api/v1/chat/message', {
      message: 'Can a 5-year-old do it?', itinerary_id: 'trip-1', resource_table: 'trails', resource_id: 12,
    }, { headers: { 'X-Itinerary-Token': 'secret' }, withCredentials: true });
    expect(store.messages.map((m) => m.content)).toEqual(['Can a 5-year-old do it?', 'Yes, with a catch.']);
    expect(store.sending).toBe(false);
  });

  it('escapes what the traveler typed before showing it', async () => {
    const store = useAssistantStore();
    axios.post.mockResolvedValue({ data: { assistant_messages: [] } });
    await store.send('<img src=x onerror=alert(1)>');
    expect(store.messages[0].content_html).toBe('&lt;img src=x onerror=alert(1)&gt;');
  });

  it('shows a friendly message when rate limited and keeps the failed reply when there is one', async () => {
    const store = useAssistantStore();
    axios.post.mockRejectedValueOnce({ response: { status: 429, data: {} } });
    await store.send('hello');
    expect(store.messages.at(-1).content).toMatch(/wait a minute/);

    axios.post.mockRejectedValueOnce({ response: { status: 503, data: { assistant_messages: [{ is_assistant: true, content: 'Sorry, try again.' }] } } });
    await store.send('again');
    expect(store.messages.at(-1).content).toBe('Sorry, try again.');
  });

  it('ignores empty messages and double sends', async () => {
    const store = useAssistantStore();
    await store.send('   ');
    store.sending = true;
    await store.send('hi');
    expect(axios.post).not.toHaveBeenCalled();
  });
});

describe('chat components', () => {
  it('choice buttons emit the picked option', async () => {
    const wrapper = mount(ChatChoices, { props: { question: 'Which one?', options: [{ label: 'Canyon Overlook (Zion)', detail: '1 mi', reply: 'Canyon Overlook, trail 499' }, { label: 'Timber Creek Overlook' }] } });
    expect(wrapper.text()).toContain('Canyon Overlook (Zion)');
    await wrapper.findAll('button')[0].trigger('click');
    expect(wrapper.emitted('pick')[0][0].reply).toBe('Canyon Overlook, trail 499');
  });

  it('sources link only http(s) urls and label live data with its time', () => {
    const wrapper = mount(ChatSources, { props: { sources: [
      { title: 'National Weather Service forecast', url: 'https://forecast.weather.gov/x', kind: 'live', as_of: 'Nov 25, 10:05 AM' },
      { title: 'Bad', url: 'javascript:alert(1)', kind: 'blog' },
      { title: 'Trail records', kind: 'our_data' },
    ] } });
    const links = wrapper.findAll('a');
    expect(links).toHaveLength(1);
    expect(links[0].text()).toBe('National Weather Service forecast (live, Nov 25, 10:05 AM)');
    expect(wrapper.text()).toContain('Bad (blog)');
    expect(wrapper.text()).toContain('Trail records');
  });
});
