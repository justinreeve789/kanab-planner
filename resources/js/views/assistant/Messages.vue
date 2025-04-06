<template>
    <div class="messages max-h-[350px] p-4" style="overflow-y: scroll;" ref="messagesContainer">
        <div v-for="(message, index) in store.messages" :key="index">
            <div :class="['chat', message.is_assistant ? 'chat-start' : 'chat-end']">
                <div class="chat-image avatar">
                    <div class="w-10 rounded-full">
                        <img
                            :src="message.is_assistant
                            ? '/images/icons/virtual-assistant.png'
                            : '/images/icons/blank-profile.svg'"
                        />
                    </div>
                </div>
                <div :class="['chat-bubble', message.is_assistant ? 'chat-bubble-primary' : 'chat-bubble-neutral']">
                    <div v-if="message.content == '...'">
                        <span class="loading loading-dots loading-xs"></span>
                    </div>
                    <div v-else>
                        {{ message.content }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useAssistantStore } from '../../stores/assistant.js';
import { ref, watch, nextTick } from 'vue'

export default {
    setup() {
        const store = useAssistantStore();
        const messagesContainer = ref(null)

        // Watch for changes in the store's messages, and scroll to the bottom of the messages container after a new message.
        watch(() => store.messages.length, () => {
            nextTick(() => {
                if (messagesContainer.value) {
                    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
                }
            })
        })

        return { store, messagesContainer }
    },
    mounted() {
    },
}

</script>
