import { beforeEach, describe, expect, it, vi } from 'vitest';

const postMock = vi.fn();
const registerMock = vi.fn();
const requestPermissionMock = vi.fn();

const currentPublicKey = 'AQAB';

function createSubscription(endpoint: string, keyBytes: number[]): PushSubscription {
    return {
        options: {
            applicationServerKey: Uint8Array.from(keyBytes).buffer,
        },
        toJSON: () => ({ endpoint }),
        unsubscribe: vi.fn().mockResolvedValue(true),
    } as unknown as PushSubscription;
}

async function loadComposable() {
    const mod = await import('../../composables/usePushNotifications');
    return mod.usePushNotifications();
}

describe('usePushNotifications', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();

        vi.stubGlobal('useApi', () => ({
            post: postMock,
        }));
        vi.stubGlobal('useRuntimeConfig', () => ({
            public: {
                webPushVapidPublicKey: currentPublicKey,
            },
        }));

        Object.defineProperty(import.meta, 'client', { value: true, writable: true });
        Object.defineProperty(window, 'PushManager', { value: class PushManager {}, configurable: true });
        Object.defineProperty(window, 'matchMedia', {
            value: vi.fn().mockReturnValue({ matches: false }),
            configurable: true,
        });
        Object.defineProperty(document, 'referrer', { value: '', configurable: true });
        Object.defineProperty(globalThis, 'Notification', {
            value: {
                permission: 'granted',
                requestPermission: requestPermissionMock,
            },
            configurable: true,
        });
        Object.defineProperty(globalThis, 'navigator', {
            value: {
                userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0)',
                serviceWorker: {
                    register: registerMock,
                },
            },
            configurable: true,
        });
    });

    it('syncs an already granted subscription without prompting', async () => {
        const subscription = createSubscription('https://push.example.test/current', [1, 0, 1]);
        registerMock.mockResolvedValue({
            pushManager: {
                getSubscription: vi.fn().mockResolvedValue(subscription),
            },
        });

        const { syncPushRegistration, pushStatus } = await loadComposable();
        await syncPushRegistration('customer');

        expect(requestPermissionMock).not.toHaveBeenCalled();
        expect(postMock).toHaveBeenCalledWith('/api/customer/me/push-devices', {
            token: JSON.stringify({ endpoint: 'https://push.example.test/current' }),
            platform: 'web',
            provider: 'webpush',
            deviceName: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0)',
        });
        expect(pushStatus.value).toBe('enabled');
    });

    it('re-registers the subscription when the VAPID key changed', async () => {
        const staleSubscription = createSubscription('https://push.example.test/stale', [9, 9, 9]);
        const freshSubscription = createSubscription('https://push.example.test/fresh', [1, 0, 1]);
        const subscribeMock = vi.fn().mockResolvedValue(freshSubscription);

        registerMock.mockResolvedValue({
            pushManager: {
                getSubscription: vi.fn().mockResolvedValue(staleSubscription),
                subscribe: subscribeMock,
            },
        });

        const { syncPushRegistration, pushStatus } = await loadComposable();
        await syncPushRegistration('customer');

        expect(postMock).toHaveBeenNthCalledWith(1, '/api/customer/me/push-devices/unregister', {
            token: JSON.stringify({ endpoint: 'https://push.example.test/stale' }),
        });
        expect(subscribeMock).toHaveBeenCalledOnce();
        expect(postMock).toHaveBeenNthCalledWith(2, '/api/customer/me/push-devices', {
            token: JSON.stringify({ endpoint: 'https://push.example.test/fresh' }),
            platform: 'web',
            provider: 'webpush',
            deviceName: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0)',
        });
        expect(pushStatus.value).toBe('enabled');
    });
});
