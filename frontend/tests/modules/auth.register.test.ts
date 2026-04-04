import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { createFormFeedbackState, uiPageStubs } from '../nuxt/ui-test-stubs';
import { installNuxtGlobals, flushPromises } from '../nuxt/page-test-utils';

const registerMock = vi.fn();
const navigateToMock = vi.fn();

describe('auth register page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installNuxtGlobals();
        vi.stubGlobal('navigateTo', navigateToMock);
        vi.stubGlobal('useAuth', () => ({
            register: registerMock,
        }));
        vi.stubGlobal('useFormFeedback', () => createFormFeedbackState());
    });

    it('validates registration and redirects to the customer area on success', async () => {
        const Page = (await import('~/modules/auth/register/index.vue')).default;
        const wrapper = mount(Page, { global: { stubs: uiPageStubs } });

        await wrapper.get('form').trigger('submit.prevent');
        await flushPromises();
        expect(wrapper.text()).toContain('Bitte prüfe die markierten Felder.');
        expect(wrapper.text()).toContain('Bitte einen Namen angeben.');

        const inputs = wrapper.findAll('input');
        await inputs[0]!.setValue('Max Muster');
        await inputs[1]!.setValue('max@example.com');
        await inputs[2]!.setValue('secret');
        await inputs[3]!.setValue('secret');
        await wrapper.get('form').trigger('submit.prevent');
        await flushPromises();

        expect(registerMock).toHaveBeenCalledWith({
            name: 'Max Muster',
            email: 'max@example.com',
            password: 'secret',
        });
        expect(navigateToMock).toHaveBeenCalledWith('/customer');
    });
});
