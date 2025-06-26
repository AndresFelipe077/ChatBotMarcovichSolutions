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
    if (typeof window === 'undefined') return null;

    // Obtener el ID del chat de la URL actual
    const match = window.location.pathname.match(/\/chats\/(\d+)/);
    const urlChatId = match ? match[1] : null;

    // Si hay un chat en la URL, asegurarse de que exista en la lista
    if (urlChatId && !chats.value.some(chat => chat.id.toString() === urlChatId)) {
        // Si el chat no está en la lista, recargar los chats
        fetchChats();
    }

    return urlChatId;
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

        const result = await response.json();
        // Asegurarse de que los datos tengan el formato correcto
        chats.value = result.data.map((chat: { id: string | number; title?: string; updated_at?: string }) => ({
            id: chat.id,
            title: chat.title || 'Chat sin título',
            updated_at: chat.updated_at || new Date().toISOString()
        }));

        console.log('Chats cargados:', chats.value);
    } catch (error) {
        console.error('Error fetching chats:', error);
        // Manejar expiración de token
        if (error instanceof Error && (error.message.includes('401') || error.message.includes('Unauthenticated'))) {
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
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                title: 'Nuevo chat',
            })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || 'Failed to create chat');
        }

        const result = await response.json();
        if (result.success && result.data) {
            // Agregar el nuevo chat a la lista
            const newChat = {
                id: result.data.id,
                title: result.data.title || 'Nuevo chat',
                updated_at: result.data.updated_at || new Date().toISOString()
            };
            chats.value = [newChat, ...chats.value];

            // Navegar al nuevo chat
            await router.visit(`/chats/${result.data.id}`, { preserveState: true });
        } else {
            throw new Error('No se pudo crear el chat correctamente');
        }
    } catch (error) {
        console.error('Error creating chat:', error);
        // Manejar expiración de token
        if (error instanceof Error && (error.message.includes('401') || error.message.includes('Unauthenticated'))) {
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
                        :is-active="activeChatId === chat.id.toString()"
                        />
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
