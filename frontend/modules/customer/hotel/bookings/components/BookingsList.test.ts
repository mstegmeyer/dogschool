import { beforeEach, describe, expect, it } from 'vitest';
import HotelBookingsList from './BookingsList.vue';
import { installComponentGlobals, mountComponent } from '~/tests/components/component-test-helpers';
import { hotelBooking } from '~/tests/modules/customer-page-helpers';

describe('HotelBookingsList', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders the loading state', () => {
        const wrapper = mountComponent(HotelBookingsList, {
            props: {
                loading: true,
                bookings: [],
            },
        });

        expect(wrapper.text()).toContain('loading');
    });

    it('renders the empty state when no bookings exist', () => {
        const wrapper = mountComponent(HotelBookingsList, {
            props: {
                loading: false,
                bookings: [],
            },
        });

        expect(wrapper.text()).toContain('Noch keine Hotelbuchungen vorhanden.');
    });

    it('renders booking details for the customer overview', () => {
        const wrapper = mountComponent(HotelBookingsList, {
            props: {
                loading: false,
                bookings: [hotelBooking],
            },
        });

        expect(wrapper.text()).toContain('Luna');
        expect(wrapper.text()).toContain('formatted:2026-04-05T08:00:00+02:00');
        expect(wrapper.text()).toContain('formatted:2026-04-06T18:00:00+02:00');
        expect(wrapper.text()).toContain('Raum: Waldzimmer');
        expect(wrapper.text()).toContain('REQUESTED');
    });
});
