import { beforeEach, describe, expect, it } from 'vitest';
import ContractsTable from './ContractsTable.vue';
import {
    baseContract,
    basePendingContract,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('ContractsTable', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders contract actions in the contracts table and emits the row events', async () => {
        const wrapper = mountComponent(ContractsTable, {
            props: {
                loading: false,
                contracts: [basePendingContract, baseContract],
                sort: { column: 'createdAt', direction: 'desc' },
                columns: [
                    { key: 'participant', label: 'Teilnehmer' },
                    { key: 'state', label: 'Status' },
                    { key: 'actions', label: '' },
                ],
                resultSummary: '2 Verträge',
                showPagination: true,
                currentPage: 1,
                pageSize: 20,
                totalContracts: 2,
            },
        });

        await wrapper.get('[data-testid="approve-contract-mobile-contract-2"]').trigger('click');
        await wrapper.get('[data-testid="cancel-contract-mobile-contract-1"]').trigger('click');

        expect(wrapper.text()).toContain('Max');
        expect(wrapper.text()).toContain('79.00 EUR');
        expect(wrapper.emitted('approve')).toHaveLength(1);
        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });
});
