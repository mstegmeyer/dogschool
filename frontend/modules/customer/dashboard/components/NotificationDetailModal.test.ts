import { beforeEach, describe, expect, it } from 'vitest';
import NotificationDetailModal from './NotificationDetailModal.vue';
import {
    baseNotification,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('NotificationDetailModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders the dashboard notification detail modal and closes it', async () => {
        const wrapper = mountComponent(NotificationDetailModal, {
            props: {
                modelValue: true,
                notification: baseNotification,
            },
        });

        await wrapper.get('button[aria-label="Mitteilung schliessen"]').trigger('click');

        expect(wrapper.text()).toContain('Mitteilung');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false]);
    });
});
