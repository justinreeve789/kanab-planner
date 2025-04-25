<template>
  <div>
    <div class="tooltip tooltip-primary tooltip-left fixed bottom-4 right-4 z-50 hidden md:block" data-tip="Talk to a virtual assistant">
      <button class="btn btn-circle btn-primary h-16 shadow-lg w-16" @click="toggleWindow">
        <img src="images/icons/virtual-assistant.png"/>
      </button>
    </div>

    <div v-if="store.isAssistantOpen"
         class="fixed bottom-[64px] right-0 top-0 left-0 md:right-4 md:top-auto md:left-auto md:bottom-20 w-full md:w-96 md:max-w-[90vw] bg-base-100 md:border md:rounded-xl md:shadow-2xl p-0 z-50">
      <div class="flex justify-between items-center mb-0 bg-gradient-to-r from-blue-100 to-cyan-200 p-4 pr-2 md:rounded-t-xl w-full">
        <h2 class="text-lg">Virtual Assistant</h2>
        <div class="flex align-right">
          <button class="tooltip tooltip-bottom btn btn-sm btn-ghost hover:bg-cyan-100" @click="showHistory" data-tip="Show chat history">
            <img src="images/icons/history.svg"/>
          </button>
          <button class="tooltip tooltip-bottom btn btn-sm btn-ghost hover:bg-cyan-100" @click="deleteHistory" data-tip="Clear chat history">
            <img class="h-4" src="images/icons/sweep.svg"/>
          </button>
          <button class="btn btn-sm btn-ghost hover:bg-cyan-100" @click="toggleWindow">✕</button>
        </div>
      </div>
      <div class="assistant-panels carousel bg-white w-full">
        <div class="carousel-item w-full">
          <History/>
        </div>
        <div class="carousel-item bg-white w-full flex flex-col">
          <div
            class="flex justify-between items-center mb-2 bg-gradient-to-r from-blue-50 to-cyan-100 pl-4 pr-4 pt-2 pb-2 w-full"
            v-if="store.currentResource">
            <div class="text-sm">
              {{ store.currentResource.name }}
            </div>
            <img src="images/icons/hiking.svg" alt="Trail" class="h-6 w-6"/>
          </div>
          <div class="w-full">
            <Messages/>
          </div>
        </div>
      </div>
      <div class="fixed bottom-[64px] p-4 bg-white w-full sm:relative sm:bottom-0">
        <input
          v-model="message"
          @keyup.enter="sendMessage"
          type="text"
          placeholder="Type a message…"
          class="input input-bordered input-md input-info bg-white mt-2 w-full"
        />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import History from './History.vue';
import Messages from './Messages.vue';
import {useAssistantStore} from '../../stores/assistant.js';
import {ref, watch} from "vue";

export default {
  components: {
    History,
    Messages,
  },
  setup() {
    const store = useAssistantStore();
    return {store};
  },
  watch: {
    // If the assistant opens, we'll want to make sure we're on the messages panel.
    'store.isAssistantOpen'(newVal, oldVal) {
      if (oldVal === false && newVal === true) {
        this.scrollToSlide(2, false);
      }
    },
  },
  mounted() {
    this.store.addMessages(this.messages);
  },
  data() {
    return {
      isOpen: false,
      message: '',
      messages: [],
    };
  },
  methods: {
    scrollToSlide(slideNumber, smoothScroll = true) {
      this.$nextTick(() => {
        const carouselItems = document.querySelectorAll('.assistant-panels.carousel .carousel-item');
        if (carouselItems.length >= slideNumber) {
          carouselItems[slideNumber - 1].scrollIntoView({
            behavior: smoothScroll ? 'smooth' : 'instant',
            block: 'nearest',
          });
        }
      });
    },
    deleteHistory() {
      axios
        .delete('/api/v1/assistant/thread', {
          params: {
            resource_table: this.store.hasCurrentResource() ? this.store.currentResourceTable : null,
            resource_id: this.store.hasCurrentResource() ? this.store.currentResourceId : null,
          },
        })
        .then((response) => {
          this.store.clearMessages();
        })
        .catch((error) => {
          console.error('Error deleting assistant history:', error);
        });
    },
    sendMessage() {
      const messageToSend = this.message;
      this.message = '';
      if (messageToSend.trim()) {
        this.store.addMessage({
          is_assistant: false,
          content: messageToSend,
          content_html: messageToSend,
        });
        this.store.addMessage({
          is_assistant: true,
          content: '...',
          content_html: '...',
        });
        axios
          .post('/api/v1/assistant/message', {
            message: messageToSend,
            resource_type: this.store.hasCurrentResource() ? this.store.currentResource.type : null,
            resource_id: this.store.hasCurrentResource() ? this.store.currentResource.id : null,
          })
          .then((response) => {
            this.updateMessages(response.data.assistant_messages);
          })
          .catch((error) => {
            console.error('Error fetching assistant response:', error);
          });
      }
    },
    toggleWindow() {
      this.store.isAssistantOpen = !this.store.isAssistantOpen;
      if (this.store.isAssistantOpen) {
        this.scrollToSlide(2, false);
      }
    },
    updateMessages(messages) {
      this.store.removeLastMessage();
      this.store.addMessages(messages);
    },
  }
};
</script>
