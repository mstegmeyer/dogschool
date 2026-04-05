import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    activeContract,
    apiGetMock,
    apiPostMock,
    installAdminGlobals,
    mountContractsPage,
    routeQueryMock,
} from '~/tests/modules/admin-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('admin contracts page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
        apiGetMock.mockImplementation((url: string) => {
            if (url.startsWith('/api/admin/contracts?')) {
                return Promise.resolve({
                    items: [activeContract],
                    pagination: { total: 1, pages: 1, page: 1, limit: 20 },
                });
            }
            if (url === '/api/admin/contracts/contract-1') {
                return Promise.resolve(activeContract);
            }

            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads contracts, approves, declines, validates cancellation, and cancels with a month end', async () => {
        routeQueryMock.value = { state: 'open' };
        const wrapper = await mountContractsPage();
        const table = wrapper.getComponent({ name: 'ContractsTable' });
        const modal = wrapper.getComponent({ name: 'CancelModal' });
        const reviewModal = wrapper.getComponent({ name: 'ReviewModal' });

        expect(table.props('contracts')).toHaveLength(1);
        expect(apiGetMock).toHaveBeenNthCalledWith(1, '/api/admin/contracts?page=1&limit=20&state=open&sort=createdAt&direction=desc');

        apiPostMock.mockResolvedValue({});
        await table.vm.$emit('review', activeContract);
        await flushPromises();
        expect(reviewModal.props('modelValue')).toBe(true);
        expect(reviewModal.props('contract')).toMatchObject({ id: 'contract-1' });
        await reviewModal.vm.$emit('approve');
        await flushPromises();

        await table.vm.$emit('review', activeContract);
        await flushPromises();
        await reviewModal.vm.$emit('decline');
        await flushPromises();

        expect(apiPostMock).toHaveBeenNthCalledWith(1, '/api/admin/contracts/contract-1/approve', {
            price: '89.00',
            registrationFee: '149.00',
            adminComment: null,
        });
        expect(apiPostMock).toHaveBeenNthCalledWith(2, '/api/admin/contracts/contract-1/decline', {
            adminComment: null,
        });

        await table.vm.$emit('cancel', activeContract);
        await flushPromises();
        await modal.vm.$emit('submit');
        await flushPromises();

        expect(modal.props('formError')).toBe('Bitte prüfe die markierten Felder.');
        expect(modal.props('endDateError')).toBe('Bitte ein Enddatum wählen.');

        await modal.vm.$emit('update:end-date', '2026-04-15');
        await modal.vm.$emit('normalize-end-date');
        await flushPromises();
        expect(modal.props('endDate')).toBe('2026-04-30');

        await modal.vm.$emit('submit');
        await flushPromises();
        expect(apiPostMock).toHaveBeenCalledWith('/api/admin/contracts/contract-1/cancel', {
            endDate: '2026-04-30',
        });
    });
});
