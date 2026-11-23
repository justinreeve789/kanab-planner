import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('../../resources/js/axiosHelper', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() },
}));

import axiosHelper from '../../resources/js/axiosHelper';
import ForgotPassword from '../../resources/js/views/login/ForgotPassword.vue';
import ResetPassword from '../../resources/js/views/login/ResetPassword.vue';

const mountWithRoute = (component, route) => {
  const router = { push: vi.fn() };
  const wrapper = mount(component, {
    global: { mocks: { $route: { query: {}, params: {}, ...route }, $router: router }, stubs: { 'router-link': { template: '<a><slot /></a>' } } },
  });
  return { wrapper, router };
};

describe('password reset pages', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('asks for a link and shows the neutral confirmation', async () => {
    axiosHelper.post.mockResolvedValue({ data: { message: 'If an account uses that email address, we\'ve sent it a link.' } });
    const { wrapper } = mountWithRoute(ForgotPassword, { query: { email: 'ada@example.test' } });

    expect(wrapper.get('#forgot-email').element.value).toBe('ada@example.test');
    await wrapper.get('form').trigger('submit');
    await flushPromises();

    expect(axiosHelper.post).toHaveBeenCalledWith('/auth/forgot-password', { email: 'ada@example.test' });
    expect(wrapper.get('[role="status"]').text()).toContain('we\'ve sent it a link');
  });

  it('shows a throttling message from the API', async () => {
    axiosHelper.post.mockRejectedValue({ response: { data: { errors: { email: ['Please wait before retrying.'] } } } });
    const { wrapper } = mountWithRoute(ForgotPassword, {});
    await wrapper.get('#forgot-email').setValue('ada@example.test');
    await wrapper.get('form').trigger('submit');
    await flushPromises();

    expect(wrapper.get('[role="alert"]').text()).toBe('Please wait before retrying.');
  });

  it('checks the passwords match before sending', async () => {
    const { wrapper } = mountWithRoute(ResetPassword, { params: { token: 'tok' }, query: { email: 'ada@example.test' } });
    await wrapper.get('#reset-password').setValue('new-password-2');
    await wrapper.get('#reset-password-confirmation').setValue('different-3');
    await wrapper.get('form').trigger('submit');

    expect(axiosHelper.post).not.toHaveBeenCalled();
    expect(wrapper.get('[role="alert"]').text()).toContain('don\'t match');
  });

  it('saves the new password and sends you to sign in', async () => {
    axiosHelper.post.mockResolvedValue({ data: {} });
    const { wrapper, router } = mountWithRoute(ResetPassword, { params: { token: 'tok' }, query: { email: 'ada@example.test' } });
    await wrapper.get('#reset-password').setValue('new-password-2');
    await wrapper.get('#reset-password-confirmation').setValue('new-password-2');
    await wrapper.get('form').trigger('submit');
    await flushPromises();

    expect(axiosHelper.post).toHaveBeenCalledWith('/auth/reset-password', {
      token: 'tok', email: 'ada@example.test', password: 'new-password-2', password_confirmation: 'new-password-2',
    });
    expect(router.push).toHaveBeenCalledWith({ name: 'login', query: { email: 'ada@example.test', reset: 1 } });
  });

  it('offers a new link when the token has expired', async () => {
    axiosHelper.post.mockRejectedValue({ response: { data: { errors: { email: ['This password reset token is invalid.'] } } } });
    const { wrapper } = mountWithRoute(ResetPassword, { params: { token: 'old' }, query: { email: 'ada@example.test' } });
    await wrapper.get('#reset-password').setValue('new-password-2');
    await wrapper.get('#reset-password-confirmation').setValue('new-password-2');
    await wrapper.get('form').trigger('submit');
    await flushPromises();

    expect(wrapper.get('[role="alert"]').text()).toContain('expired or has already been used');
    expect(wrapper.get('[role="alert"]').text()).toContain('Send a new link');
  });

  it('explains when the link is missing its email', () => {
    const { wrapper } = mountWithRoute(ResetPassword, { params: { token: 'tok' } });
    expect(wrapper.get('[role="alert"]').text()).toContain('incomplete');
    expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeDefined();
  });
});
