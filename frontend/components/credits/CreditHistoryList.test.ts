import { beforeEach, describe, expect, it } from 'vitest';
import CreditHistoryList from './CreditHistoryList.vue';
import {
    baseCreditTransaction,
    installComponentGlobals,
    makeCreditColumns,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CreditHistoryList', () => {
    beforeEach(() => {
        installComponentGlobals();
    });

    it('renders credit history entries with amount and type formatting', () => {
        const wrapper = mountComponent(CreditHistoryList, {
            props: {
                entries: [baseCreditTransaction],
                columns: makeCreditColumns(),
            },
        });

        expect(wrapper.text()).toContain('+2');
        expect(wrapper.text()).toContain('WEEKLY_GRANT');
        expect(wrapper.text()).toContain('formatted:2026-04-01T10:00:00+02:00');
    });
});
