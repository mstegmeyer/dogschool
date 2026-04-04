import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { computed } from 'vue';
import AppNotificationDetail from '~/components/notification/AppNotificationDetail.vue';
import { uiPageStubs } from '../nuxt/ui-test-stubs';

vi.stubGlobal('computed', computed);
vi.stubGlobal('useHelpers', () => ({
    formatDateTime: (value: string) => `formatted:${value}`,
    formatNotificationCourse: (course: { typeName: string | null; dayOfWeek: number; startTime: string }) =>
        `${course.typeName ?? 'Kurs'}-${course.dayOfWeek}-${course.startTime}`,
}));

const baseNotification = {
    id: 'notification-1',
    title: 'Wichtige Info',
    message: 'Bitte Leinen mitbringen.',
    authorName: 'Trainer Team',
    authorId: 'author-1',
    isGlobal: false,
    courses: [
        { id: 'course-1', typeCode: 'MH', typeName: 'Mensch-Hund', dayOfWeek: 1 as const, startTime: '10:00', endTime: '11:00' },
    ],
    courseIds: ['course-1'],
    pinnedUntil: null,
    isPinned: false,
    createdAt: '2026-03-27T09:00:00+01:00',
};

describe('AppNotificationDetail', () => {
    it('shows a pinned global notification with the fallback team author', () => {
        const wrapper = mount(AppNotificationDetail, {
            shallow: true,
            props: {
                notification: {
                    ...baseNotification,
                    authorName: null,
                    courses: [],
                    courseIds: [],
                    isGlobal: true,
                    isPinned: true,
                },
            },
            global: {
                stubs: uiPageStubs,
            },
        });

        const text = wrapper.text().replace(/\u00a0/g, ' ');

        expect(text).toContain('Wichtige Info');
        expect(text).toContain('Angepinnt');
        expect(text).toContain('Alle Kurse');
        expect(text).toContain('formatted:2026-03-27T09:00:00+01:00');
        expect(text).toContain('von Team');
    });

    it('summarizes course-targeted notifications and shows the hidden course count', () => {
        const wrapper = mount(AppNotificationDetail, {
            shallow: true,
            props: {
                notification: {
                    ...baseNotification,
                    courses: [
                        { id: 'course-1', typeCode: 'MH', typeName: 'Mensch-Hund', dayOfWeek: 1 as const, startTime: '10:00', endTime: '11:00' },
                        { id: 'course-2', typeCode: 'AG', typeName: 'Agility', dayOfWeek: 2 as const, startTime: '12:00', endTime: '13:00' },
                        { id: 'course-3', typeCode: 'OB', typeName: 'Obedience', dayOfWeek: 3 as const, startTime: '14:00', endTime: '15:00' },
                        { id: 'course-4', typeCode: 'TH', typeName: 'Therapiehund', dayOfWeek: 4 as const, startTime: '16:00', endTime: '17:00' },
                    ],
                    courseIds: ['course-1', 'course-2', 'course-3', 'course-4'],
                },
            },
            global: {
                stubs: uiPageStubs,
            },
        });

        const text = wrapper.text().replace(/\u00a0/g, ' ');

        expect(text).toContain('Kurs');
        expect(text).toContain('Mensch-Hund-1-10:00');
        expect(text).toContain('Agility-2-12:00');
        expect(text).toContain('Obedience-3-14:00');
        expect(text).toContain('(+ 1 weitere)');
        expect(text).not.toContain('Therapiehund-4-16:00');
    });

    it('respects a custom maxVisibleCourses limit for focused announcements', () => {
        const wrapper = mount(AppNotificationDetail, {
            shallow: true,
            props: {
                maxVisibleCourses: 1,
                notification: {
                    ...baseNotification,
                    courses: [
                        { id: 'course-1', typeCode: 'MH', typeName: 'Mensch-Hund', dayOfWeek: 1 as const, startTime: '10:00', endTime: '11:00' },
                        { id: 'course-2', typeCode: 'AG', typeName: 'Agility', dayOfWeek: 2 as const, startTime: '12:00', endTime: '13:00' },
                    ],
                    courseIds: ['course-1', 'course-2'],
                },
            },
            global: {
                stubs: uiPageStubs,
            },
        });

        const text = wrapper.text().replace(/\u00a0/g, ' ');

        expect(text).toContain('Mensch-Hund-1-10:00');
        expect(text).toContain('(+ 1 weitere)');
        expect(text).not.toContain('Agility-2-12:00');
        expect(text).toContain('von Trainer Team');
    });
});
