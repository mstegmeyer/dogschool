import { beforeEach, describe, expect, it } from 'vitest';
import CustomersList from './List.vue';
import {
    baseCustomer,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomersList', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders customers and emits row selection', async () => {
        const wrapper = mountComponent(CustomersList, {
            props: {
                loading: false,
                customers: [baseCustomer],
                sort: { column: 'createdAt', direction: 'desc' },
                columns: [
                    { key: 'name', label: 'Name' },
                    { key: 'email', label: 'E-Mail' },
                    { key: 'createdAt', label: 'Seit' },
                    { key: 'address', label: 'Ort' },
                ],
                resultSummary: '1 Kunde',
                showPagination: true,
                currentPage: 1,
                pageSize: 20,
                totalCustomers: 1,
            },
        });

        await wrapper.get('[data-testid="customer-row-customer-1"]').trigger('click');

        expect(wrapper.text()).toContain('max@example.com');
        expect(wrapper.text()).toContain('formatted:2026-04-01T10:00:00+02:00');
        expect(wrapper.emitted('select')).toHaveLength(1);
    });
});
