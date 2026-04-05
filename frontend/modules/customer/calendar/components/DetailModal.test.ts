import { beforeEach, describe, expect, it } from 'vitest';
import CustomerCalendarDetailModal from './DetailModal.vue';
import {
    baseBookedCourseDate,
    baseDog,
    baseTrainer,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomerCalendarDetailModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders course metadata and closes via the footer action', async () => {
        const wrapper = mountComponent(CustomerCalendarDetailModal, {
            props: {
                modelValue: true,
                courseDate: {
                    ...baseBookedCourseDate,
                    comment: 'Nachholstunde',
                    trainer: baseTrainer,
                },
                dogs: [baseDog],
            },
        });

        await wrapper.get('button:last-of-type').trigger('click');

        expect(wrapper.get('[data-testid="customer-calendar-detail-modal"]').text()).toContain('formatted:2026-04-04');
        expect(wrapper.text()).toContain('09:00 – 10:00');
        expect(wrapper.text()).toContain(baseTrainer.fullName);
        expect(wrapper.text()).toContain('Gebucht für Luna');
        expect(wrapper.text()).toContain('Nachholstunde');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false]);
    });
});
