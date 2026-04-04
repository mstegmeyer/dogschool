import { beforeEach, describe, expect, it } from 'vitest';
import PendingContractsCard from './PendingContractsCard.vue';
import {
    basePendingContract,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('PendingContractsCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders pending contracts with contract pricing', () => {
        const wrapper = mountComponent(PendingContractsCard, {
            props: {
                loading: false,
                count: 1,
                contracts: [basePendingContract],
            },
        });

        expect(wrapper.text()).toContain('Offene Vertragsanfragen');
        expect(wrapper.text()).toContain('79.00 EUR');
        expect(wrapper.text()).toContain('Angefragt');
    });
});
