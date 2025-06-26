<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MessageSquare } from 'lucide-vue-next';

defineProps<{
  chat: {
    id: string | number;
    title: string;
    last_message?: string;
    updated_at: string;
  };
  isActive?: boolean;
}>();

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<template>
  <li>
    <Link
      :href="`/dashboard/${chat.id}`"
      class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
      :class="isActive ? 'bg-accent' : 'text-muted-foreground'"
    >
      <MessageSquare class="h-4 w-4 flex-shrink-0" />
      <div class="flex-1 min-w-0">
        <p class="truncate font-medium" :class="{ 'text-foreground': isActive }">
          {{ chat.title || 'Nuevo chat' }}
        </p>
        <p v-if="chat.last_message" class="truncate text-xs text-muted-foreground">
          {{ chat.last_message }}
        </p>
      </div>
      <span class="text-xs text-muted-foreground">
        {{ formatDate(chat.updated_at) }}
      </span>
    </Link>
  </li>
</template>
