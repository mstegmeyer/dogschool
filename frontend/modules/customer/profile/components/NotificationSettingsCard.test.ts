import { beforeEach, describe, expect, it } from 'vitest';
import NotificationSettingsCard from './NotificationSettingsCard.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('NotificationSettingsCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders notification settings and emits enable and disable actions', async () => {
        const wrapper = mountComponent(NotificationSettingsCard, {
            props: {
                loading: false,
                badgeColor: 'green',
                statusLabel: 'Aktiv',
                alertColor: 'green',
                statusTitle: 'Push aktiv',
                statusDescription: 'Du erhältst Hinweise.',
                canEnable: true,
                canDisable: false,
                saving: false,
            },
        });

        await wrapper.get('[data-testid="enable-notifications"]').trigger('click');
        await wrapper.setProps({ canEnable: false, canDisable: true });
        await wrapper.get('[data-testid="disable-notifications"]').trigger('click');

        expect(wrapper.text()).toContain('Benachrichtigungen');
        expect(wrapper.emitted('enable')).toHaveLength(1);
        expect(wrapper.emitted('disable')).toHaveLength(1);
    });
});
