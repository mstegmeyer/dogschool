import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref, computed as vueComputed } from 'vue';

const navigateToMock = vi.fn();

const tokenRef = ref<string | null>(null);
const roleRef = ref<'admin' | 'customer' | null>(null);

const isAuthenticated = vueComputed(() => !!tokenRef.value);
const isAdmin = vueComputed(() => roleRef.value === 'admin');
const isCustomer = vueComputed(() => roleRef.value === 'customer');
const hydrateMock = vi.fn();

vi.stubGlobal('useAuth', () => ({
    isAuthenticated,
    isAdmin,
    isCustomer,
    hydrate: hydrateMock,
    token: tokenRef,
    role: roleRef,
}));
vi.stubGlobal('navigateTo', navigateToMock);
vi.stubGlobal('computed', vueComputed);
vi.stubGlobal('useState', (_key: string, init?: () => unknown) => ref(init?.() ?? null));

let middlewareFn: (to: { path: string }) => unknown;

vi.stubGlobal('defineNuxtRouteMiddleware', (fn: (to: { path: string }) => unknown) => {
    middlewareFn = fn;
    return fn;
});

// Force re-import each test run
beforeEach(async () => {
    vi.clearAllMocks();
    tokenRef.value = null;
    roleRef.value = null;

    vi.resetModules();
    await import('../../middleware/auth.global');
});

describe('auth middleware', () => {
    it('redirects unauthenticated users from / to /login', () => {
        middlewareFn({ path: '/' });
        expect(navigateToMock).toHaveBeenCalledWith('/login');
    });

    it('allows unauthenticated access to /login', () => {
        middlewareFn({ path: '/login' });
        expect(navigateToMock).not.toHaveBeenCalled();
    });

    it('allows unauthenticated access to /register', () => {
        middlewareFn({ path: '/register' });
        expect(navigateToMock).not.toHaveBeenCalled();
    });

    it('redirects authenticated admin from /login to /admin', () => {
        tokenRef.value = 'jwt';
        roleRef.value = 'admin';
        middlewareFn({ path: '/login' });
        expect(navigateToMock).toHaveBeenCalledWith('/admin');
    });

    it('redirects authenticated customer from / to /customer', () => {
        tokenRef.value = 'jwt';
        roleRef.value = 'customer';
        middlewareFn({ path: '/' });
        expect(navigateToMock).toHaveBeenCalledWith('/customer');
    });

    it('redirects unauthenticated users from protected routes to /login', () => {
        middlewareFn({ path: '/admin/courses' });
        expect(navigateToMock).toHaveBeenCalledWith('/login');
    });

    it('redirects customer away from /admin', () => {
        tokenRef.value = 'jwt';
        roleRef.value = 'customer';
        middlewareFn({ path: '/admin/courses' });
        expect(navigateToMock).toHaveBeenCalledWith('/customer');
    });

    it('redirects admin away from /customer', () => {
        tokenRef.value = 'jwt';
        roleRef.value = 'admin';
        middlewareFn({ path: '/customer/dogs' });
        expect(navigateToMock).toHaveBeenCalledWith('/admin');
    });

    it('calls hydrate when not authenticated', () => {
        middlewareFn({ path: '/customer/dogs' });
        expect(hydrateMock).toHaveBeenCalled();
    });
});
