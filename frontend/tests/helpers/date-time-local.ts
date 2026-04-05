export function toDateTimeLocalStubValue(value: string | Date): string {
    const date = value instanceof Date
        ? value
        : new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toISOString().slice(0, 16);
}

export function futureDateTimeLocalStubValue(offsetHours: number, roundToHour = false): string {
    const date = new Date('2026-04-04T08:00:00Z');
    if (roundToHour) {
        date.setUTCMinutes(0, 0, 0);
    }
    date.setUTCHours(date.getUTCHours() + offsetHours);

    return toDateTimeLocalStubValue(date);
}
