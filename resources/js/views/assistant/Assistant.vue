<template>
  <div class="sticky top-0 z-10 flex justify-between items-center mb-0 bg-gradient-to-r from-blue-100 to-cyan-200 p-4 pr-2 w-full md:rounded-t-xl">
    <h2 class="text-black text-lg">Virtual Assistant</h2>
    <div class="flex align-right">
      <button class="tooltip tooltip-bottom btn btn-sm btn-ghost hover:bg-cyan-100" data-tip="Show chat history">
        <img src="images/icons/history.svg"/>
      </button>
      <button class="tooltip tooltip-bottom btn btn-sm btn-ghost hover:bg-cyan-100" @click="deleteHistory" data-tip="Clear chat history">
        <img class="h-4" src="images/icons/sweep.svg"/>
      </button>
      <button class="btn btn-sm btn-ghost hover:bg-cyan-100 hidden md:flex" @click="closeAssistantModal" v-if="modal">✕</button>
    </div>
  </div>
  <div class="assistant-panels carousel bg-white h-full w-full">
    <div class="carousel-item w-full">
      <History />
    </div>
    <div class="carousel-item bg-white w-full flex flex-col">
      <div
        class="flex justify-between items-center mb-2 bg-gradient-to-r from-blue-50 to-cyan-100 pl-4 pr-4 pt-2 pb-2 w-full"
        v-if="assistantStore.currentResource">
        <div class="text-sm text-black">
          {{ assistantStore.currentResource.name }}
        </div>
        <img src="images/icons/hiking.svg" alt="Trail" class="h-6 w-6"/>
      </div>
      <div class="w-full">
        <Messages />
      </div>
    </div>
  </div>
  <div class="sticky bottom-0 p-4 bg-white w-full md:rounded-b-xl">
    <input
      v-model="message"
      @keyup.enter="sendMessage"
      type="text"
      placeholder="Type a message…"
      class="input input-bordered input-md input-info bg-white text-black mt-2 w-full"
    />
  </div>
</template>

<script>
import axios from "axios";
import History from "../assistant/History.vue";
import Messages from "../assistant/Messages.vue";
import {useAssistantStore} from "../../stores/assistant";

export default {
  components: {
    History,
    Messages,
  },
  setup() {
    const assistantStore = useAssistantStore();
    return { assistantStore };
  },
  props: {
    modal: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      isOpen: false,
      message: '',
      messages: [],
    };
  },
  mounted() {
    this.scrollToSlide(2, false);
    this.assistantStore.addMessages(this.messages);
  },
  methods: {
    closeAssistantModal() {
      this.assistantStore.isAssistantOpen = false;
    },
    deleteHistory() {
      axios
        .delete('/api/v1/assistant/thread', {
          params: {
            resource_table: this.assistantStore.hasCurrentResource() ? this.assistantStore.currentResourceTable : null,
            resource_id: this.assistantStore.hasCurrentResource() ? this.assistantStore.currentResourceId : null,
          },
        })
        .then((response) => {
          this.assistantStore.clearMessages();
        })
        .catch((error) => {
          console.error('Error deleting assistant history:', error);
        });
    },
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
    sendMessage() {
      const messageToSend = this.message;
      this.message = '';
      if (messageToSend.trim()) {
        this.assistantStore.addMessage({
          is_assistant: false,
          content: messageToSend,
          content_html: messageToSend,
        });
        this.assistantStore.addMessage({
          is_assistant: true,
          content: '...',
          content_html: '...',
        });
        axios
          .post('/api/v1/assistant/message', {
            message: messageToSend,
            resource_type: this.assistantStore.hasCurrentResource() ? this.assistantStore.currentResource.type : null,
            resource_id: this.assistantStore.hasCurrentResource() ? this.assistantStore.currentResource.id : null,
          })
          .then((response) => {
            this.updateMessages(response.data.assistant_messages);
          })
          .catch((error) => {
            console.error('Error fetching assistant response:', error);
          });
      }
    },
    updateMessages(messages) {
      this.assistantStore.removeLastMessage();
      this.assistantStore.addMessages(messages);
    },
  }
}
</script>