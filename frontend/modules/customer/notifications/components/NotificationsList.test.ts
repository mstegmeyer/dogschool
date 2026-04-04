import { beforeEach, describe, expect, it } from 'vitest';
import CustomerNotificationsList from './NotificationsList.vue';
import {
    installComponentGlobals,
    makeNotification,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomerNotificationsList', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders customer notifications with the detail component content', () => {
        const wrapper = mountComponent(CustomerNotificationsList, {
            props: {
                loading: false,
                notifications: [makeNotification({ isPinned: true })],
            },
        });

        expect(wrapper.get('[data-testid="notification-card-notification-1"]').exists()).toBe(true);
        expect(wrapper.getComponent({ name: 'AppNotificationDetail' }).props('notification')).toMatchObject({ id: 'notification-1' });
    });
});
