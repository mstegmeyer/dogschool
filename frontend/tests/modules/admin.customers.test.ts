import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    customerRecord,
    installAdminGlobals,
    mountCustomersPage,
    navigateToMock,
} from './admin-page-helpers';
import { flushPromises } from '../nuxt/page-test-utils';

describe('admin customers page', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
        apiGetMock.mockImplementation((url: string) => {
            if (url.startsWith('/api/admin/customers?')) {
                return Promise.resolve({
                    items: [customerRecord],
                    pagination: { total: 1, pages: 1, page: 1, limit: 20 },
                });
            }

            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('loads customers, debounces search, and navigates to the detail page', async () => {
        const wrapper = await mountCustomersPage();
        const list = wrapper.getComponent({ name: 'CustomersList' });

        expect(apiGetMock).toHaveBeenCalledWith('/api/admin/customers?page=1&limit=20&sort=createdAt&direction=desc');
        expect(list.props('customers')).toHaveLength(1);

        await wrapper.get('[data-testid="customer-search"]').setValue('  Max  ');
        vi.advanceTimersByTime(250);
        await flushPromises();
        expect(apiGetMock).toHaveBeenLastCalledWith('/api/admin/customers?page=1&limit=20&q=Max&sort=createdAt&direction=desc');

        await list.vm.$emit('select', customerRecord);
        expect(navigateToMock).toHaveBeenCalledWith('/admin/customers/customer-1');
    });
});
