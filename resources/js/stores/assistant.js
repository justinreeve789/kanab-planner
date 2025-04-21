import { defineStore } from 'pinia'

export const useAssistantStore = defineStore('assistant', {
    state: () => ({
        currentResource: null,
        currentResourceTable: null,
        currentResourceId: null,
        currentResourceTableId: null,
        isAssistantOpen: false,
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
    }
})
