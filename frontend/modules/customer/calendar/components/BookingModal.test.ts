import { beforeEach, describe, expect, it } from 'vitest';
import CustomerCalendarBookingModal from './BookingModal.vue';
import {
    baseDog,
    installComponentGlobals,
    makeCourseDate,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomerCalendarBookingModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders the booking modal and emits the selected dog and confirmation', async () => {
        const wrapper = mountComponent(CustomerCalendarBookingModal, {
            props: {
                modelValue: true,
                courseDate: makeCourseDate(),
                dogs: [baseDog, { ...baseDog, id: 'dog-2', name: 'Milo' }],
                bookingDogId: '',
                selectedDogId: 'dog-2',
                bookingInFlight: false,
            },
        });

        await wrapper.get('[data-testid="booking-dog-select"]').setValue('dog-2');
        await wrapper.get('[data-testid="confirm-booking"]').trigger('click');

        expect(wrapper.text()).toContain('Buchung');
        expect(wrapper.emitted('update:bookingDogId')?.[0]).toEqual(['dog-2']);
        expect(wrapper.emitted('confirm')).toHaveLength(1);
    });
});
