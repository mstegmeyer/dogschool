import { beforeEach, describe, expect, it } from 'vitest';
import ContractsList from './ContractsList.vue';
import {
    baseContract,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('ContractsList', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders contracts and their date range in the contracts list', () => {
        const wrapper = mountComponent(ContractsList, {
            props: {
                loading: false,
                contracts: [baseContract],
            },
        });

        expect(wrapper.text()).toContain('ACTIVE');
        expect(wrapper.text()).toContain('89.00 EUR');
        expect(wrapper.text()).toContain('formatted:2026-04-01');
    });
});
