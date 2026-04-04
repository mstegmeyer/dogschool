import { beforeEach, describe, expect, it } from 'vitest';
import CreditBalanceSummary from './CreditBalanceSummary.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CreditBalanceSummary', () => {
    beforeEach(() => {
        installComponentGlobals();
    });

    it('shows compact negative balances with the alert color', () => {
        const wrapper = mountComponent(CreditBalanceSummary, {
            props: {
                loading: false,
                balance: -2,
                compact: true,
            },
        });

        expect(wrapper.text()).toContain('-2');
        expect(wrapper.text()).toContain('Verfügbare Credits');
        expect(wrapper.get('p').classes()).toContain('text-red-500');
    });
});
