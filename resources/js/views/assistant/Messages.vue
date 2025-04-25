<template>
  <div class="messages max-h-[540px] lg:max-h-[350px] p-4" style="overflow-y: scroll;" ref="messagesContainer">
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
            <span v-html="message.content_html"></span>
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
    });

    return {store, messagesContainer, getComponent}
  },
  watch: {
    // If the current resource changes, retrieve the new resource's messages.
    'store.messages.length'() {
      this.$nextTick(() => {
        if (this.messagesContainer) {
          this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }
      });
    },
    // 'store.currentResource'(newVal, oldVal) {
    //   if (oldVal !== null) {
    //     this.store.clearMessages();
      // }
      // this.getMessages();
    // },
    // 'store.currentResourceTableId'(newVal, oldVal) {
      // this.store.clearMessages();
      // this.getMessages();
    // },
  },
  mounted() {
    // TODO: Make the watcher work and remove this, so we're not reloading messages each time the chat opens.
    this.store.clearMessages();
    this.getMessages();
  },
  methods: {
    getMessages() {
      const endpoint = '/api/v1/assistant/thread';
      axios
        .get(endpoint, {
          params: {
            resource_table: this.store.hasCurrentResource() ? this.store.currentResourceTable : null,
            resource_id: this.store.hasCurrentResource() ? this.store.currentResourceId : null,
          },
        })
        .then((response) => {
          this.store.messages = response.data;
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