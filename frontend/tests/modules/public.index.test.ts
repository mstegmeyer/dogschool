import { beforeEach, describe, expect, it, vi } from 'vitest';
import { installComponentGlobals, mountComponent } from '../components/component-test-helpers';

describe('public index page', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/' });
    });

    it('declares that the public page disables layouts', async () => {
        const Page = (await import('~/modules/public/index.vue')).default;
        const wrapper = mountComponent(Page);
        const definePageMetaMock = vi.mocked(globalThis.definePageMeta as any);

        expect(wrapper.html()).toContain('<div');
        expect(definePageMetaMock).toHaveBeenCalledWith({ layout: false });
    });
});
