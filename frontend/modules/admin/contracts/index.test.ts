import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    activeContract,
    apiGetMock,
    apiPostMock,
    installAdminGlobals,
    mountContractsPage,
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
        const wrapper = await mountContractsPage();
        const table = wrapper.getComponent({ name: 'ContractsTable' });
        const modal = wrapper.getComponent({ name: 'CancelModal' });

        expect(table.props('contracts')).toHaveLength(1);

        apiPostMock.mockResolvedValue({});
        await table.vm.$emit('review', activeContract);
        await flushPromises();
        await wrapper.findAll('button').find(button => button.text() === 'Bestätigen')?.trigger('click');
        await flushPromises();

        await table.vm.$emit('review', activeContract);
        await flushPromises();
        await wrapper.findAll('button').find(button => button.text() === 'Ablehnen')?.trigger('click');
        await flushPromises();

        expect(apiPostMock).toHaveBeenNthCalledWith(1, '/api/admin/contracts/contract-1/approve', {
            price: '89.00',
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
