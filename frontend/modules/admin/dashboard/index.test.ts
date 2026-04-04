import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    activeContract,
    apiGetMock,
    archivedCourse,
    course,
    installAdminGlobals,
    mountDashboardPage,
    pendingContract,
    todayCourseDate,
} from '~/tests/modules/admin-page-helpers';

describe('admin dashboard page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
        apiGetMock.mockImplementation((url: string) => {
            if (url === '/api/admin/contracts') {
                return Promise.resolve({ items: [activeContract, pendingContract] });
            }
            if (url === '/api/admin/courses') {
                return Promise.resolve({ items: [course, archivedCourse] });
            }
            if (url === '/api/admin/calendar?week=2026-03-30') {
                return Promise.resolve({
                    items: [
                        todayCourseDate,
                        { ...todayCourseDate, id: 'course-date-2', date: '2026-04-05', startTime: '12:00' },
                    ],
                });
            }

            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads dashboard data and computes stats, pending contracts, and today schedule', async () => {
        const wrapper = await mountDashboardPage();
        const statsGrid = wrapper.getComponent({ name: 'StatsGrid' });
        const pendingCard = wrapper.getComponent({ name: 'PendingContractsCard' });
        const scheduleCard = wrapper.getComponent({ name: 'TodayScheduleCard' });

        const stats = statsGrid.props('stats') as Array<{ label: string; value: string | number }>;
        expect(stats[0]).toMatchObject({ label: 'Aktive Kurse / Woche', value: 1 });
        expect(stats[1]).toMatchObject({ label: 'Aktive Verträge', value: 1 });
        expect(String(stats[2]?.value)).toContain('89');

        expect(pendingCard.props('count')).toBe(1);
        expect((pendingCard.props('contracts') as unknown[])).toHaveLength(1);
        expect((scheduleCard.props('courseDates') as unknown[])).toHaveLength(1);
    });
});
