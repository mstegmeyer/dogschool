import { beforeEach, describe, expect, it } from 'vitest';
import HotelBookingDetailModal from './DetailModal.vue';
import { installComponentGlobals, mountComponent } from '~/tests/components/component-test-helpers';
import { hotelBooking } from '~/tests/modules/admin-page-helpers';

describe('HotelBookingDetailModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders booking details, room availability and emits actions', async () => {
        const wrapper = mountComponent(HotelBookingDetailModal, {
            props: {
                modelValue: true,
                booking: hotelBooking,
                selectedRoomId: '',
                roomOptions: [{ label: 'Waldzimmer · 14 m²', value: 'room-1' }],
                assigning: false,
                confirming: false,
                declining: false,
            },
        });

        expect(wrapper.text()).toContain('Rex');
        expect(wrapper.text()).toContain('Aktueller Platzbedarf: 8 m²');
        expect(wrapper.text()).toContain('Waldzimmer');
        expect(wrapper.get('[data-testid="confirm-hotel-booking"]').attributes('disabled')).toBeDefined();

        await wrapper.get('[data-testid="hotel-booking-room-select"]').setValue('room-1');
        await wrapper.setProps({ selectedRoomId: 'room-1' });
        await wrapper.get('[data-testid="assign-hotel-booking-room"]').trigger('click');
        await wrapper.get('[data-testid="decline-hotel-booking"]').trigger('click');
        await wrapper.get('button[type="button"]').trigger('click');

        expect(wrapper.emitted('update:selectedRoomId')?.[0]).toEqual(['room-1']);
        expect(wrapper.emitted('assign-room')).toHaveLength(1);
        expect(wrapper.emitted('decline')).toHaveLength(1);
        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });

    it('enables confirming once the booking already has a room', async () => {
        const wrapper = mountComponent(HotelBookingDetailModal, {
            props: {
                modelValue: true,
                booking: {
                    ...hotelBooking,
                    roomId: 'room-1',
                    roomName: 'Waldzimmer',
                    state: 'CONFIRMED',
                },
                selectedRoomId: 'room-1',
                roomOptions: [{ label: 'Waldzimmer · 14 m²', value: 'room-1' }],
                assigning: false,
                confirming: false,
                declining: false,
            },
        });

        expect(wrapper.get('[data-testid="confirm-hotel-booking"]').attributes('disabled')).toBeUndefined();

        await wrapper.get('[data-testid="confirm-hotel-booking"]').trigger('click');

        expect(wrapper.emitted('confirm')).toHaveLength(1);
    });
});
