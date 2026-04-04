import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { h } from 'vue';
import AppCalendarTimeline from '~/components/calendar/AppCalendarTimeline.vue';
import type { CourseDate } from '~/types';
import type { CalendarTimelineDay } from '~/composables/useCalendarTimeline';

function makeCourseDate(overrides: Partial<CourseDate> = {}): CourseDate {
    return {
        id: overrides.id ?? 'course-date-1',
        courseId: overrides.courseId ?? 'course-1',
        courseType: overrides.courseType ?? { code: 'AGI', name: 'Agility', recurrenceKind: 'RECURRING' },
        level: overrides.level ?? 1,
        date: overrides.date ?? '2026-03-30',
        dayOfWeek: overrides.dayOfWeek ?? 1,
        startTime: overrides.startTime ?? '10:00',
        endTime: overrides.endTime ?? '11:00',
        trainer: overrides.trainer ?? null,
        courseTrainer: overrides.courseTrainer,
        trainerOverridden: overrides.trainerOverridden,
        cancelled: overrides.cancelled ?? false,
        bookingCount: overrides.bookingCount ?? 0,
        createdAt: overrides.createdAt ?? '2026-03-29T12:00:00+01:00',
        booked: overrides.booked,
        bookings: overrides.bookings,
        subscriberCount: overrides.subscriberCount,
        subscribers: overrides.subscribers,
        subscribed: overrides.subscribed,
        bookingWindowClosed: overrides.bookingWindowClosed,
    };
}

function makeDay(courseDates: CourseDate[]): CalendarTimelineDay {
    return {
        date: '2026-03-30',
        label: 'Mo',
        dateShort: '30.03.',
        isToday: false,
        courseDates,
    };
}

describe('AppCalendarTimeline', () => {
    it('renders overlapping events side by side inside the same hour block', () => {
        const wrapper = mount(AppCalendarTimeline, {
            props: {
                days: [
                    makeDay([
                        makeCourseDate({ id: 'a', startTime: '10:00', endTime: '11:00' }),
                        makeCourseDate({ id: 'b', startTime: '10:15', endTime: '11:15' }),
                    ]),
                ],
                viewMode: 'day',
            },
            slots: {
                event: ({ courseDate }: { courseDate: CourseDate }) => h('span', courseDate.courseType?.name ?? 'Kurs'),
            },
        });

        const events = wrapper.findAll('[data-testid="calendar-event"]');

        expect(events).toHaveLength(2);
        expect(events[0].attributes('style')).toContain('left: 0%');
        expect(events[0].attributes('style')).toContain('width: 50%');
        expect(events[1].attributes('style')).toContain('left: 50%');
        expect(events[1].attributes('style')).toContain('width: 50%');
    });

    it('shows the empty label when a day has no course dates', () => {
        const wrapper = mount(AppCalendarTimeline, {
            props: {
                days: [makeDay([])],
                viewMode: 'day',
                emptyLabel: 'Keine Termine',
            },
            slots: {
                event: ({ courseDate }: { courseDate: CourseDate }) => h('span', courseDate.courseType?.name ?? 'Kurs'),
            },
        });

        expect(wrapper.text()).toContain('Keine Termine');
        expect(wrapper.findAll('[data-testid="calendar-event"]')).toHaveLength(0);
    });
});
