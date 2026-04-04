import { beforeEach, describe, expect, it } from 'vitest';
import CreditAdjustCard from './CreditAdjustCard.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CreditAdjustCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('normalizes credit adjustments and emits field clearing', async () => {
        const wrapper = mountComponent(CreditAdjustCard, {
            props: {
                balance: 4,
                adjustAmount: null,
                adjustDescription: '',
                amountError: '',
                descriptionError: '',
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('input[type="number"]').setValue('5');
        await wrapper.get('input[placeholder="Grund der Korrektur"]').setValue('Sondergutschrift');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Guthaben');
        expect(wrapper.emitted('update:adjustAmount')?.[0]).toEqual([5]);
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['amount']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });
});
