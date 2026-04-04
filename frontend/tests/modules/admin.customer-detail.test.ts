import { beforeEach, describe, expect, it, vi } from 'vitest';
import { namedStub, flushPromises } from '../nuxt/page-test-utils';
import {
    apiGetMock,
    apiPostMock,
    baseCreditTransaction,
    baseCustomer,
    installComponentGlobals,
    mountComponent,
    toastAddMock,
} from '../components/component-test-helpers';

describe('admin customer detail page', () => {
    beforeEach(() => {
        vi.resetModules();
        installComponentGlobals({
            path: '/admin/customers/customer-1',
            params: { id: 'customer-1' },
        });
        apiGetMock.mockImplementation((url: string) => {
            if (url === '/api/admin/customers/customer-1') {
                return Promise.resolve(baseCustomer);
            }
            if (url === '/api/admin/credits?customerId=customer-1') {
                return Promise.resolve({ balance: 4, items: [baseCreditTransaction] });
            }
            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads the customer record and posts credit adjustments', async () => {
        const Page = (await import('~/modules/admin/customers/[id]/index.vue')).default;
        const wrapper = mountComponent(Page, {
            global: {
                stubs: {
                    CreditHistoryList: namedStub('CreditHistoryList', ['entries', 'columns']),
                    CreditAdjustCard: namedStub(
                        'CreditAdjustCard',
                        ['balance', 'adjustAmount', 'adjustDescription', 'amountError', 'descriptionError', 'formError', 'saving'],
                        ['submit', 'clear-field-error', 'update:adjustAmount', 'update:adjustDescription'],
                    ),
                    CustomerInfoCard: namedStub('CustomerInfoCard', ['customer']),
                    CustomerLoadingState: namedStub('CustomerLoadingState'),
                },
            },
        });

        await flushPromises();

        expect(apiGetMock).toHaveBeenCalledWith('/api/admin/customers/customer-1');
        expect(apiGetMock).toHaveBeenCalledWith('/api/admin/credits?customerId=customer-1');

        const adjustCard = wrapper.getComponent({ name: 'CreditAdjustCard' });
        expect(adjustCard.props('balance')).toBe(4);

        await adjustCard.vm.$emit('update:adjustAmount', 3);
        await adjustCard.vm.$emit('update:adjustDescription', 'Bonus');
        await adjustCard.vm.$emit('submit');
        await flushPromises();

        expect(apiPostMock).toHaveBeenCalledWith('/api/admin/credits/adjust', {
            customerId: 'customer-1',
            amount: 3,
            description: 'Bonus',
        });
        expect(toastAddMock).toHaveBeenCalledWith({ title: 'Guthaben angepasst', color: 'green' });
    });
});
