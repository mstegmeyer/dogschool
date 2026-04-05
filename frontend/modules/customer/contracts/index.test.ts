import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
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

    afterEach(() => {
        vi.useRealTimers();
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

    it('clears a pending preview timer when the request modal closes or the page unmounts', async () => {
        vi.useFakeTimers();

        const wrapper = await mountContractsPage();
        const openButton = wrapper.findAll('button').find(button => button.text() === 'Vertrag anfragen');
        expect(openButton).toBeDefined();

        await openButton!.trigger('click');
        const modal = wrapper.getComponent({ name: 'RequestModal' });
        const form = modal.props('form') as Record<string, any>;
        form.dogId = 'dog-1';
        form.coursesPerWeek = 2;
        form.startDate = '2026-05-01';
        await flushPromises();

        await modal.vm.$emit('cancel');
        vi.advanceTimersByTime(300);
        await flushPromises();
        expect(apiPostMock).not.toHaveBeenCalledWith('/api/customer/contracts/preview', expect.anything());

        await openButton!.trigger('click');
        form.dogId = 'dog-1';
        form.coursesPerWeek = 2;
        form.startDate = '2026-05-01';
        await flushPromises();

        wrapper.unmount();
        vi.advanceTimersByTime(300);
        await flushPromises();
        expect(apiPostMock).not.toHaveBeenCalledWith('/api/customer/contracts/preview', expect.anything());
    });

    it('ignores late preview responses after the request modal closes', async () => {
        vi.useFakeTimers();

        let resolvePreview: ((value: unknown) => void) | null = null;
        apiPostMock.mockImplementation((url: string) => {
            if (url === '/api/customer/contracts/preview') {
                return new Promise(resolve => {
                    resolvePreview = resolve;
                });
            }

            return Promise.reject(new Error(`Unhandled POST ${url}`));
        });

        const wrapper = await mountContractsPage();
        const openButton = wrapper.findAll('button').find(button => button.text() === 'Vertrag anfragen');
        expect(openButton).toBeDefined();

        await openButton!.trigger('click');
        const modal = wrapper.getComponent({ name: 'RequestModal' });
        const form = modal.props('form') as Record<string, any>;
        form.dogId = 'dog-1';
        form.coursesPerWeek = 2;
        form.startDate = '2026-05-01';
        await flushPromises();

        vi.advanceTimersByTime(300);
        await flushPromises();
        expect(resolvePreview).not.toBeNull();

        await modal.vm.$emit('cancel');
        await flushPromises();

        resolvePreview?.({
            monthlyPrice: '160.00',
            registrationFee: '149.00',
            firstInvoiceTotal: '309.00',
            monthlyUnitPrice: '80.00',
            coursesPerWeek: 2,
            requiresRegistrationFee: true,
            snapshot: {
                type: 'contract',
                lineItems: [],
            },
        });
        await flushPromises();

        expect(modal.props('preview')).toBeNull();
        expect(modal.props('previewLoading')).toBe(false);
    });
});
