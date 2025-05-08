<template>
  <div>
    <div class="tooltip tooltip-primary tooltip-left hidden md:block" data-tip="Talk to a virtual assistant">
      <button class="btn btn-circle btn-primary h-16 shadow-lg w-16" @click="toggleWindow">
        <img src="images/icons/virtual-assistant.png"/>
      </button>
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
