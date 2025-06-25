import axios, {
    AxiosError,
    AxiosResponse,
    AxiosRequestConfig,
    AxiosInstance,
    InternalAxiosRequestConfig,
} from 'axios';

interface ApiResponse<T = any> {
    data: T;
    message?: string;
    status: number;
}

const api: AxiosInstance = axios.create({
    baseURL: '/api',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true
});

const handleResponse = <T>(response: AxiosResponse<ApiResponse<T>>): T => {
    return response.data.data;
};

const handleError = (error: AxiosError<{ message?: string }>): never => {
    if (error.response) {
        const status = error.response.status;
        const errorData = error.response.data;

        if (status === 401) {
            window.location.href = '/login';
        }

        const errorMessage = errorData?.message || error.message || 'Error en la solicitud';
        throw new Error(errorMessage);
    } else if (error.request) {
        throw new Error('No se pudo conectar con el servidor. Por favor, verifica tu conexión a internet.');
    } else {
        throw new Error('Error al realizar la solicitud');
    }
};

api.interceptors.response.use(
    (response: AxiosResponse<ApiResponse>) => response,
    (error: AxiosError<{ message?: string }>) => {
        return Promise.reject(handleError(error));
    }
);

api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers = config.headers || {};
        config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
});

interface HttpMethods {
    get: <T>(url: string, config?: AxiosRequestConfig) => Promise<T>;
    post: <T>(url: string, data?: any, config?: AxiosRequestConfig) => Promise<T>;
    put: <T>(url: string, data?: any, config?: AxiosRequestConfig) => Promise<T>;
    patch: <T>(url: string, data?: any, config?: AxiosRequestConfig) => Promise<T>;
    delete: <T>(url: string, config?: AxiosRequestConfig) => Promise<T>;
}

export const http: HttpMethods = {
    get: <T>(url: string, config?: AxiosRequestConfig) =>
        api.get<ApiResponse<T>>(url, config).then(handleResponse<T>),
    post: <T>(url: string, data?: any, config?: AxiosRequestConfig) =>
        api.post<ApiResponse<T>>(url, data, config).then(handleResponse<T>),
    put: <T>(url: string, data?: any, config?: AxiosRequestConfig) =>
        api.put<ApiResponse<T>>(url, data, config).then(handleResponse<T>),
    patch: <T>(url: string, data?: any, config?: AxiosRequestConfig) =>
        api.patch<ApiResponse<T>>(url, data, config).then(handleResponse<T>),
    delete: <T>(url: string, config?: AxiosRequestConfig) =>
        api.delete<ApiResponse<T>>(url, config).then(handleResponse<T>),
};

export default api;
