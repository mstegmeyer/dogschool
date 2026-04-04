import { describe, expect, it } from 'vitest';
import type { CourseDate } from '~/types';
import { buildCalendarTimeline, parseTimeToMinutes, type CalendarTimelineDay } from './useCalendarTimeline';

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

describe('useCalendarTimeline', () => {
    it('parses HH:mm values into minutes', () => {
        expect(parseTimeToMinutes('09:30')).toBe(570);
        expect(parseTimeToMinutes('18:05')).toBe(1085);
    });

    it('places overlapping course dates next to each other', () => {
        const timeline = buildCalendarTimeline([
            makeDay([
                makeCourseDate({ id: 'a', startTime: '10:00', endTime: '11:00' }),
                makeCourseDate({ id: 'b', startTime: '10:30', endTime: '11:30' }),
                makeCourseDate({ id: 'c', startTime: '11:30', endTime: '12:30' }),
            ]),
        ], { viewMode: 'day' });

        const [first, second, third] = timeline.days[0].items;

        expect(first.left).toBe(0);
        expect(first.width).toBe(50);
        expect(first.columns).toBe(2);

        expect(second.left).toBe(50);
        expect(second.width).toBe(50);
        expect(second.columns).toBe(2);

        expect(third.left).toBe(0);
        expect(third.width).toBe(100);
        expect(third.columns).toBe(1);
        expect(timeline.days[0].maxColumns).toBe(2);
    });

    it('expands the hour range when events start earlier or end later than the defaults', () => {
        const timeline = buildCalendarTimeline([
            makeDay([
                makeCourseDate({ startTime: '07:15', endTime: '08:00' }),
                makeCourseDate({ id: 'late', startTime: '20:30', endTime: '21:15' }),
            ]),
        ]);

        expect(timeline.startMinute).toBe(7 * 60);
        expect(timeline.endMinute).toBe(22 * 60);
        expect(timeline.hourMarks[0].label).toBe('07:00');
        expect(timeline.hourMarks.at(-1)?.label).toBe('22:00');
    });

    it('uses a denser vertical scale in week view than in day view', () => {
        const dayView = buildCalendarTimeline([makeDay([makeCourseDate()])], { viewMode: 'day' });
        const weekView = buildCalendarTimeline([makeDay([makeCourseDate()])], { viewMode: 'week' });

        expect(dayView.timelineHeight).toBeGreaterThan(weekView.timelineHeight);
        expect(dayView.pixelsPerMinute).toBeGreaterThan(weekView.pixelsPerMinute);
    });
});
