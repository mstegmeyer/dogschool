import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { AuthRole } from '~/types';

export type PushStatus = 'unsupported' | 'install-required' | 'available' | 'enabled' | 'blocked' | 'error';

function endpointForRole(role: AuthRole): string {
    return role === 'admin'
        ? '/api/admin/me/push-devices'
        : '/api/customer/me/push-devices';
}

function isClient(): boolean {
    return import.meta.client;
}

function isIosBrowser(): boolean {
    if (!isClient()) {
        return false;
    }

    return /iPad|iPhone|iPod/.test(navigator.userAgent);
}

function supportsWebPush(vapidPublicKey: string): boolean {
    return isClient()
        && 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window
        && vapidPublicKey.trim() !== '';
}

function isStandaloneWebApp(): boolean {
    if (!isClient()) {
        return false;
    }

    const mediaQuery = window.matchMedia?.('(display-mode: standalone)').matches ?? false;
    const iosStandalone = 'standalone' in navigator && Boolean((navigator as Navigator & { standalone?: boolean }).standalone);

    return mediaQuery || iosStandalone || document.referrer.startsWith('android-app://');
}

function canPromptForNotifications(): boolean {
    return !isIosBrowser() || isStandaloneWebApp();
}

function decodeBase64UrlToUint8Array(value: string): Uint8Array {
    const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
    const padded = normalized.padEnd(normalized.length + ((4 - normalized.length % 4) % 4), '=');
    const decoded = atob(padded);

    return Uint8Array.from(decoded, char => char.charCodeAt(0));
}

function buffersEqual(a: Uint8Array, b: Uint8Array): boolean {
    if (a.length !== b.length) {
        return false;
    }

    return a.every((value, index) => value === b[index]);
}

function getSubscriptionToken(subscription: PushSubscription): string {
    return JSON.stringify(subscription.toJSON());
}

async function getPushRegistration(): Promise<ServiceWorkerRegistration> {
    return navigator.serviceWorker.register('/push-sw.js');
}

async function getExistingSubscription(): Promise<PushSubscription | null> {
    const registration = await getPushRegistration();

    return registration.pushManager.getSubscription();
}

function subscriptionMatchesCurrentKey(subscription: PushSubscription, vapidPublicKey: string): boolean {
    const currentKey = subscription.options.applicationServerKey;
    if (!currentKey) {
        return false;
    }

    return buffersEqual(
        new Uint8Array(currentKey),
        decodeBase64UrlToUint8Array(vapidPublicKey),
    );
}

export const usePushNotificationsStore = defineStore('pushNotifications', () => {
    const pushStatus = ref<PushStatus>('unsupported');
    const pushError = ref<string | null>(null);
    const pushPermission = ref<NotificationPermission | 'unsupported'>('unsupported');
    const pushSupported = ref(false);
    const pushSubscriptionActive = ref(false);
    const lastRegisteredSubscription = ref<string | null>(null);

    const runtimeConfig = useRuntimeConfig();

    function updateStatus(vapidPublicKey: string, subscription: PushSubscription | null): void {
        pushSupported.value = supportsWebPush(vapidPublicKey);
        pushError.value = null;

        if (!pushSupported.value) {
            pushPermission.value = 'unsupported';
            pushSubscriptionActive.value = false;
            pushStatus.value = 'unsupported';
            return;
        }

        pushPermission.value = Notification.permission;
        pushSubscriptionActive.value = subscription !== null;

        if (pushPermission.value === 'denied') {
            pushStatus.value = 'blocked';
            return;
        }

        if (!canPromptForNotifications() && pushPermission.value !== 'granted') {
            pushStatus.value = 'install-required';
            return;
        }

        pushStatus.value = subscription !== null && pushPermission.value === 'granted'
            ? 'enabled'
            : 'available';
    }

    async function persistSubscription(role: AuthRole, subscription: PushSubscription): Promise<void> {
        const token = getSubscriptionToken(subscription);
        if (token === lastRegisteredSubscription.value) {
            return;
        }

        lastRegisteredSubscription.value = token;

        await useApi().post(endpointForRole(role), {
            token,
            platform: 'web',
            provider: 'webpush',
            deviceName: navigator.userAgent.slice(0, 255),
        });
    }

    async function refreshStatus(): Promise<void> {
        if (!supportsWebPush(runtimeConfig.public.webPushVapidPublicKey)) {
            updateStatus(runtimeConfig.public.webPushVapidPublicKey, null);
            return;
        }

        const subscription = await getExistingSubscription();
        updateStatus(runtimeConfig.public.webPushVapidPublicKey, subscription);
    }

    async function unregisterSubscription(role: AuthRole, subscription: PushSubscription): Promise<void> {
        const token = getSubscriptionToken(subscription);

        await useApi().post(`${endpointForRole(role)}/unregister`, { token });
        await subscription.unsubscribe();

        lastRegisteredSubscription.value = null;
    }

    async function ensurePushRegistration(role: AuthRole): Promise<boolean> {
        if (!supportsWebPush(runtimeConfig.public.webPushVapidPublicKey)) {
            updateStatus(runtimeConfig.public.webPushVapidPublicKey, null);
            return false;
        }

        pushError.value = null;
        pushPermission.value = Notification.permission;

        if (pushPermission.value !== 'granted') {
            updateStatus(runtimeConfig.public.webPushVapidPublicKey, await getExistingSubscription());
            return false;
        }

        let subscription = await getExistingSubscription();

        if (subscription && !subscriptionMatchesCurrentKey(subscription, runtimeConfig.public.webPushVapidPublicKey)) {
            await unregisterSubscription(role, subscription);
            subscription = null;
        }

        if (!subscription) {
            const registration = await getPushRegistration();
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: decodeBase64UrlToUint8Array(runtimeConfig.public.webPushVapidPublicKey),
            });
        }

        await persistSubscription(role, subscription);
        updateStatus(runtimeConfig.public.webPushVapidPublicKey, subscription);

        return true;
    }

    async function enablePush(role: AuthRole): Promise<boolean> {
        if (!supportsWebPush(runtimeConfig.public.webPushVapidPublicKey)) {
            updateStatus(runtimeConfig.public.webPushVapidPublicKey, null);
            return false;
        }

        pushError.value = null;

        if (Notification.permission === 'denied') {
            updateStatus(runtimeConfig.public.webPushVapidPublicKey, await getExistingSubscription());
            return false;
        }

        if (Notification.permission === 'default' && !canPromptForNotifications()) {
            updateStatus(runtimeConfig.public.webPushVapidPublicKey, await getExistingSubscription());
            return false;
        }

        if (Notification.permission !== 'granted') {
            const permission = await Notification.requestPermission();
            pushPermission.value = permission;

            if (permission !== 'granted') {
                updateStatus(runtimeConfig.public.webPushVapidPublicKey, await getExistingSubscription());
                return false;
            }
        }

        return ensurePushRegistration(role);
    }

    async function disablePush(role: AuthRole): Promise<boolean> {
        if (!supportsWebPush(runtimeConfig.public.webPushVapidPublicKey)) {
            updateStatus(runtimeConfig.public.webPushVapidPublicKey, null);
            return false;
        }

        pushError.value = null;

        const subscription = await getExistingSubscription();
        if (!subscription) {
            updateStatus(runtimeConfig.public.webPushVapidPublicKey, null);
            return false;
        }

        await unregisterSubscription(role, subscription);
        updateStatus(runtimeConfig.public.webPushVapidPublicKey, null);

        return true;
    }

    async function syncPushRegistration(role: AuthRole): Promise<void> {
        if (!supportsWebPush(runtimeConfig.public.webPushVapidPublicKey)) {
            updateStatus(runtimeConfig.public.webPushVapidPublicKey, null);
            return;
        }

        try {
            if (Notification.permission === 'granted') {
                await ensurePushRegistration(role);
                return;
            }

            await refreshStatus();
        } catch (error) {
            pushError.value = error instanceof Error ? error.message : 'Unbekannter Fehler';
            pushStatus.value = 'error';
        }
    }

    function $reset(): void {
        pushStatus.value = 'unsupported';
        pushError.value = null;
        pushPermission.value = 'unsupported';
        pushSupported.value = false;
        pushSubscriptionActive.value = false;
        lastRegisteredSubscription.value = null;
    }

    return {
        pushStatus,
        pushError,
        pushPermission,
        pushSupported,
        pushSubscriptionActive,
        refreshStatus,
        syncPushRegistration,
        enablePush,
        disablePush,
        $reset,
    };
});
