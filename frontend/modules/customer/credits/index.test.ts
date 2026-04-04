import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    creditTransaction,
    installCustomerGlobals,
    mountCreditsPage,
} from '~/tests/modules/customer-page-helpers';

describe('customer credits page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installCustomerGlobals();
        apiGetMock.mockResolvedValue({
            balance: 5,
            nextWeeklyGrants: [{
                contractId: 'contract-1',
                amount: 2,
                dogName: 'Luna',
                nextGrantAt: '2026-04-07T10:00:00+02:00',
                currentWeekRef: '2026-W14',
                pendingGrantThisWeek: false,
            }],
            items: [creditTransaction],
        });
    });

    it('loads the credit summary and passes data to the child components', async () => {
        const wrapper = await mountCreditsPage();

        expect(wrapper.getComponent({ name: 'CreditBalanceSummary' }).props('balance')).toBe(5);
        expect((wrapper.getComponent({ name: 'NextWeeklyGrantsCard' }).props('items') as unknown[])).toHaveLength(1);
        expect((wrapper.getComponent({ name: 'CreditHistoryList' }).props('entries') as unknown[])).toHaveLength(1);
    });
});
