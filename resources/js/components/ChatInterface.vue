<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import { Send, Bot, User } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

interface Message {
  id: number;
  content: string;
  isUser: boolean;
  timestamp: Date;
}

const messages = ref<Message[]>([]);
const newMessage = ref('');
const isLoading = ref(false);
const messagesEnd = ref<HTMLElement | null>(null);

// Sample responses for demo purposes
const botResponses = [
  "¡Hola! Soy tu asistente de IA. ¿En qué puedo ayudarte hoy?",
  "Entiendo tu pregunta. Déjame pensar en la mejor respuesta...",
  "Esa es una gran pregunta. Basado en mi conocimiento, puedo decirte que...",
  "No estoy seguro de entender completamente. ¿Podrías reformular tu pregunta?",
  "¡Claro! Aquí tienes la información que necesitas...",
  "Para responder a eso, necesitaría más contexto. ¿Podrías darme más detalles?",
];

const scrollToBottom = () => {
  nextTick(() => {
    messagesEnd.value?.scrollIntoView({ behavior: 'smooth' });
  });
};

const sendMessage = async () => {
  if (!newMessage.value.trim()) return;

  // Add user message
  const userMessage: Message = {
    id: Date.now(),
    content: newMessage.value,
    isUser: true,
    timestamp: new Date(),
  };

  messages.value.push(userMessage);
  const userMessageText = newMessage.value;
  newMessage.value = '';

  scrollToBottom();

  // Simulate typing indicator
  isLoading.value = true;

  // Simulate bot response after a delay
  setTimeout(() => {
    const botResponse: Message = {
      id: Date.now() + 1,
      content: getBotResponse(userMessageText),
      isUser: false,
      timestamp: new Date(),
    };

    messages.value.push(botResponse);
    isLoading.value = false;
    scrollToBottom();
  }, 1000);
};

const getBotResponse = (userMessage: string): string => {
  // Simple response logic - in a real app, this would call an API
  const randomIndex = Math.floor(Math.random() * botResponses.length);
  return botResponses[randomIndex];
};

const handleKeyDown = (e: KeyboardEvent) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
};

// Add welcome message on component mount
onMounted(() => {
  setTimeout(() => {
    const welcomeMessage: Message = {
      id: 0,
      content: '¡Hola! Soy tu asistente de IA. ¿En qué puedo ayudarte hoy?',
      isUser: false,
      timestamp: new Date(),
    };
    messages.value.push(welcomeMessage);
  }, 500);
});
</script>

<template>
  <div class="flex flex-col h-full w-full">
    <div class="border-b p-4">
      <h2 class="text-lg font-semibold">Asistente de IA</h2>
    </div>

    <ScrollArea class="flex-1 p-4 overflow-y-auto">
      <div class="space-y-4">
        <div
          v-for="message in messages"
          :key="message.id"
          class="flex"
          :class="message.isUser ? 'justify-end' : 'justify-start'"
        >
          <div
            class="flex max-w-[80%] space-x-2"
            :class="message.isUser ? 'flex-row-reverse' : ''"
          >
            <div
              class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
              :class="message.isUser ? 'bg-primary text-primary-foreground' : 'bg-muted'"
            >
              <component :is="message.isUser ? User : Bot" class="w-4 h-4" />
            </div>
            <div
              class="px-4 py-2 rounded-lg"
              :class="message.isUser
                ? 'bg-primary text-primary-foreground rounded-tr-none'
                : 'bg-muted rounded-tl-none'"
            >
              <p class="whitespace-pre-wrap">{{ message.content }}</p>
              <p class="text-xs mt-1 opacity-70" :class="message.isUser ? 'text-primary-foreground/70' : 'text-muted-foreground'">
                {{ message.timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Typing indicator -->
        <div v-if="isLoading" class="flex justify-start">
          <div class="flex space-x-2 items-center">
            <div class="w-8 h-8 rounded-full bg-muted flex items-center justify-center">
              <Bot class="w-4 h-4" />
            </div>
            <div class="px-4 py-2 bg-muted rounded-lg rounded-tl-none">
              <div class="flex space-x-1">
                <div class="w-2 h-2 rounded-full bg-muted-foreground animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-2 h-2 rounded-full bg-muted-foreground animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-2 h-2 rounded-full bg-muted-foreground animate-bounce" style="animation-delay: 300ms"></div>
              </div>
            </div>
          </div>
        </div>

        <div ref="messagesEnd" />
      </div>
    </ScrollArea>

    <!-- Input area -->
    <div class="border-t p-4">
      <div class="flex space-x-2">
        <Input
          v-model="newMessage"
          type="text"
          placeholder="Escribe tu mensaje..."
          class="flex-1"
          @keydown="handleKeyDown"
        />
        <Button
          type="button"
          :disabled="!newMessage.trim() || isLoading"
          @click="sendMessage"
        >
          <Send class="w-4 h-4 mr-2" />
          Enviar
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-bounce {
  animation: bounce 1.5s infinite ease-in-out;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}
</style>
