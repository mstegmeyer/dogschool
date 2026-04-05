import { storeToRefs } from 'pinia';
import { readonly } from 'vue';
import { resolvePinia } from '~/stores/pinia';
import { usePushNotificationsStore } from '~/stores/pushNotifications';

export const usePushNotifications = () => {
    const store = usePushNotificationsStore(resolvePinia());
    const {
        pushStatus,
        pushError,
        pushPermission,
        pushSupported,
        pushSubscriptionActive,
    } = storeToRefs(store);

    return {
        pushStatus: readonly(pushStatus),
        pushError: readonly(pushError),
        pushPermission: readonly(pushPermission),
        pushSupported: readonly(pushSupported),
        pushSubscriptionActive: readonly(pushSubscriptionActive),
        refreshStatus: store.refreshStatus,
        syncPushRegistration: store.syncPushRegistration,
        enablePush: store.enablePush,
        disablePush: store.disablePush,
    };
};
