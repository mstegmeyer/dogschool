import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useAuthStore } from './auth';

const fetchMock = vi.fn();

const localStorageEntries: Record<string, string> = {};
const mockLocalStorage = {
    getItem: (key: string): string | null => localStorageEntries[key] ?? null,
    setItem: (key: string, value: string): void => {
        localStorageEntries[key] = value;
    },
    removeItem: (key: string): void => {
        delete localStorageEntries[key];
    },
    clear: (): void => {
        Object.keys(localStorageEntries).forEach((key) => {
            delete localStorageEntries[key];
        });
    },
};

vi.stubGlobal('localStorage', mockLocalStorage);
vi.stubGlobal('$fetch', fetchMock);

describe('auth store', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockLocalStorage.clear();
        setActivePinia(createPinia());
    });

    it('starts unauthenticated', () => {
        const store = useAuthStore();

        expect(store.token).toBeNull();
        expect(store.role).toBeNull();
        expect(store.user).toBeNull();
        expect(store.isAuthenticated).toBe(false);
        expect(store.isAdmin).toBe(false);
        expect(store.isCustomer).toBe(false);
    });

    it('loginAdmin stores token and role', async () => {
        fetchMock.mockResolvedValueOnce({ token: 'admin-token' });
        const store = useAuthStore();

        await store.loginAdmin('admin', 'password');

        expect(fetchMock).toHaveBeenCalledWith('/api/admin/login', {
            method: 'POST',
            body: { username: 'admin', password: 'password' },
        });
        expect(store.token).toBe('admin-token');
        expect(store.role).toBe('admin');
        expect(store.user).toBeNull();
        expect(store.isAdmin).toBe(true);
        expect(mockLocalStorage.getItem('auth:token')).toBe('admin-token');
        expect(mockLocalStorage.getItem('auth:role')).toBe('admin');
    });

    it('loginCustomer stores auth state and fetches the profile', async () => {
        fetchMock
            .mockResolvedValueOnce({ token: 'customer-token' })
            .mockResolvedValueOnce({ id: 'customer-1', name: 'Max', email: 'max@example.com' });
        const store = useAuthStore();

        await store.loginCustomer('max@example.com', 'secret');

        expect(fetchMock).toHaveBeenNthCalledWith(1, '/api/customer/login', {
            method: 'POST',
            body: { email: 'max@example.com', username: 'max@example.com', password: 'secret' },
        });
        expect(fetchMock).toHaveBeenNthCalledWith(2, '/api/customer/me', {
            headers: { Authorization: 'Bearer customer-token' },
        });
        expect(store.token).toBe('customer-token');
        expect(store.role).toBe('customer');
        expect(store.user).toEqual({ id: 'customer-1', name: 'Max', email: 'max@example.com' });
        expect(store.isCustomer).toBe(true);
    });

    it('register creates the customer and logs them in', async () => {
        fetchMock
            .mockResolvedValueOnce({})
            .mockResolvedValueOnce({ token: 'registered-token' })
            .mockResolvedValueOnce({ id: 'customer-2', name: 'New', email: 'new@example.com' });
        const store = useAuthStore();

        await store.register({ email: 'new@example.com', password: 'password', name: 'New' });

        expect(fetchMock).toHaveBeenNthCalledWith(1, '/api/customer/register', {
            method: 'POST',
            body: { email: 'new@example.com', password: 'password', name: 'New' },
        });
        expect(fetchMock).toHaveBeenNthCalledWith(2, '/api/customer/login', {
            method: 'POST',
            body: { email: 'new@example.com', username: 'new@example.com', password: 'password' },
        });
        expect(store.token).toBe('registered-token');
        expect(store.isAuthenticated).toBe(true);
    });

    it('hydrate restores state from localStorage', () => {
        mockLocalStorage.setItem('auth:token', 'saved-token');
        mockLocalStorage.setItem('auth:role', 'customer');
        const store = useAuthStore();

        store.hydrate();

        expect(store.token).toBe('saved-token');
        expect(store.role).toBe('customer');
        expect(store.isAuthenticated).toBe(true);
    });

    it('logout and reset clear state and persisted auth', async () => {
        fetchMock.mockResolvedValueOnce({ token: 'admin-token' });
        const store = useAuthStore();

        await store.loginAdmin('admin', 'password');
        store.logout();

        expect(store.token).toBeNull();
        expect(store.role).toBeNull();
        expect(store.user).toBeNull();
        expect(mockLocalStorage.getItem('auth:token')).toBeNull();
        expect(mockLocalStorage.getItem('auth:role')).toBeNull();

        fetchMock
            .mockResolvedValueOnce({ token: 'customer-token' })
            .mockResolvedValueOnce({ id: 'customer-1', name: 'Max', email: 'max@example.com' });

        await store.loginCustomer('max@example.com', 'secret');
        store.$reset();

        expect(store.isAuthenticated).toBe(false);
        expect(store.user).toBeNull();
    });

    it('fetchProfile is a no-op without a customer token', async () => {
        const store = useAuthStore();

        await store.fetchProfile();

        expect(fetchMock).not.toHaveBeenCalled();
    });

    it('fetchProfile swallows request failures', async () => {
        fetchMock
            .mockResolvedValueOnce({ token: 'customer-token' })
            .mockRejectedValueOnce(new Error('Network error'));
        const store = useAuthStore();

        await store.loginCustomer('max@example.com', 'secret');

        expect(store.user).toBeNull();
    });
});
