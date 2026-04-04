import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiDelMock,
    apiGetMock,
    apiPostMock,
    bookedCourseDate,
    courseDate,
    dog,
    installCustomerGlobals,
    mountCalendarPage,
} from './customer-page-helpers';
import { flushPromises } from '../nuxt/page-test-utils';

describe('customer calendar page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installCustomerGlobals();
        apiGetMock.mockImplementation((url: string) => {
            if (url === '/api/customer/calendar?week=2026-03-30') {
                return Promise.resolve({ items: [courseDate, bookedCourseDate] });
            }
            if (url === '/api/customer/dogs') {
                return Promise.resolve({ items: [dog] });
            }
            if (url === '/api/customer/calendar/subscription') {
                return Promise.resolve({ path: '/calendar/feed.ics' });
            }
            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads the calendar, books and cancels dates, and handles subscription actions', async () => {
        const wrapper = await mountCalendarPage();
        const bookingModal = wrapper.getComponent({ name: 'CustomerCalendarBookingModal' });
        const subscriptionModal = wrapper.getComponent({ name: 'CustomerCalendarSubscriptionModal' });
        const eventCards = wrapper.findAllComponents({ name: 'CustomerCalendarEventCard' });

        expect(eventCards).toHaveLength(2);
        expect(subscriptionModal.props('calendarSubscriptionUrl')).toBe('https://api.example.test/calendar/feed.ics');

        await eventCards[0]!.vm.$emit('open-booking', courseDate);
        await flushPromises();
        expect(bookingModal.props('courseDate')).toEqual(courseDate);
        expect(bookingModal.props('selectedDogId')).toBe('dog-1');

        apiPostMock.mockResolvedValue({});
        await bookingModal.vm.$emit('confirm');
        await flushPromises();
        expect(apiPostMock).toHaveBeenCalledWith('/api/customer/calendar/course-dates/course-date-1/book', { dogId: 'dog-1' });

        apiDelMock.mockResolvedValue({});
        await wrapper.findAllComponents({ name: 'CustomerCalendarEventCard' })[1]!.vm.$emit('cancel-booking', bookedCourseDate);
        await flushPromises();
        expect(apiDelMock).toHaveBeenCalledWith('/api/customer/calendar/course-dates/course-date-2/book?dogId=dog-1');

        await subscriptionModal.vm.$emit('copy');
        await flushPromises();
        expect(navigator.clipboard.writeText).toHaveBeenCalledWith('https://api.example.test/calendar/feed.ics');
    });
});
