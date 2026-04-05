import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    apiPostMock,
    apiPutMock,
    archivedCourse,
    course,
    installAdminGlobals,
    mountCoursesPage,
    recurrenceCourseType,
    trainer,
} from '~/tests/modules/admin-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('admin courses page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
        apiGetMock.mockImplementation((url: string) => {
            if (url.startsWith('/api/admin/courses?')) {
                return Promise.resolve({
                    items: [course, archivedCourse],
                    pagination: { total: 2, pages: 1, page: 1, limit: 20 },
                });
            }

            if (url === '/api/admin/trainers') {
                return Promise.resolve({ items: [trainer] });
            }

            if (url === '/api/admin/course-types') {
                return Promise.resolve({ items: [recurrenceCourseType] });
            }

            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads courses, validates create, edits a course, archives, and unarchives', async () => {
        const wrapper = await mountCoursesPage();
        const table = wrapper.getComponent({ name: 'AdminCourseTable' });
        const modal = wrapper.getComponent({ name: 'AdminCourseFormModal' });

        expect(table.props('courses')).toHaveLength(2);
        expect(modal.props('trainerOptions')).toEqual([
            { label: 'Keine Zuordnung', value: '' },
            { label: 'Lea', value: 'trainer-1' },
        ]);
        expect(modal.props('courseTypeOptions')).toEqual([
            { label: 'Agility (AGI)', value: 'AGI' },
        ]);

        await wrapper.get('button').trigger('click');
        await modal.vm.$emit('submit');
        await flushPromises();
        expect(modal.props('formError')).toBe('Bitte prüfe die markierten Felder.');

        await table.vm.$emit('edit', course);
        await flushPromises();

        const form = modal.props('form') as Record<string, unknown>;
        form.dayOfWeek = 4;
        form.startTime = '12:00';
        form.endTime = '13:00';
        await flushPromises();

        expect(modal.props('showScheduleHint')).toBe(true);
        expect(modal.props('scheduleHintText')).toContain('Wochentag und die neue Uhrzeit');

        apiPutMock.mockResolvedValue({});
        await modal.vm.$emit('submit');
        await flushPromises();

        expect(apiPutMock).toHaveBeenCalledWith('/api/admin/courses/course-1', {
            typeCode: 'AGI',
            dayOfWeek: 4,
            startTime: '12:00',
            endTime: '13:00',
            level: 1,
            trainerId: 'trainer-1',
            comment: 'Bring treats',
        });

        apiPostMock.mockResolvedValueOnce({
            ...course,
            removeFromDate: '2026-04-08',
            removedCourseDates: 3,
            refundedBookings: 2,
        });
        await wrapper.getComponent({ name: 'AdminCourseTable' }).vm.$emit('toggle-archive', course);
        await flushPromises();

        const archiveModal = wrapper.getComponent({ name: 'AdminCourseArchiveModal' });
        await archiveModal.vm.$emit('update:remove-from-date', '');
        await flushPromises();
        await archiveModal.vm.$emit('confirm');
        await flushPromises();
        expect(wrapper.getComponent({ name: 'AdminCourseArchiveModal' }).props('error')).toBe('Bitte ein Datum auswählen.');

        await wrapper.getComponent({ name: 'AdminCourseArchiveModal' }).vm.$emit('update:remove-from-date', '2026-04-08');
        await flushPromises();
        await wrapper.getComponent({ name: 'AdminCourseArchiveModal' }).vm.$emit('confirm');
        await flushPromises();

        expect(apiPostMock).toHaveBeenCalledWith('/api/admin/courses/course-1/archive', {
            removeFromDate: '2026-04-08',
        });

        apiPostMock.mockResolvedValueOnce({});
        await wrapper.getComponent({ name: 'AdminCourseTable' }).vm.$emit('toggle-archive', archivedCourse);
        await flushPromises();
        expect(apiPostMock).toHaveBeenCalledWith('/api/admin/courses/course-archived/unarchive');
    });
});
