<script setup lang="ts">
declare global {
    interface Window {
        Toast: any;
    }
}

import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ChatInterface from '@/components/ChatInterface.vue';
import { type BreadcrumbItem } from '@/types';

const page = usePage();
const chatId = computed<string | number | null>(() => {
  const id = page.props.chatId;
  return id ? String(id) : null;
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inicio',
        href: '/dashboard',
    },
    {
        title: 'Chat',
        href: '#',
    },
];
</script>

<template>
    <Head title="Chat" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-10rem)] flex-1 overflow-hidden rounded-xl border bg-card text-card-foreground shadow">
            <ChatInterface :chat-id="chatId" />
        </div>
    </AppLayout>
</template>
