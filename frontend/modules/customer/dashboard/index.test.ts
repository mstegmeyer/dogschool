import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    apiPostMock,
    bookedCourseDate,
    course,
    courseDate,
    dog,
    fetchProfileMock,
    installCustomerGlobals,
    mountDashboardPage,
    notification,
    userRef,
} from '~/tests/modules/customer-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('customer dashboard page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installCustomerGlobals();
        userRef.value = { id: 'customer-1', name: 'Max' };
        fetchProfileMock.mockResolvedValue(undefined);
        apiGetMock.mockImplementation((url: string) => {
            if (url === '/api/customer/credits') {
                return Promise.resolve({ balance: 3 });
            }
            if (url === '/api/customer/dogs') {
                return Promise.resolve({ items: [dog] });
            }
            if (url === '/api/customer/courses/subscribed') {
                return Promise.resolve({ items: [course] });
            }
            if (url === '/api/customer/calendar?from=2026-04-04&days=14') {
                return Promise.resolve({ items: [courseDate, bookedCourseDate] });
            }
            if (url === '/api/customer/notifications') {
                return Promise.resolve({ items: [notification] });
            }
            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads dashboard data, books a date, and opens notification details', async () => {
        const wrapper = await mountDashboardPage();
        const datesCard = wrapper.getComponent({ name: 'UpcomingDatesCard' });
        const notificationsCard = wrapper.getComponent({ name: 'NotificationsCard' });
        const detailModal = wrapper.getComponent({ name: 'NotificationDetailModal' });

        expect(datesCard.props('upcomingDates')).toHaveLength(2);
        expect(wrapper.getComponent({ name: 'OverviewStats' }).props('creditBalance')).toBe(3);

        apiPostMock.mockResolvedValue({ creditBalance: 2 });
        await datesCard.vm.$emit('book', courseDate);
        await flushPromises();
        expect(apiPostMock).toHaveBeenCalledWith('/api/customer/calendar/course-dates/course-date-1/book', { dogId: 'dog-1' });

        await notificationsCard.vm.$emit('select', notification);
        await flushPromises();
        expect(detailModal.props('notification')).toEqual(notification);

        await detailModal.vm.$emit('update:modelValue', false);
        await flushPromises();
        expect(detailModal.props('notification')).toBeNull();
    });
});
