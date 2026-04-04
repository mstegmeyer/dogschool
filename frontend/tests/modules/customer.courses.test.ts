import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiDelMock,
    apiGetMock,
    apiPostMock,
    course,
    courseDate,
    installCustomerGlobals,
    mountCoursesPage,
    notification,
} from './customer-page-helpers';
import { flushPromises } from '../nuxt/page-test-utils';

describe('customer courses page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installCustomerGlobals();
        apiGetMock.mockImplementation((url: string) => {
            if (url === '/api/customer/courses') {
                return Promise.resolve({ items: [course] });
            }
            if (url === '/api/customer/courses/subscribed') {
                return Promise.resolve({ items: [] });
            }
            if (url === '/api/customer/courses/course-1/detail') {
                return Promise.resolve({
                    course,
                    upcomingDates: [courseDate],
                    notifications: [notification],
                });
            }
            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads courses, subscribes, unsubscribes, and opens the course detail modal', async () => {
        const wrapper = await mountCoursesPage();
        const section = wrapper.getComponent({ name: 'CustomerCourseGroupSection' });

        expect(section.props('variant')).toBe('available');
        expect((section.props('group') as { courses: unknown[] }).courses).toHaveLength(1);

        apiPostMock.mockResolvedValue({});
        await section.vm.$emit('subscribe', course);
        await flushPromises();
        expect(apiPostMock).toHaveBeenCalledWith('/api/customer/courses/course-1/subscribe');

        apiDelMock.mockResolvedValue({});
        await wrapper.getComponent({ name: 'CustomerCourseGroupSection' }).vm.$emit('unsubscribe', course);
        await flushPromises();
        expect(apiDelMock).toHaveBeenCalledWith('/api/customer/courses/course-1/subscribe');

        await wrapper.getComponent({ name: 'CustomerCourseGroupSection' }).vm.$emit('select', course);
        await flushPromises();
        expect(wrapper.getComponent({ name: 'CustomerCourseDetailModal' }).props('course')).toEqual(course);
    });
});
