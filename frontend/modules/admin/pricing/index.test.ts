import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import {
    apiGetMock,
    installAdminGlobals,
    pricingConfig,
    toastAddMock,
} from '~/tests/modules/admin-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';
import { uiPageStubs } from '~/tests/nuxt/ui-test-stubs';

describe('admin pricing page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
    });

    it('loads the pricing config on mount', async () => {
        apiGetMock.mockResolvedValue(pricingConfig);

        const Page = (await import('~/modules/admin/pricing/index.vue')).default;
        const wrapper = mount(Page, {
            global: {
                stubs: {
                    ...uiPageStubs,
                },
            },
        });
        await flushPromises();

        expect(apiGetMock).toHaveBeenCalledWith('/api/admin/pricing');
        expect(wrapper.text()).toContain('Hundeschule');
    });

    it('shows an error toast when loading the pricing config fails', async () => {
        apiGetMock.mockRejectedValue(new Error('pricing load failed'));

        const Page = (await import('~/modules/admin/pricing/index.vue')).default;
        const wrapper = mount(Page, {
            global: {
                stubs: {
                    ...uiPageStubs,
                },
            },
        });
        await flushPromises();

        expect(toastAddMock).toHaveBeenCalledWith(expect.objectContaining({
            title: 'pricing load failed',
            color: 'red',
        }));
        expect(wrapper.text()).toContain('pricing load failed');
    });
});
