import { beforeEach, describe, expect, it } from 'vitest';
import CancelModal from './CancelModal.vue';
import {
    baseContract,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CancelModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('updates the cancel modal end date and emits submit', async () => {
        const wrapper = mountComponent(CancelModal, {
            props: {
                modelValue: true,
                contract: baseContract,
                endDate: '2026-04-30',
                endDateError: '',
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('[data-testid="contract-end-date"]').setValue('2026-05-31');
        await wrapper.get('[data-testid="confirm-contract-cancel"]').trigger('click');

        expect(wrapper.text()).toContain('Vertrag kündigen?');
        expect(wrapper.emitted('update:endDate')?.[0]).toEqual(['2026-05-31']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });
});
