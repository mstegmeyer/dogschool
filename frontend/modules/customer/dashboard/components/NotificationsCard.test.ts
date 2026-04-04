import { beforeEach, describe, expect, it } from 'vitest';
import NotificationsCard from './NotificationsCard.vue';
import {
    installComponentGlobals,
    makeNotification,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('NotificationsCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders dashboard notifications and emits the selected item', async () => {
        const wrapper = mountComponent(NotificationsCard, {
            props: {
                loading: false,
                notifications: [makeNotification({ isPinned: true, isGlobal: true })],
            },
        });

        await wrapper.get('[data-testid="dashboard-notification-notification-1"]').trigger('click');

        expect(wrapper.text()).toContain('Alle Kurse');
        expect(wrapper.emitted('select')).toHaveLength(1);
    });
});
