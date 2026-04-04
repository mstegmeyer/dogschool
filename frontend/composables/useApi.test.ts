import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

const fetchMock = vi.fn();
const navigateToMock = vi.fn();
const logoutMock = vi.fn();
const tokenRef = ref<string | null>(null);

vi.stubGlobal('$fetch', fetchMock);
vi.stubGlobal('navigateTo', navigateToMock);
vi.stubGlobal('useAuth', () => ({
    token: tokenRef,
    logout: logoutMock,
}));

async function loadComposable() {
    const mod = await import('./useApi');
    return mod.useApi();
}

describe('useApi', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        tokenRef.value = null;
    });

    it('adds the auth token to requests when available', async () => {
        tokenRef.value = 'jwt-token';
        fetchMock.mockResolvedValueOnce({ ok: true });

        const { get } = await loadComposable();
        await get('/api/customer/profile');

        expect(fetchMock).toHaveBeenCalledWith('/api/customer/profile', {
            headers: {
                Accept: 'application/json',
                Authorization: 'Bearer jwt-token',
            },
        });
    });

    it('uses the expected HTTP methods for write helpers', async () => {
        fetchMock
            .mockResolvedValueOnce({ ok: true })
            .mockResolvedValueOnce({ ok: true })
            .mockResolvedValueOnce({ ok: true });

        const { post, put, del } = await loadComposable();
        await post('/api/customer/dogs', { name: 'Rex' });
        await put('/api/customer/profile', { name: 'Max' });
        await del('/api/customer/me/push-devices/device-1');

        expect(fetchMock).toHaveBeenNthCalledWith(1, '/api/customer/dogs', {
            method: 'POST',
            body: { name: 'Rex' },
            headers: { Accept: 'application/json' },
        });
        expect(fetchMock).toHaveBeenNthCalledWith(2, '/api/customer/profile', {
            method: 'PUT',
            body: { name: 'Max' },
            headers: { Accept: 'application/json' },
        });
        expect(fetchMock).toHaveBeenNthCalledWith(3, '/api/customer/me/push-devices/device-1', {
            method: 'DELETE',
            headers: { Accept: 'application/json' },
        });
    });

    it('logs out and redirects when the API returns a 401 statusCode', async () => {
        const unauthorizedError = { statusCode: 401 };
        fetchMock.mockRejectedValueOnce(unauthorizedError);

        const { get } = await loadComposable();

        await expect(get('/api/customer/credits')).rejects.toBe(unauthorizedError);
        expect(logoutMock).toHaveBeenCalledOnce();
        expect(navigateToMock).toHaveBeenCalledWith('/login');
    });

    it('logs out and redirects when the API returns a 401 status field', async () => {
        const unauthorizedError = { status: 401 };
        fetchMock.mockRejectedValueOnce(unauthorizedError);

        const { get } = await loadComposable();

        await expect(get('/api/customer/contracts')).rejects.toBe(unauthorizedError);
        expect(logoutMock).toHaveBeenCalledOnce();
        expect(navigateToMock).toHaveBeenCalledWith('/login');
    });

    it('rethrows non-authentication errors without logging out', async () => {
        const apiError = { statusCode: 500 };
        fetchMock.mockRejectedValueOnce(apiError);

        const { get } = await loadComposable();

        await expect(get('/api/customer/calendar')).rejects.toBe(apiError);
        expect(logoutMock).not.toHaveBeenCalled();
        expect(navigateToMock).not.toHaveBeenCalled();
    });
});
