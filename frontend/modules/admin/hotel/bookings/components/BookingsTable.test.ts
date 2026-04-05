import { beforeEach, describe, expect, it } from 'vitest';
import HotelBookingsTable from './BookingsTable.vue';
import { installComponentGlobals, mountComponent } from '~/tests/components/component-test-helpers';
import { hotelBooking } from '~/tests/modules/admin-page-helpers';

describe('HotelBookingsTable', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders booking rows and emits open for inspection', async () => {
        const wrapper = mountComponent(HotelBookingsTable, {
            props: {
                loading: false,
                bookings: [{
                    ...hotelBooking,
                    roomId: 'room-1',
                    roomName: 'Waldzimmer',
                }],
                resultSummary: '1–1 von 1 Hotelbuchungen',
                showPagination: false,
                currentPage: 1,
                pageSize: 20,
                totalBookings: 1,
            },
        });

        expect(wrapper.text()).toContain('Rex');
        expect(wrapper.text()).toContain('Max');
        expect(wrapper.text()).toContain('Waldzimmer');
        expect(wrapper.text()).toContain('REQUESTED');

        await wrapper.get('[data-testid="open-hotel-booking-hotel-booking-1"]').trigger('click');

        expect(wrapper.emitted('open')?.[0]).toEqual([{
            ...hotelBooking,
            roomId: 'room-1',
            roomName: 'Waldzimmer',
        }]);
    });

    it('emits pagination updates when multiple pages are shown', async () => {
        const wrapper = mountComponent(HotelBookingsTable, {
            props: {
                loading: false,
                bookings: [hotelBooking],
                resultSummary: '1–20 von 25 Hotelbuchungen',
                showPagination: true,
                currentPage: 1,
                pageSize: 20,
                totalBookings: 25,
            },
        });

        await wrapper.getComponent({ name: 'u-pagination-stub' }).get('button').trigger('click');

        expect(wrapper.emitted('update:currentPage')?.[0]).toEqual([2]);
    });
});
