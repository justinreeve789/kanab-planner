import { defineStore } from 'pinia'
import axios from 'axios';
import { useItineraryStore } from './itinerary';

const PENDING = '...';

/**
 * The trip assistant chat. Messages go to /api/v1/chat with the itinerary on screen (and its edit token, so the
 * assistant can change it when asked) and the record the chat was opened from.
 */
export const useAssistantStore = defineStore('assistant', {
    state: () => ({
        currentResource: null,
        currentResourceTable: null,
        currentResourceId: null,
        currentResourceTableId: null,
        isAssistantOpen: false,
        messages: [],
        sending: false,
        error: null,
    }),
    getters: {
        /** Choice buttons are only live on the newest message. */
        lastMessageIndex: (state) => state.messages.length - 1,
    },
    actions: {
        addMessage(message) {
            this.messages.push(message);
        },
        addMessages(messages) {
            for (let i = 0; i < messages.length; i++) {
                this.messages.push(messages[i]);
            }
        },
        clearMessages() {
            this.messages = [];
        },
        hasCurrentResource() {
            return !!this.currentResource;
        },
        removeMessage(index) {
            this.messages.splice(index, 1);
        },
        removeLastMessage() {
            this.messages.splice(this.messages.length - 1, 1);
        },
        setCurrentResource(table, id, resource) {
            this.currentResourceTable = table;
            this.currentResourceId = id;
            this.currentResourceTableId = table + '_' + id;
            this.currentResource = resource;
        },

        /** itinerary_id plus the edit token header for the itinerary on screen, if any. */
        tripContext() {
            const itineraryStore = useItineraryStore();
            const id = itineraryStore.itinerary?.id ?? null;
            return { itineraryId: id, headers: id ? itineraryStore.headers(id) : {} };
        },

        async send(text) {
            const message = (text || '').trim();
            if (!message || this.sending) {
                return;
            }
            this.error = null;
            this.sending = true;
            this.addMessage({ is_assistant: false, content: message, content_html: escapeHtml(message) });
            this.addMessage({ is_assistant: true, content: PENDING, content_html: PENDING });

            const { itineraryId, headers } = this.tripContext();
            try {
                const response = await axios.post('/api/v1/chat/message', {
                    message,
                    itinerary_id: itineraryId,
                    resource_table: this.currentResourceTable,
                    resource_id: this.currentResourceId,
                }, { headers, withCredentials: true });
                this.removeLastMessage();
                this.addMessages(response.data.assistant_messages || []);
            } catch (error) {
                this.removeLastMessage();
                const data = error?.response?.data;
                if (data?.assistant_messages?.length) {
                    this.addMessages(data.assistant_messages);
                } else {
                    const text = error?.response?.status === 429
                        ? "You're sending messages quickly. Please wait a minute and try again."
                        : "Sorry, I couldn't reach the assistant. Please try again.";
                    this.addMessage({ is_assistant: true, content: text, content_html: escapeHtml(text) });
                }
                this.error = error;
            } finally {
                this.sending = false;
            }
        },

        async loadMessages() {
            const { itineraryId } = this.tripContext();
            try {
                const response = await axios.get('/api/v1/chat/thread', { params: { itinerary_id: itineraryId }, withCredentials: true });
                this.messages = Array.isArray(response.data) ? response.data : [];
            } catch (error) {
                this.error = error;
            }
        },

        async clearThread() {
            const { itineraryId } = this.tripContext();
            await axios.delete('/api/v1/chat/thread', { params: { itinerary_id: itineraryId }, withCredentials: true });
            this.clearMessages();
        },
    }
})

function escapeHtml(text) {
    return text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
