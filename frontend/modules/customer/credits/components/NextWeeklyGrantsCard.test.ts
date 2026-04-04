import { beforeEach, describe, expect, it } from 'vitest';
import NextWeeklyGrantsCard from './NextWeeklyGrantsCard.vue';
import {
    installComponentGlobals,
    mountComponent,
    nextWeeklyGrant,
} from '~/tests/components/component-test-helpers';

describe('NextWeeklyGrantsCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders next weekly grant hints', () => {
        const wrapper = mountComponent(NextWeeklyGrantsCard, {
            props: {
                loading: false,
                items: [nextWeeklyGrant],
            },
        });

        expect(wrapper.text()).toContain('Nächste Gutschriften');
        expect(wrapper.text()).toContain('+2 Credits');
        expect(wrapper.text()).toContain('formatted:2026-04-08T08:00:00+02:00');
    });
});
