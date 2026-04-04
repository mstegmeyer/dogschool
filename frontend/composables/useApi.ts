interface ApiFetchOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE',
    body?: unknown,
    headers?: Record<string, string>,
}

interface FetchError {
    statusCode?: number,
    status?: number,
}

export const useApi = () => {
    const { token, logout } = useAuth();

    async function apiFetch<T = unknown>(url: string, options: ApiFetchOptions = {}): Promise<T> {
        const headers: Record<string, string> = {
            Accept: 'application/json',
            ...(options.headers ?? {}),
        };
        if (token.value) {
            headers.Authorization = `Bearer ${token.value}`;
        }
        try {
            return await $fetch<T>(url, { ...options, headers });
        } catch (error: unknown) {
            const fe = error as FetchError;
            if (fe.statusCode === 401 || fe.status === 401) {
                logout();
                navigateTo('/login');
            }
            throw error;
        }
    }

    const get = <T = unknown>(url: string): Promise<T> => apiFetch<T>(url);
    const post = <T = unknown>(url: string, body?: unknown): Promise<T> => apiFetch<T>(url, { method: 'POST', body });
    const put = <T = unknown>(url: string, body?: unknown): Promise<T> => apiFetch<T>(url, { method: 'PUT', body });
    const del = <T = unknown>(url: string): Promise<T> => apiFetch<T>(url, { method: 'DELETE' });

    return { get, post, put, del };
};
