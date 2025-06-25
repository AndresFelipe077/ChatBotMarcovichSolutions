<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';
import ChatListItem from './chat/ChatListItem.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

// Chat related state
const chats = ref<Array<{
    id: string | number;
    title: string;
    last_message?: string;
    updated_at: string;
}>>([]);

const isLoading = ref(false);
const activeChatId = computed(() => {
    if (typeof window !== 'undefined') {
        const match = window.location.pathname.match(/\/chats\/(\d+)/);
        return match ? match[1] : null;
    }
    return null;
});

// Helper function to get auth headers
const getAuthHeaders = () => {
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };
    
    const token = localStorage.getItem('access_token');
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    return headers;
};

// Fetch chats
const fetchChats = async () => {
    try {
        isLoading.value = true;
        const response = await fetch('/api/chats', {
            headers: getAuthHeaders()
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch chats');
        }
        
        const data = await response.json();
        chats.value = data;
    } catch (error) {
        console.error('Error fetching chats:', error);
        // Optional: Handle token expiration or invalid token
        if (error instanceof Error && error.message.includes('401')) {
            localStorage.removeItem('access_token');
            window.location.href = '/login';
        }
    } finally {
        isLoading.value = false;
    }
};

// Create new chat
const createNewChat = async () => {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch('/api/chats', {
            method: 'POST',
            headers: {
                ...getAuthHeaders(),
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                title: 'Nuevo chat',
            })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to create chat');
        }

        const newChat = await response.json();
        await router.visit(`/chats/${newChat.id}`);
        await fetchChats();
    } catch (error) {
        console.error('Error creating chat:', error);
        // Optional: Handle token expiration or invalid token
        if (error instanceof Error && error.message.includes('401')) {
            localStorage.removeItem('access_token');
            window.location.href = '/login';
        }
    }
};

// Initialize
onMounted(() => {
    if (typeof window !== 'undefined') {
        fetchChats();
    }
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="flex-1 overflow-y-auto">
            <NavMain :items="mainNavItems" />

            <!-- Chats Section -->
            <div class="px-3 py-2">
                <div class="flex items-center justify-between mb-2 px-2">
                    <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                        Chats
                    </h3>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6"
                        @click="createNewChat"
                        :disabled="isLoading"
                    >
                        <Plus class="h-4 w-4" />
                        <span class="sr-only">Nuevo chat</span>
                    </Button>
                </div>

                <ul v-if="!isLoading" class="space-y-1">
                    <ChatListItem
                        v-for="chat in chats"
                        :key="chat.id"
                        :chat="chat"
                        />
                        <!-- :is-active="chat && (activeChatId === chat.id.toString())" -->
                </ul>

                <div v-else class="flex justify-center py-4">
                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
                </div>

                <div v-if="!isLoading && chats.length === 0" class="text-center py-4 text-sm text-muted-foreground">
                    <p>No hay chats recientes</p>
                    <Button
                        variant="link"
                        class="mt-2 text-sm h-auto p-0"
                        @click="createNewChat"
                    >
                        <Plus class="h-4 w-4 mr-1" />
                        Crear nuevo chat
                    </Button>
                </div>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
