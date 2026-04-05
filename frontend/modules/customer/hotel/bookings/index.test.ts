import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    apiPostMock,
    dog,
    hotelBooking,
    installCustomerGlobals,
    mountHotelBookingsPage,
} from '~/tests/modules/customer-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('customer hotel bookings page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installCustomerGlobals();
        apiGetMock.mockImplementation((url: string) => {
            if (url === '/api/customer/hotel-bookings') {
                return Promise.resolve({ items: [hotelBooking] });
            }
            if (url === '/api/customer/dogs') {
                return Promise.resolve({ items: [dog] });
            }
            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads bookings, validates the request form, and submits a hotel booking', async () => {
        const wrapper = await mountHotelBookingsPage();
        const modal = wrapper.getComponent({ name: 'HotelBookingRequestModal' });

        expect(wrapper.getComponent({ name: 'HotelBookingsList' }).props('bookings')).toHaveLength(1);

        await modal.vm.$emit('submit');
        await flushPromises();
        expect(modal.props('formError')).toBe('Bitte prüfe die markierten Felder.');

        const form = modal.props('form') as Record<string, any>;
        form.dogId = dog.id;
        form.currentShoulderHeightCm = 52;
        form.startAt = '2026-04-05T08:00';
        form.endAt = '2026-04-06T10:00';

        apiPostMock.mockResolvedValue({});
        await modal.vm.$emit('submit');
        await flushPromises();

        expect(apiPostMock).toHaveBeenCalledWith('/api/customer/hotel-bookings', {
            dogId: dog.id,
            startAt: '2026-04-05T08:00',
            endAt: '2026-04-06T10:00',
            currentShoulderHeightCm: 52,
            includesTravelProtection: false,
            customerComment: null,
        });
    });

    it('rejects end times outside the allowed handover window', async () => {
        const wrapper = await mountHotelBookingsPage();
        const modal = wrapper.getComponent({ name: 'HotelBookingRequestModal' });
        const form = modal.props('form') as Record<string, any>;

        form.dogId = dog.id;
        form.currentShoulderHeightCm = dog.shoulderHeightCm;
        form.startAt = '2026-04-05T08:00';
        form.endAt = '2026-04-06T05:30';

        await modal.vm.$emit('submit');
        await flushPromises();

        expect(modal.props('fieldErrors')).toMatchObject({
            endAt: 'Das Ende muss zwischen 06:00 und 22:00 Uhr liegen.',
        });
        expect(apiPostMock).not.toHaveBeenCalled();
    });
});
