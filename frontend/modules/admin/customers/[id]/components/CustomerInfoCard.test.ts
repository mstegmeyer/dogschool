import { beforeEach, describe, expect, it } from 'vitest';
import CustomerInfoCard from './CustomerInfoCard.vue';
import {
    baseCustomer,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomerInfoCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders customer info details with formatted registration date', () => {
        const wrapper = mountComponent(CustomerInfoCard, {
            props: {
                customer: baseCustomer,
            },
        });

        expect(wrapper.text()).toContain('Kundendaten');
        expect(wrapper.text()).toContain('formatted:2026-04-01T10:00:00+02:00');
    });
});
