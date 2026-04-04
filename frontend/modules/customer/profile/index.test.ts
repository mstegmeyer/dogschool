import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiPutMock,
    disablePushMock,
    enablePushMock,
    fetchProfileMock,
    installCustomerGlobals,
    mountProfilePage,
    pushErrorRef,
    pushStatusRef,
    refreshStatusMock,
    userRef,
} from '~/tests/modules/customer-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('customer profile page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installCustomerGlobals();
        userRef.value = {
            id: 'customer-1',
            name: 'Max',
            email: 'max@example.com',
            address: { street: 'Musterweg 1', postalCode: '12345', city: 'Berlin' },
            bankAccount: { iban: 'DE89', bic: 'TESTDEFF', accountHolder: 'Max' },
        };
        pushStatusRef.value = 'available';
        pushErrorRef.value = '';
        fetchProfileMock.mockResolvedValue(undefined);
        refreshStatusMock.mockResolvedValue(undefined);
    });

    it('hydrates the form, validates saves, and toggles notification registration', async () => {
        const wrapper = await mountProfilePage();
        const formCard = wrapper.getComponent({ name: 'ProfileFormCard' });
        const notificationCard = wrapper.getComponent({ name: 'NotificationSettingsCard' });
        const form = formCard.props('form') as Record<string, any>;

        expect(form.name).toBe('Max');
        expect(notificationCard.props('statusLabel')).toBe('Verfügbar');

        form.name = '';
        form.email = '';
        await formCard.vm.$emit('submit');
        await flushPromises();
        expect(formCard.props('formError')).toBe('Bitte prüfe die markierten Felder.');

        form.name = 'Max Muster';
        form.email = 'neu@example.com';
        form.password = 'new-secret';
        apiPutMock.mockResolvedValue({});
        await formCard.vm.$emit('submit');
        await flushPromises();

        expect(apiPutMock).toHaveBeenCalledWith('/api/customer/me', {
            name: 'Max Muster',
            email: 'neu@example.com',
            password: 'new-secret',
            address: { street: 'Musterweg 1', postalCode: '12345', city: 'Berlin' },
            bankAccount: { iban: 'DE89', bic: 'TESTDEFF', accountHolder: 'Max' },
        });

        enablePushMock.mockResolvedValue(true);
        await notificationCard.vm.$emit('enable');
        await flushPromises();
        expect(enablePushMock).toHaveBeenCalledWith('customer');

        disablePushMock.mockResolvedValue(false);
        await notificationCard.vm.$emit('disable');
        await flushPromises();
        expect(disablePushMock).toHaveBeenCalledWith('customer');
    });
});
