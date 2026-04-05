import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    apiPostMock,
    contract,
    dog,
    installCustomerGlobals,
    mountContractsPage,
} from '~/tests/modules/customer-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('customer contracts page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installCustomerGlobals();
        apiGetMock.mockImplementation((url: string) => {
            if (url === '/api/customer/contracts') {
                return Promise.resolve({ items: [contract] });
            }
            if (url === '/api/customer/dogs') {
                return Promise.resolve({ items: [dog] });
            }
            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads contracts, validates requests, normalizes the start date, and submits', async () => {
        const wrapper = await mountContractsPage();
        const modal = wrapper.getComponent({ name: 'RequestModal' });

        expect(wrapper.getComponent({ name: 'ContractsList' }).props('contracts')).toHaveLength(1);
        expect(modal.props('dogOptions')).toEqual([{ label: 'Luna', value: 'dog-1' }]);

        await modal.vm.$emit('submit');
        await flushPromises();
        expect(modal.props('formError')).toBe('Bitte prüfe die markierten Felder.');

        const form = modal.props('form') as Record<string, any>;
        form.dogId = 'dog-1';
        form.coursesPerWeek = 3;
        form.startDate = '2026-05-12';
        await modal.vm.$emit('normalize-start-date');
        await flushPromises();
        expect(form.startDate).toBe('2026-05-01');

        apiPostMock.mockResolvedValue({});
        await modal.vm.$emit('submit');
        await flushPromises();
        expect(apiPostMock).toHaveBeenCalledWith('/api/customer/contracts', {
            dogId: 'dog-1',
            coursesPerWeek: 3,
            startDate: '2026-05-01',
            customerComment: null,
        });
    });
});
