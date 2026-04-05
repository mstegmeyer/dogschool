import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type { AuthRole, Customer } from '~/types';

interface RegisterPayload {
    email: string,
    password: string,
    name?: string,
}

export const useAuthStore = defineStore('auth', () => {
    const token = ref<string | null>(null);
    const role = ref<AuthRole | null>(null);
    const user = ref<Customer | null>(null);

    const isAuthenticated = computed(() => token.value !== null);
    const isAdmin = computed(() => role.value === 'admin');
    const isCustomer = computed(() => role.value === 'customer');

    function persist(): void {
        if (!import.meta.client) {
            return;
        }

        if (token.value) {
            localStorage.setItem('auth:token', token.value);
            localStorage.setItem('auth:role', role.value ?? '');
            return;
        }

        localStorage.removeItem('auth:token');
        localStorage.removeItem('auth:role');
    }

    function hydrate(): void {
        if (!import.meta.client) {
            return;
        }

        const savedToken = localStorage.getItem('auth:token');
        const savedRole = localStorage.getItem('auth:role');

        if (!savedToken || !savedRole) {
            return;
        }

        token.value = savedToken;
        role.value = savedRole as AuthRole;
    }

    async function loginAdmin(username: string, password: string): Promise<void> {
        const data = await $fetch<{ token: string }>('/api/admin/login', {
            method: 'POST',
            body: { username, password },
        });

        token.value = data.token;
        role.value = 'admin';
        user.value = null;
        persist();
    }

    async function loginCustomer(email: string, password: string): Promise<void> {
        const data = await $fetch<{ token: string }>('/api/customer/login', {
            method: 'POST',
            body: { email, username: email, password },
        });

        token.value = data.token;
        role.value = 'customer';
        persist();

        await fetchProfile();
    }

    async function register(payload: RegisterPayload): Promise<void> {
        await $fetch('/api/customer/register', {
            method: 'POST',
            body: payload,
        });

        await loginCustomer(payload.email, payload.password);
    }

    async function fetchProfile(): Promise<void> {
        if (!token.value || role.value !== 'customer') {
            return;
        }

        try {
            user.value = await $fetch<Customer>('/api/customer/me', {
                headers: { Authorization: `Bearer ${token.value}` },
            });
        } catch {
            // Profile fetch failed. Keep the current session state and let the caller react if needed.
        }
    }

    function logout(): void {
        token.value = null;
        role.value = null;
        user.value = null;
        persist();
    }

    function $reset(): void {
        logout();
    }

    return {
        token,
        role,
        user,
        isAuthenticated,
        isAdmin,
        isCustomer,
        persist,
        hydrate,
        loginAdmin,
        loginCustomer,
        register,
        fetchProfile,
        logout,
        $reset,
    };
});
