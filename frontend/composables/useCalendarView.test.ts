import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, nextTick, ref } from 'vue';
import type { CourseDate } from '~/types';
import type { UseCalendarViewResult } from './useCalendarView';

const TODAY_ISO = '2026-04-01';
const DAY_NAMES_SHORT = ['', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

function parseDateValue(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
}

function toIsoDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function addDaysToIso(iso: string, days: number): string {
    const date = parseDateValue(iso);
    date.setDate(date.getDate() + days);
    return toIsoDate(date);
}

function getIsoDayOfWeek(iso: string): number {
    const day = parseDateValue(iso).getDay();
    return day === 0 ? 7 : day;
}

function getWeekMonday(iso: string): string {
    const date = parseDateValue(iso);
    const day = date.getDay() === 0 ? 7 : date.getDay();
    date.setDate(date.getDate() - (day - 1));
    return toIsoDate(date);
}

function formatDateShort(iso: string): string {
    return parseDateValue(iso).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
}

function makeCourseDate(overrides: Partial<CourseDate> = {}): CourseDate {
    return {
        id: overrides.id ?? 'course-date-1',
        courseId: overrides.courseId ?? 'course-1',
        courseType: overrides.courseType ?? { code: 'AGI', name: 'Agility', recurrenceKind: 'RECURRING' },
        level: overrides.level ?? 1,
        date: overrides.date ?? TODAY_ISO,
        dayOfWeek: overrides.dayOfWeek ?? 3,
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

async function mountComposable(courseDates: CourseDate[], width: number): Promise<{
    result: UseCalendarViewResult,
    wrapper: ReturnType<typeof mount>,
}> {
    Object.defineProperty(window, 'innerWidth', {
        value: width,
        writable: true,
        configurable: true,
    });

    let result: UseCalendarViewResult | undefined;

    const { useCalendarView } = await import('./useCalendarView');
    const TestComponent = defineComponent({
        setup() {
            result = useCalendarView(ref(courseDates));
            return () => null;
        },
    });

    const wrapper = mount(TestComponent);
    await nextTick();

    return {
        result: result as UseCalendarViewResult,
        wrapper,
    };
}

describe('useCalendarView', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        vi.stubGlobal('useHelpers', () => ({
            addDaysToIso,
            dayNameShort: (dayOfWeek: number) => DAY_NAMES_SHORT[dayOfWeek] || '',
            formatDateShort,
            getIsoDayOfWeek,
            getWeekMonday,
            todayIso: () => TODAY_ISO,
        }));
    });

    it('builds a weekly desktop view with sorted course dates and week navigation', async () => {
        const addEventListenerSpy = vi.spyOn(window, 'addEventListener');
        const courseDates = [
            makeCourseDate({ id: 'late', date: '2026-03-31', dayOfWeek: 2, startTime: '14:00', endTime: '15:00' }),
            makeCourseDate({ id: 'early', date: '2026-03-31', dayOfWeek: 2, startTime: '09:00', endTime: '10:00' }),
            makeCourseDate({ id: 'friday', date: '2026-04-03', dayOfWeek: 5, startTime: '18:00', endTime: '19:00' }),
        ];

        const { result } = await mountComposable(courseDates, 1440);

        expect(addEventListenerSpy).toHaveBeenCalledWith('resize', expect.any(Function));
        expect(result.viewMode.value).toBe('week');
        expect(result.currentDay.value).toBe(TODAY_ISO);
        expect(result.currentMonday.value).toBe('2026-03-30');
        expect(result.weekStart.value).toBe('2026-03-30');
        expect(result.weekEnd.value).toBe('2026-04-05');
        expect(result.visibleDays.value).toHaveLength(7);
        expect(result.visibleDays.value[2]?.isToday).toBe(true);

        const tuesday = result.visibleDays.value.find(day => day.date === '2026-03-31');
        expect(tuesday?.courseDates.map(courseDate => courseDate.id)).toEqual(['early', 'late']);
        expect(tuesday?.label).toBe('Di');
        expect(tuesday?.dateShort).toBe('31.03.');

        result.prev();
        expect(result.currentMonday.value).toBe('2026-03-23');

        result.next();
        result.next();
        expect(result.currentMonday.value).toBe('2026-04-06');

        result.goToday();
        expect(result.currentDay.value).toBe(TODAY_ISO);
        expect(result.currentMonday.value).toBe('2026-03-30');
    });

    it('switches to a single-day mobile view, syncs monday on navigation, and removes the resize listener', async () => {
        const removeEventListenerSpy = vi.spyOn(window, 'removeEventListener');
        const courseDates = [
            makeCourseDate({ id: 'later', date: TODAY_ISO, startTime: '16:00', endTime: '17:00' }),
            makeCourseDate({ id: 'earlier', date: TODAY_ISO, startTime: '08:00', endTime: '09:00' }),
            makeCourseDate({ id: 'other-day', date: '2026-04-02', dayOfWeek: 4, startTime: '11:00', endTime: '12:00' }),
        ];

        const { result, wrapper } = await mountComposable(courseDates, 480);

        expect(result.viewMode.value).toBe('day');
        expect(result.visibleDays.value).toHaveLength(1);
        expect(result.visibleDays.value[0]?.date).toBe(TODAY_ISO);
        expect(result.visibleDays.value[0]?.courseDates.map(courseDate => courseDate.id)).toEqual(['earlier', 'later']);

        result.prev();
        expect(result.currentDay.value).toBe('2026-03-31');
        expect(result.currentMonday.value).toBe('2026-03-30');

        result.prev();
        result.prev();
        expect(result.currentDay.value).toBe('2026-03-29');
        expect(result.currentMonday.value).toBe('2026-03-23');

        result.next();
        expect(result.currentDay.value).toBe('2026-03-30');
        expect(result.currentMonday.value).toBe('2026-03-30');

        window.innerWidth = 1280;
        window.dispatchEvent(new Event('resize'));
        await nextTick();

        expect(result.viewMode.value).toBe('week');
        expect(result.visibleDays.value).toHaveLength(7);

        wrapper.unmount();
        expect(removeEventListenerSpy).toHaveBeenCalledWith('resize', expect.any(Function));
    });
});
