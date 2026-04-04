import type { CourseDate } from '~/types';

export interface CalendarTimelineDay {
    date: string,
    label: string,
    dateShort: string,
    isToday: boolean,
    courseDates: CourseDate[],
}

export interface CalendarTimelineItem {
    courseDate: CourseDate,
    startMinutes: number,
    endMinutes: number,
    top: number,
    height: number,
    left: number,
    width: number,
    column: number,
    columns: number,
}

export interface CalendarTimelineHourMark {
    label: string,
    minute: number,
    top: number,
}

export interface CalendarTimelineDayLayout extends CalendarTimelineDay {
    items: CalendarTimelineItem[],
    maxColumns: number,
}

export interface CalendarTimelineResult {
    days: CalendarTimelineDayLayout[],
    hourMarks: CalendarTimelineHourMark[],
    startMinute: number,
    endMinute: number,
    timelineHeight: number,
    pixelsPerMinute: number,
}

export interface CalendarTimelineOptions {
    viewMode?: 'day' | 'week',
    defaultStartHour?: number,
    defaultEndHour?: number,
    pixelsPerHourDay?: number,
    pixelsPerHourWeek?: number,
}

const DEFAULT_START_HOUR = 8;
const DEFAULT_END_HOUR = 20;
const DEFAULT_PIXELS_PER_HOUR_DAY = 108;
const DEFAULT_PIXELS_PER_HOUR_WEEK = 96;

interface CourseDateMetrics {
    courseDate: CourseDate,
    startMinutes: number,
    endMinutes: number,
}

function hourLabel(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    return `${String(hours).padStart(2, '0')}:00`;
}

export function parseTimeToMinutes(value: string): number {
    const [hours, minutes] = value.split(':').map(Number);
    return (Number.isFinite(hours) ? hours : 0) * 60 + (Number.isFinite(minutes) ? minutes : 0);
}

function normaliseCourseDate(cd: CourseDate): CourseDateMetrics {
    const startMinutes = parseTimeToMinutes(cd.startTime);
    const rawEndMinutes = parseTimeToMinutes(cd.endTime);
    const endMinutes = rawEndMinutes > startMinutes ? rawEndMinutes : startMinutes + 60;

    return {
        courseDate: cd,
        startMinutes,
        endMinutes,
    };
}

function buildDayLayout(courseDates: CourseDate[], startMinute: number, pixelsPerMinute: number): CalendarTimelineItem[] {
    const metrics = courseDates
        .map(normaliseCourseDate)
        .sort((a, b) => a.startMinutes - b.startMinutes || a.endMinutes - b.endMinutes);

    const items: CalendarTimelineItem[] = [];
    let cluster: CourseDateMetrics[] = [];
    let clusterEnd = -1;

    function flushCluster(): void {
        if (cluster.length === 0) {
            return;
        }

        const columnsEndMinutes: number[] = [];
        const clusterItems: CalendarTimelineItem[] = [];

        for (const metric of cluster) {
            let columnIndex = columnsEndMinutes.findIndex(endMinute => endMinute <= metric.startMinutes);

            if (columnIndex === -1) {
                columnIndex = columnsEndMinutes.length;
                columnsEndMinutes.push(metric.endMinutes);
            } else {
                columnsEndMinutes[columnIndex] = metric.endMinutes;
            }

            clusterItems.push({
                courseDate: metric.courseDate,
                startMinutes: metric.startMinutes,
                endMinutes: metric.endMinutes,
                top: (metric.startMinutes - startMinute) * pixelsPerMinute,
                height: (metric.endMinutes - metric.startMinutes) * pixelsPerMinute,
                left: 0,
                width: 0,
                column: columnIndex,
                columns: 0,
            });
        }

        const columnCount = Math.max(1, columnsEndMinutes.length);

        for (const item of clusterItems) {
            item.columns = columnCount;
            item.width = 100 / columnCount;
            item.left = item.column * item.width;
            items.push(item);
        }

        cluster = [];
        clusterEnd = -1;
    }

    for (const metric of metrics) {
        if (cluster.length === 0) {
            cluster = [metric];
            clusterEnd = metric.endMinutes;
            continue;
        }

        if (metric.startMinutes < clusterEnd) {
            cluster.push(metric);
            clusterEnd = Math.max(clusterEnd, metric.endMinutes);
            continue;
        }

        flushCluster();
        cluster = [metric];
        clusterEnd = metric.endMinutes;
    }

    flushCluster();

    return items;
}

export function buildCalendarTimeline(
    days: CalendarTimelineDay[],
    options: CalendarTimelineOptions = {},
): CalendarTimelineResult {
    const {
        viewMode = 'week',
        defaultStartHour = DEFAULT_START_HOUR,
        defaultEndHour = DEFAULT_END_HOUR,
        pixelsPerHourDay = DEFAULT_PIXELS_PER_HOUR_DAY,
        pixelsPerHourWeek = DEFAULT_PIXELS_PER_HOUR_WEEK,
    } = options;

    const allCourseDates = days.flatMap(day => day.courseDates.map(normaliseCourseDate));
    const earliestStart = allCourseDates.length > 0
        ? Math.min(...allCourseDates.map(cd => cd.startMinutes))
        : defaultStartHour * 60;
    const latestEnd = allCourseDates.length > 0
        ? Math.max(...allCourseDates.map(cd => cd.endMinutes))
        : defaultEndHour * 60;

    const startHour = Math.min(defaultStartHour, Math.floor(earliestStart / 60));
    const endHour = Math.max(defaultEndHour, Math.ceil(latestEnd / 60));
    const startMinute = startHour * 60;
    const endMinute = Math.max(startMinute + 60, endHour * 60);
    const pixelsPerMinute = (viewMode === 'day' ? pixelsPerHourDay : pixelsPerHourWeek) / 60;
    const timelineHeight = (endMinute - startMinute) * pixelsPerMinute;
    const hourMarks: CalendarTimelineHourMark[] = [];

    for (let minute = startMinute; minute <= endMinute; minute += 60) {
        hourMarks.push({
            label: hourLabel(minute),
            minute,
            top: (minute - startMinute) * pixelsPerMinute,
        });
    }

    const laidOutDays = days.map((day) => {
        const items = buildDayLayout(day.courseDates, startMinute, pixelsPerMinute);
        const maxColumns = Math.max(1, ...items.map(item => item.columns));

        return {
            ...day,
            items,
            maxColumns,
        };
    });

    return {
        days: laidOutDays,
        hourMarks,
        startMinute,
        endMinute,
        timelineHeight,
        pixelsPerMinute,
    };
}

export const useCalendarTimeline = () => ({
    buildCalendarTimeline,
    parseTimeToMinutes,
});
