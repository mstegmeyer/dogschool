import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { computed } from 'vue';
import { installNuxtGlobals, flushPromises } from '../nuxt/page-test-utils';
import { createFormFeedbackState, uiPageStubs } from '../nuxt/ui-test-stubs';

const loginAdminMock = vi.fn();
const loginCustomerMock = vi.fn();
const navigateToMock = vi.fn();

describe('auth login page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installNuxtGlobals();
        vi.stubGlobal('navigateTo', navigateToMock);
        vi.stubGlobal('useAuth', () => ({
            loginAdmin: loginAdminMock,
            loginCustomer: loginCustomerMock,
        }));
        vi.stubGlobal('useFormFeedback', () => createFormFeedbackState());
        vi.stubGlobal('computed', computed);
    });

    it('logs a customer in and redirects to the customer area', async () => {
        const Page = (await import('~/modules/auth/login/index.vue')).default;
        const wrapper = mount(Page, { global: { stubs: uiPageStubs } });

        const inputs = wrapper.findAll('input');
        await inputs[0]!.setValue('kunde@example.com');
        await inputs[1]!.setValue('secret');
        await wrapper.get('form').trigger('submit.prevent');
        await flushPromises();

        expect(loginCustomerMock).toHaveBeenCalledWith('kunde@example.com', 'secret');
        expect(navigateToMock).toHaveBeenCalledWith('/customer');
    });

    it('validates admin credentials and shows the mapped 401 error message', async () => {
        loginAdminMock.mockRejectedValueOnce({ statusCode: 401 });

        const Page = (await import('~/modules/auth/login/index.vue')).default;
        const wrapper = mount(Page, { global: { stubs: uiPageStubs } });
        const tabs = wrapper.findAll('button');

        await tabs[1]!.trigger('click');
        await wrapper.get('form').trigger('submit.prevent');
        await flushPromises();

        expect(wrapper.text()).toContain('Bitte prüfe die markierten Felder.');
        expect(wrapper.text()).toContain('Bitte einen Benutzernamen angeben.');
        expect(wrapper.text()).toContain('Bitte ein Passwort angeben.');

        const inputs = wrapper.findAll('input');
        await inputs[0]!.setValue('trainer');
        await inputs[1]!.setValue('wrong-secret');
        await wrapper.get('form').trigger('submit.prevent');
        await flushPromises();

        expect(loginAdminMock).toHaveBeenCalledWith('trainer', 'wrong-secret');
        expect(wrapper.text()).toContain('Anmeldung fehlgeschlagen. Bitte prüfe deine Zugangsdaten.');
    });
});
