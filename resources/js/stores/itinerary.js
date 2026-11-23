import { defineStore } from 'pinia';
import axiosHelper from '../axiosHelper';

/**
 * The itinerary on screen, plus the edit tokens for itineraries made in this browser.
 *
 * Creating an itinerary returns a one-time edit token. It's kept in localStorage (keyed by itinerary id) and sent
 * as X-Itinerary-Token on every change. Signed-in owners don't need it.
 */
export const TOKEN_HEADER = 'X-Itinerary-Token';
export const TOKENS_STORAGE_KEY = 'itinerary-edit-tokens';

function readTokens() {
  try {
    return JSON.parse(window.localStorage.getItem(TOKENS_STORAGE_KEY) || '{}') || {};
  } catch (e) {
    return {};
  }
}

function writeTokens(tokens) {
  try {
    window.localStorage.setItem(TOKENS_STORAGE_KEY, JSON.stringify(tokens));
  } catch (e) {
    // Private mode or storage full: editing still works until the page is reloaded.
  }
}

export const useItineraryStore = defineStore('itinerary', {
  state: () => ({
    itinerary: null,
    loading: false,
    saving: false,
    error: null,
    tokens: readTokens(),
    savedItineraries: [],
  }),

  getters: {
    canEdit: (state) => Boolean(state.itinerary?.can_edit),
    tokenFor: (state) => (id) => state.tokens[id] ?? null,
    days: (state) => state.itinerary?.days ?? [],
    baseArea(state) {
      const first = state.itinerary?.days?.[0];
      return first?.area ?? null;
    },
  },

  actions: {
    headers(id = this.itinerary?.id) {
      const token = id ? this.tokens[id] : null;
      return token ? { [TOKEN_HEADER]: token } : {};
    },

    rememberToken(id, token) {
      if (!id || !token) {
        return;
      }
      this.tokens = { ...this.tokens, [id]: token };
      writeTokens(this.tokens);
    },

    forgetToken(id) {
      const { [id]: _removed, ...rest } = this.tokens;
      this.tokens = rest;
      writeTokens(rest);
    },

    async create(params) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await axiosHelper.post('/itinerary', params);
        this.rememberToken(data.id, data.edit_token);
        const { edit_token: _token, ...itinerary } = data;
        this.itinerary = itinerary;
        return itinerary;
      } catch (error) {
        this.error = error?.response?.status === 429
          ? 'You\'ve made a lot of itineraries in the last minute. Give it a moment and try again.'
          : 'We couldn\'t build an itinerary. Please try again.';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async load(id) {
      this.loading = true;
      this.error = null;
      try {
        const { data } = await axiosHelper.get(`/itinerary/${id}`, { headers: this.headers(id) });
        this.itinerary = data;
        return data;
      } finally {
        this.loading = false;
      }
    },

    async rename(name) {
      const { data } = await this.mutate('put', `/itinerary/${this.itinerary.id}`, { name });
      this.itinerary = { ...this.itinerary, name: data.name };
    },

    async remove() {
      const id = this.itinerary.id;
      await this.mutate('delete', `/itinerary/${id}`);
      this.forgetToken(id);
      this.itinerary = null;
    },

    async claim() {
      const { data } = await this.mutate('post', `/itinerary/${this.itinerary.id}/claim`);
      this.itinerary = data;
    },

    async fetchSaved() {
      const { data } = await axiosHelper.get('/itinerary', { persistent: true });
      this.savedItineraries = data;
      return data;
    },

    async updateDayNote(day, preamble) {
      const { data } = await this.mutate('put', `/day/${day.id}`, { preamble });
      this.replaceDay(data);
    },

    async clearDay(day) {
      const { data } = await this.mutate('delete', `/day/${day.id}`);
      this.replaceDay(data);
    },

    async regenerateDay(day) {
      const { data } = await this.mutate('post', `/day/${day.id}/regenerate`);
      this.replaceDay(data);
    },

    async addActivity(day, payload) {
      await this.mutate('post', `/day/${day.id}/activity`, payload);
      await this.refreshDay(day.id);
    },

    async updateActivity(activity, payload) {
      await this.mutate('put', `/activity/${activity.id}`, payload);
      await this.refreshDay(this.dayOf(activity)?.id);
    },

    async removeActivity(activity) {
      const day = this.dayOf(activity);
      await this.mutate('delete', `/activity/${activity.id}`);
      await this.refreshDay(day?.id);
    },

    /** The day holding this activity. */
    dayOf(activity) {
      return this.days.find((day) => (day.periods ?? []).some((period) => period.id === activity.period_id));
    },

    async refreshDay(dayId) {
      if (!dayId) {
        return;
      }
      const { data } = await axiosHelper.get(`/day/${dayId}`, { persistent: true });
      this.replaceDay(data);
    },

    /** Swap in a fresh copy of a day from the API, keeping its area/weather fields if the response lacks them. */
    replaceDay(fresh) {
      if (!this.itinerary) {
        return;
      }
      this.itinerary = {
        ...this.itinerary,
        days: this.itinerary.days.map((day) => (day.id === fresh.id ? { ...day, ...fresh } : day)),
      };
    },

    async mutate(method, url, payload = undefined) {
      this.saving = true;
      this.error = null;
      try {
        const config = { headers: this.headers() };
        const response = method === 'delete'
          ? await axiosHelper.delete(url, config)
          : await axiosHelper[method](url, payload ?? {}, config);
        return response;
      } catch (error) {
        this.error = error?.response?.status === 403
          ? 'This itinerary can only be changed from the browser that created it, or by its owner when signed in.'
          : (error?.response?.data?.message ?? 'That change didn\'t save. Please try again.');
        throw error;
      } finally {
        this.saving = false;
      }
    },
  },
});
