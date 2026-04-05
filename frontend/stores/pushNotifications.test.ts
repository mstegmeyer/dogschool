import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { usePushNotificationsStore } from './pushNotifications';

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

describe('push notifications store', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        setActivePinia(createPinia());
        postMock.mockResolvedValue(undefined);

        vi.stubGlobal('useApi', () => ({
            post: postMock,
        }));
        vi.stubGlobal('useRuntimeConfig', () => ({
            public: {
                webPushVapidPublicKey: currentPublicKey,
            },
        }));

        Object.defineProperty(window, 'PushManager', { value: class PushManager {}, configurable: true });
        Object.defineProperty(window, 'matchMedia', {
            value: vi.fn().mockReturnValue({ matches: false }),
            configurable: true,
        });
        Object.defineProperty(window, 'Notification', {
            value: {
                permission: 'granted',
                requestPermission: requestPermissionMock,
            },
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

    it('marks the store unsupported when the VAPID key is missing', async () => {
        vi.stubGlobal('useRuntimeConfig', () => ({
            public: {
                webPushVapidPublicKey: '',
            },
        }));
        const store = usePushNotificationsStore();

        await store.refreshStatus();

        expect(store.pushSupported).toBe(false);
        expect(store.pushStatus).toBe('unsupported');
        expect(store.pushPermission).toBe('unsupported');
        expect(store.pushSubscriptionActive).toBe(false);
    });

    it('refreshes to install-required on iOS outside standalone mode', async () => {
        const subscription = null;
        registerMock.mockResolvedValue({
            pushManager: {
                getSubscription: vi.fn().mockResolvedValue(subscription),
            },
        });
        Object.defineProperty(globalThis, 'Notification', {
            value: {
                permission: 'default',
                requestPermission: requestPermissionMock,
            },
            configurable: true,
        });
        Object.defineProperty(globalThis, 'navigator', {
            value: {
                userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)',
                serviceWorker: {
                    register: registerMock,
                },
            },
            configurable: true,
        });

        const store = usePushNotificationsStore();
        await store.refreshStatus();

        expect(store.pushStatus).toBe('install-required');
        expect(store.pushSubscriptionActive).toBe(false);
    });

    it('syncs an existing granted subscription without prompting', async () => {
        const subscription = createSubscription('https://push.example.test/current', [1, 0, 1]);
        registerMock.mockResolvedValue({
            pushManager: {
                getSubscription: vi.fn().mockResolvedValue(subscription),
            },
        });
        const store = usePushNotificationsStore();

        await store.syncPushRegistration('customer');

        expect(requestPermissionMock).not.toHaveBeenCalled();
        expect(postMock).toHaveBeenCalledWith('/api/customer/me/push-devices', {
            token: JSON.stringify({ endpoint: 'https://push.example.test/current' }),
            platform: 'web',
            provider: 'webpush',
            deviceName: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0)',
        });
        expect(store.pushStatus).toBe('enabled');
        expect(store.pushSubscriptionActive).toBe(true);
    });

    it('re-registers the subscription when the key changed', async () => {
        const staleSubscription = createSubscription('https://push.example.test/stale', [9, 9, 9]);
        const freshSubscription = createSubscription('https://push.example.test/fresh', [1, 0, 1]);
        const subscribeMock = vi.fn().mockResolvedValue(freshSubscription);
        registerMock.mockResolvedValue({
            pushManager: {
                getSubscription: vi.fn().mockResolvedValue(staleSubscription),
                subscribe: subscribeMock,
            },
        });
        const store = usePushNotificationsStore();

        await store.syncPushRegistration('customer');

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
        expect(store.pushStatus).toBe('enabled');
    });

    it('enablePush requests permission and creates a subscription', async () => {
        const subscription = createSubscription('https://push.example.test/new', [1, 0, 1]);
        const subscribeMock = vi.fn().mockResolvedValue(subscription);
        const notificationState = { permission: 'default' as NotificationPermission };
        requestPermissionMock.mockImplementation(async () => {
            notificationState.permission = 'granted';
            return 'granted';
        });
        Object.defineProperty(globalThis, 'Notification', {
            value: {
                get permission() {
                    return notificationState.permission;
                },
                requestPermission: requestPermissionMock,
            },
            configurable: true,
        });
        Object.defineProperty(window, 'Notification', {
            value: {
                get permission() {
                    return notificationState.permission;
                },
                requestPermission: requestPermissionMock,
            },
            configurable: true,
        });
        registerMock.mockResolvedValue({
            pushManager: {
                getSubscription: vi.fn().mockResolvedValue(null),
                subscribe: subscribeMock,
            },
        });
        const store = usePushNotificationsStore();

        const result = await store.enablePush('admin');

        expect(result).toBe(true);
        expect(requestPermissionMock).toHaveBeenCalledOnce();
        expect(postMock).toHaveBeenCalledWith('/api/admin/me/push-devices', {
            token: JSON.stringify({ endpoint: 'https://push.example.test/new' }),
            platform: 'web',
            provider: 'webpush',
            deviceName: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0)',
        });
        expect(store.pushStatus).toBe('enabled');
    });

    it('disablePush unregisters an active subscription', async () => {
        const subscription = createSubscription('https://push.example.test/current', [1, 0, 1]);
        registerMock.mockResolvedValue({
            pushManager: {
                getSubscription: vi.fn().mockResolvedValue(subscription),
            },
        });
        const store = usePushNotificationsStore();

        const result = await store.disablePush('customer');

        expect(result).toBe(true);
        expect(postMock).toHaveBeenCalledWith('/api/customer/me/push-devices/unregister', {
            token: JSON.stringify({ endpoint: 'https://push.example.test/current' }),
        });
        expect(store.pushStatus).toBe('available');
        expect(store.pushSubscriptionActive).toBe(false);
    });

    it('syncPushRegistration captures runtime failures as an error state', async () => {
        registerMock.mockRejectedValueOnce(new Error('Registration failed'));
        const store = usePushNotificationsStore();

        await store.syncPushRegistration('customer');

        expect(store.pushStatus).toBe('error');
        expect(store.pushError).toBe('Registration failed');
    });

    it('reset clears the tracked push state', async () => {
        const subscription = createSubscription('https://push.example.test/current', [1, 0, 1]);
        registerMock.mockResolvedValue({
            pushManager: {
                getSubscription: vi.fn().mockResolvedValue(subscription),
            },
        });
        const store = usePushNotificationsStore();

        await store.refreshStatus();
        store.$reset();

        expect(store.pushStatus).toBe('unsupported');
        expect(store.pushError).toBeNull();
        expect(store.pushPermission).toBe('unsupported');
        expect(store.pushSupported).toBe(false);
        expect(store.pushSubscriptionActive).toBe(false);
    });
});
