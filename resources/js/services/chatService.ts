import { Chat } from '@/models/Chat';
import { Message } from '@/models/Message';
import axios, { AxiosInstance, AxiosResponse } from 'axios';

// Tipos para las respuestas de la API
interface ApiResponse<T> {
    success: boolean;
    data: T;
    message?: string;
}

/**
 * Servicio para manejar las operaciones relacionadas con chats
 * Implementado con llamadas HTTP RESTful
 */
class ChatService {
    private axios: AxiosInstance;
    private authToken: string | null = null;
    private isRefreshing = false;
    private failedQueue: Array<{resolve: (value: any) => void, reject: (reason?: any) => void}> = [];

    constructor() {
        this.axios = axios.create({
            baseURL: '/api',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            withCredentials: true,
        });

        this.initialize();
        this.setupInterceptors();
    }

    /**
     * Inicializa el servicio con el token guardado si existe
     */
    private initialize(): void {
        const token = localStorage.getItem('auth_token');
        if (token) {
            this.setAuthToken(token);
        }
    }

    /**
     * Configura los interceptores de axios
     */
    private setupInterceptors(): void {
        // Interceptor de solicitud
        this.axios.interceptors.request.use(config => {
            config.headers = config.headers || {};

            // Agregar token CSRF si está disponible
            const csrfToken = this.getCsrfToken();
            if (csrfToken) {
                config.headers['X-CSRF-TOKEN'] = csrfToken;
            }

            // Agregar token de autenticación si existe
            if (this.authToken) {
                config.headers['Authorization'] = `Bearer ${this.authToken}`;
            }

            return config;
        });

        // Interceptor de respuesta
        this.axios.interceptors.response.use(
            (response: AxiosResponse) => response,
            async (error) => {
                const originalRequest = error.config;
                
                // Si el error es 401 y no es una solicitud de renovación de token
                if (error.response?.status === 401 && !originalRequest._retry) {
                    if (this.isRefreshing) {
                        // Si ya se está refrescando el token, encolar la petición
                        return new Promise((resolve, reject) => {
                            this.failedQueue.push({ resolve, reject });
                        }).then(() => this.axios(originalRequest))
                          .catch(err => Promise.reject(err));
                    }

                    originalRequest._retry = true;
                    this.isRefreshing = true;

                    try {
                        const newToken = await this.refreshToken();
                        if (newToken) {
                            originalRequest.headers['Authorization'] = `Bearer ${newToken}`;
                            return this.axios(originalRequest);
                        }
                    } catch (refreshError) {
                        this.setAuthToken(null);
                        window.location.href = '/login';
                        return Promise.reject(refreshError);
                    } finally {
                        this.isRefreshing = false;
                    }
                }

                return Promise.reject(error);
            }
        );
    }

    /**
     * Establece el token de autenticación
     */
    public setAuthToken(token: string | null): void {
        this.authToken = token;

        if (token) {
            this.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            localStorage.setItem('auth_token', token);
        } else {
            delete this.axios.defaults.headers.common['Authorization'];
            localStorage.removeItem('auth_token');
        }
    }

    /**
     * Obtiene el token CSRF de las cookies
     */
    private getCsrfToken(): string | null {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : null;
    }

    /**
     * Intenta renovar el token de autenticación
     */
    private async refreshToken(): Promise<string | null> {
        try {
            const response = await axios.get('/api/refresh-token', {
                withCredentials: true,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (response.data?.success && response.data.data?.access_token) {
                const newToken = response.data.data.access_token;
                this.setAuthToken(newToken);
                return newToken;
            }
            throw new Error('No se pudo renovar el token de autenticación');
        } catch (error) {
            console.error('Error al renovar el token:', error);
            throw error;
        }
    }

    // ====== Métodos de la API de Chats ======
    /**
     * Obtiene todos los chats del usuario
     */
    public async getChats(): Promise<Chat[]> {
        try {
            const response = await this.axios.get<ApiResponse<Chat[]>>('/chats');
            return response.data.data;
        } catch (error) {
            console.error('Error al obtener los chats:', error);
            throw error;
        }
    }

    /**
     * Obtiene un chat específico por su ID
     */
    public async getChat(chatId: number): Promise<Chat> {
        try {
            const response = await this.axios.get<ApiResponse<Chat>>(`/chats/${chatId}`);
            return response.data.data;
        } catch (error) {
            console.error(`Error al obtener el chat ${chatId}:`, error);
            throw error;
        }
    }

    /**
     * Crea un nuevo chat
     */
    public async createChat(title: string = 'Nueva conversación'): Promise<Chat> {
        try {
            const response = await this.axios.post<ApiResponse<Chat>>('/chats', { title });
            return response.data.data;
        } catch (error) {
            console.error('Error al crear el chat:', error);
            throw error;
        }
    }

    /**
     * Actualiza un chat existente
     */
    public async updateChat(chatId: number, data: Partial<Chat>): Promise<Chat> {
        try {
            const response = await this.axios.put<ApiResponse<Chat>>(`/chats/${chatId}`, data);
            return response.data.data;
        } catch (error) {
            console.error(`Error al actualizar el chat ${chatId}:`, error);
            throw error;
        }
    }

    /**
     * Elimina un chat
     */
    public async deleteChat(chatId: number): Promise<void> {
        try {
            await this.axios.delete(`/chats/${chatId}`);
        } catch (error) {
            console.error(`Error al eliminar el chat ${chatId}:`, error);
            throw error;
        }
    }

    /**
     * Envía un mensaje en un chat
     */
    public async sendMessage(chatId: number, content: string): Promise<Message> {
        try {
            const response = await this.axios.post<ApiResponse<Message>>(
                `/chats/${chatId}/messages`,
                { content }
            );
            return response.data.data;
        } catch (error) {
            console.error(`Error al enviar mensaje en el chat ${chatId}:`, error);
            throw error;
        }
    }
}

// Exportar una instancia única del servicio
const chatService = new ChatService();

export default chatService;
