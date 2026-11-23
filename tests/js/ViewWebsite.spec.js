import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import ViewWebsite from '../../resources/js/views/buttons/ViewWebsite.vue';

describe('ViewWebsite', () => {
  it("links to a restaurant's website from its location", () => {
    const wrapper = mount(ViewWebsite, { props: { resource: { location: { url: 'https://diner.example' } } } });
    const link = wrapper.get('a');
    expect(link.attributes('href')).toBe('https://diner.example');
    expect(link.attributes('target')).toBe('_blank');
    expect(link.attributes('rel')).toContain('noopener');
  });

  it('falls back to the resource url', () => {
    const wrapper = mount(ViewWebsite, { props: { resource: { url: 'http://park.example/visit' } } });
    expect(wrapper.get('a').attributes('href')).toBe('http://park.example/visit');
  });

  it('renders nothing without a usable web address', () => {
    expect(mount(ViewWebsite, { props: { resource: { location: {} } } }).find('a').exists()).toBe(false);
    expect(mount(ViewWebsite, { props: { resource: { url: 'javascript:alert(1)' } } }).find('a').exists()).toBe(false);
  });
});
