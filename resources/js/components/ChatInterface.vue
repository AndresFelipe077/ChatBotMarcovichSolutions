<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';

// Message structure
const messages = ref([
  {
    id: 1,
    content: '¡Hola! Soy tu asistente de clima. ¿En qué puedo ayudarte hoy?',
    role: 'assistant',
    timestamp: new Date()
  }
]);

const newMessage = ref('');
const isSending = ref(false);
const error = ref(null);
const messagesEndRef = ref(null);

// Scroll to bottom of chat
const scrollToBottom = () => {
  nextTick(() => {
    if (messagesEndRef.value) {
      messagesEndRef.value.scrollIntoView({ behavior: 'smooth' });
    }
  });
};

// Send message function
const sendMessage = async () => {
  if (!newMessage.value.trim() || isSending.value) return;

  const content = newMessage.value.trim();
  newMessage.value = '';
  isSending.value = true;
  error.value = null;

  try {
    // Add user message to UI
    const userMessage = {
      id: Date.now(),
      content: content,
      role: 'user',
      timestamp: new Date()
    };

    messages.value.push(userMessage);
    scrollToBottom();

    // Here you would typically make an API call
    // For now, we'll simulate a response
    setTimeout(() => {
      const botResponse = {
        id: Date.now() + 1,
        content: `Recibí tu mensaje: "${content}". Esta es una respuesta simulada.`,
        role: 'assistant',
        timestamp: new Date()
      };

      messages.value.push(botResponse);
      isSending.value = false;
      scrollToBottom();
    }, 1000);

  } catch (err) {
    console.error('Error sending message:', err);
    error.value = 'Error al enviar el mensaje. Inténtalo de nuevo.';
    isSending.value = false;
  }
};

// Handle Enter key press
const handleKeyDown = (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
};

// Initialize
onMounted(() => {
  scrollToBottom();
});
</script>

<template>
  <div class="flex flex-col h-full w-full bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="border-b border-gray-200 dark:border-gray-700 p-4">
      <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Asistente de Clima</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Pregúntame sobre el clima en cualquier lugar del mundo
      </p>
    </div>

    <!-- Messages -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4">
      <div v-for="message in messages" :key="message.id" class="flex" :class="{ 'justify-end': message.role === 'user' }">
        <div v-if="message.role === 'assistant'" class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center mr-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-300" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
          </svg>
        </div>

        <div
          class="max-w-[80%] rounded-lg px-4 py-2"
          :class="{
            'bg-blue-600 text-white': message.role === 'user',
            'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700': message.role === 'assistant',
            'ml-auto': message.role === 'user',
          }"
        >
          <div class="whitespace-pre-wrap">{{ message.content }}</div>
          <div
            class="text-xs mt-1 opacity-70"
            :class="{
              'text-blue-100': message.role === 'user',
              'text-gray-500 dark:text-gray-400': message.role === 'assistant',
            }"
          >
            {{ new Date(message.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
          </div>
        </div>

        <div v-if="message.role === 'user'" class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center ml-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-300" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
          </svg>
        </div>
      </div>

      <!-- Typing indicator -->
      <div v-if="isSending" class="flex items-start">
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center mr-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-300" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg px-4 py-2 max-w-[80%] border border-gray-200 dark:border-gray-700">
          <div class="flex space-x-1">
            <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 0ms"></div>
            <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 150ms"></div>
            <div class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay: 300ms"></div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="!messages.length && !isSending" class="flex flex-col items-center justify-center h-64 text-center text-gray-500 dark:text-gray-400">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <p class="text-lg font-medium text-gray-700 dark:text-gray-300">¿En qué puedo ayudarte hoy?</p>
        <p class="text-sm">Pregúntame sobre el clima en cualquier ciudad del mundo.</p>
      </div>

      <div ref="messagesEndRef" />
    </div>

    <!-- Input -->
    <div class="border-t border-gray-200 dark:border-gray-700 p-4">
      <div class="flex gap-2">
        <input
          v-model="newMessage"
          type="text"
          placeholder="Escribe tu mensaje..."
          class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
          :disabled="isSending"
          @keydown="handleKeyDown"
        />
        <button
          type="button"
          @click="sendMessage"
          :disabled="!newMessage.trim() || isSending"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
        >
          <svg v-if="!isSending" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
          </svg>
          <svg v-else class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ isSending ? 'Enviando...' : 'Enviar' }}
        </button>
      </div>
      <p v-if="error" class="text-sm text-red-600 dark:text-red-400 mt-2 flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        {{ error }}
      </p>
    </div>
  </div>
</template>

<style scoped>
/* Animation for typing indicator */
@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

.animate-bounce {
  animation: bounce 1.5s infinite ease-in-out;
}
</style>
