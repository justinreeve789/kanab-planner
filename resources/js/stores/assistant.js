import { defineStore } from 'pinia'

export const useAssistantStore = defineStore('assistant', {
    state: () => ({
        messages: [],
    }),
    actions: {
        addMessage(message) {
            this.messages.push(message);
        },
        addMessages(messages) {
            for (let i = 0; i < messages.length; i++) {
                this.messages.push(messages[i]);
            }
        },
        removeMessage(index) {
            this.messages.splice(index, 1);
        },
        removeLastMessage() {
            this.messages.splice(this.messages.length - 1, 1);
        },
        clearMessages() {
            this.messages = [];
        }
    }
})
