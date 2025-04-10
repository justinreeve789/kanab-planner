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
            <div class="method-component mt-4" v-if="message.component">
              <component :is="getComponent(message.component)" v-bind="this.parseMessageParams(message.component_params)"></component>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import {defineAsyncComponent, ref, watch, nextTick} from 'vue';
import {useAssistantStore} from '../../stores/assistant.js';

export default {
  setup() {
    const store = useAssistantStore();
    const messagesContainer = ref(null)

    const getComponent = (component) => {
      return defineAsyncComponent(() => import('../' + component)) || null;
    };

    // Watch for changes in the store's messages, and scroll to the bottom of the messages container after a new message.
    watch(() => store.messages.length, () => {
      nextTick(() => {
        if (messagesContainer.value) {
          messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
      })
    })
    return {store, messagesContainer, getComponent}
  },
  mounted() {
  },
  methods: {
    getMessages() {
      let endpoint = '/api/v1/assistant';
      if (this.store.hasCurrentResource()) {
        endpoint += '/' + this.store.currentResource.id;
      }
      axios
        .get(endpoint)
        .then((response) => {
          console.log(response.data);
        })
        .catch((error) => {
          console.error('Error fetching assistant response:', error);
        });
    },
    parseMessageParams(params) {
      try {
        return typeof params === 'string'
          ? JSON.parse(params)
          : params
      } catch (e) {
        console.error('Invalid JSON in component_params', e)
        return {}
      }
    },
  },
}
</script>