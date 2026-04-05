import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    apiPostMock,
    apiPutMock,
    hotelBooking,
    installAdminGlobals,
    mountHotelAdminBookingsPage,
    toastAddMock,
} from '~/tests/modules/admin-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('admin hotel bookings page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
        apiGetMock.mockImplementation((url: string) => {
            if (url.startsWith('/api/admin/hotel/bookings?')) {
                return Promise.resolve({
                    items: [hotelBooking],
                    pagination: { page: 1, limit: 20, total: 1, pages: 1 },
                });
            }
            if (url === '/api/admin/hotel/bookings/hotel-booking-1') {
                return Promise.resolve(hotelBooking);
            }
            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads hotel bookings with pagination, opens detail, assigns a room, confirms, and declines', async () => {
        const wrapper = await mountHotelAdminBookingsPage();
        const table = wrapper.getComponent({ name: 'HotelBookingsTable' });
        const modal = wrapper.getComponent({ name: 'HotelBookingDetailModal' });

        expect(apiGetMock.mock.calls[0]?.[0]).toContain('state=REQUESTED');
        expect(apiGetMock.mock.calls[0]?.[0]).toContain('page=1');
        expect(apiGetMock.mock.calls[0]?.[0]).toContain('limit=20');
        expect(table.props('bookings')).toHaveLength(1);
        expect(table.props('totalBookings')).toBe(1);

        await table.vm.$emit('open', hotelBooking);
        await flushPromises();

        expect(modal.props('booking')).toEqual(hotelBooking);

        await modal.vm.$emit('update:selectedRoomId', 'room-1');
        apiPutMock.mockResolvedValue(hotelBooking);
        await modal.vm.$emit('assign-room');
        await flushPromises();
        expect(apiPutMock).toHaveBeenCalledWith('/api/admin/hotel/bookings/hotel-booking-1/room', {
            roomId: 'room-1',
        });

        apiPostMock.mockResolvedValue({});
        await modal.vm.$emit('confirm');
        await flushPromises();

        await table.vm.$emit('open', hotelBooking);
        await flushPromises();
        await modal.vm.$emit('decline');
        await flushPromises();

        expect(apiPostMock).toHaveBeenNthCalledWith(1, '/api/admin/hotel/bookings/hotel-booking-1/confirm');
        expect(apiPostMock).toHaveBeenNthCalledWith(2, '/api/admin/hotel/bookings/hotel-booking-1/decline');
    });

    it('resets filters back to requested and reloads the first page', async () => {
        apiGetMock.mockResolvedValue({
            items: [hotelBooking],
            pagination: { page: 2, limit: 20, total: 25, pages: 2 },
        });

        const wrapper = await mountHotelAdminBookingsPage();
        const table = wrapper.getComponent({ name: 'HotelBookingsTable' });
        const buttons = wrapper.findAll('button');
        const resetButton = buttons.find(button => button.text() === 'Zurücksetzen');

        expect(resetButton).toBeDefined();

        await wrapper.get('[data-testid="hotel-booking-state-filter"]').setValue('CONFIRMED');
        await wrapper.get('[data-testid="hotel-booking-from-filter"]').setValue('2026-04-05T00:00');
        await wrapper.get('[data-testid="hotel-booking-to-filter"]').setValue('2026-04-06T00:00');
        await flushPromises();

        await table.vm.$emit('update:currentPage', 2);
        await flushPromises();
        expect(apiGetMock.mock.calls.at(-1)?.[0]).toContain('page=2');

        apiGetMock.mockClear();
        await resetButton!.trigger('click');
        await flushPromises();

        expect(apiGetMock.mock.calls.some(call =>
            typeof call[0] === 'string'
            && call[0].includes('state=REQUESTED')
            && call[0].includes('page=1')
            && !call[0].includes('from=')
            && !call[0].includes('to=')
        )).toBe(true);
    });

    it('blocks invalid filter ranges and shows a toast instead of reloading', async () => {
        const wrapper = await mountHotelAdminBookingsPage();
        const updateButton = wrapper.findAll('button').find(button => button.text() === 'Aktualisieren');

        expect(updateButton).toBeDefined();

        apiGetMock.mockClear();
        await wrapper.get('[data-testid="hotel-booking-from-filter"]').setValue('2026-04-06T12:00');
        await wrapper.get('[data-testid="hotel-booking-to-filter"]').setValue('2026-04-06T10:00');
        await updateButton!.trigger('click');
        await flushPromises();

        expect(apiGetMock).not.toHaveBeenCalled();
        expect(toastAddMock).toHaveBeenCalledWith(expect.objectContaining({
            title: 'Ungültiger Zeitraum',
            color: 'red',
        }));
    });
});
