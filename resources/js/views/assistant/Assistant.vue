<template>
  <div>
    <div class="tooltip tooltip-primary tooltip-left fixed bottom-4 right-4 z-50 hidden md:block"
         data-tip="Talk to a virtual assistant">
      <button class="btn btn-circle btn-primary h-16 shadow-lg w-16" @click="toggleWindow">
        <img src="images/icons/virtual-assistant.png"/>
      </button>
    </div>

    <div v-if="store.isAssistantOpen"
         class="fixed bottom-[64px] right-0 top-0 left-0 md:right-4 md:top-auto md:left-auto md:bottom-20 w-full md:w-96 md:max-w-[90vw] bg-base-100 md:border md:rounded-xl md:shadow-2xl p-0 z-50">
      <div
        class="flex justify-between items-center mb-0 bg-gradient-to-r from-blue-100 to-cyan-200 p-4 pr-2 md:rounded-t-xl w-full">
        <h2 class="text-lg">Virtual Assistant</h2>
        <button class="tooltip btn btn-sm btn-ghost hover:bg-cyan-100" @click="showHistory"
                data-tip="Show chat history">
          <img src="images/icons/history.svg"/>
        </button>
        <button class="btn btn-sm btn-ghost hover:bg-cyan-100" @click="toggleWindow">✕</button>
      </div>
      <div class="carousel bg-white w-full">
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
      <div class="p-4 bg-white">
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
import {watch} from "vue";

export default {
  components: {
    History,
    Messages,
  },
  setup() {
    const store = useAssistantStore();

    // If the current resource changes, we'll want to make sure we're on the messages panel.
    watch(() => store.currentResource, (newVal, oldVal) => {
    });

    return {store};
  },
  mounted() {
    this.store.addMessages(this.messages);
  },
  data() {
    return {
      isOpen: false,
      message: '',
      messages: [
        // {
        //   id: 4,
        //   content: 'What do I need to know about hiking the Narrows?',
        //   is_assistant: false,
        // },
        // {
        //   id: 5,
        //   content: 'Hiking the Narrows requires thorough planning for safety and requires sound judgment, as conditions can be challenging and occasionally hazardous. Always check in with the Zion park rangers at the visitor center for updates regarding water conditions, flash flood risks, and the weather forecast.',
        //   is_assistant: true,
        // },
      ],
    };
  },
  methods: {
    scrollToSlide(slideNumber, smoothScroll = true) {
      this.$nextTick(() => {
        const carouselItems = document.querySelectorAll('.carousel-item');
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
        this.store.addMessage({
          is_assistant: false,
          content: messageToSend,
        });
        this.store.addMessage({
          is_assistant: true,
          content: '...',
        });
        axios
          .post('/api/v1/assistant', {
            message: messageToSend,
          })
          .then((response) => {
            this.updateMessages(response.data);
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
      this.store.removeLastMessage();
      this.store.addMessages(messages);
    },
  }
};
</script>
