<script setup lang="ts">
declare global {
    interface Window {
        Toast: any;
    }
}

import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ChatInterface from '@/components/ChatInterface.vue';
import { type BreadcrumbItem } from '@/types';

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
            <ChatInterface />
        </div>
    </AppLayout>
</template>
