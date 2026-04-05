<template>
<div>
    <div class='mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between'>
        <div>
            <h1 class='text-2xl font-bold text-slate-800'>
                Raumbelegung
            </h1>
            <p class='mt-1 text-sm text-slate-500'>
                Stundenansicht je Raum für den ausgewählten Zeitraum.
            </p>
        </div>
        <div class='flex flex-col gap-3 sm:flex-row sm:items-end'>
            <div class='flex gap-2'>
                <UButton
                    size='sm'
                    variant='soft'
                    label='24h'
                    @click='applyPreset(24)'
                />
                <UButton
                    size='sm'
                    variant='soft'
                    label='3 Tage'
                    @click='applyPreset(72)'
                />
                <UButton
                    size='sm'
                    variant='soft'
                    label='7 Tage'
                    @click='applyPreset(168)'
                />
            </div>
            <UFormGroup label='Von'>
                <UInput v-model='fromValue' type='datetime-local' />
            </UFormGroup>
            <UFormGroup label='Bis'>
                <UInput v-model='toValue' type='datetime-local' />
            </UFormGroup>
            <UButton label='Aktualisieren' @click='loadOccupancy' />
        </div>
    </div>

    <UCard>
        <div v-if='loading' class='py-10 text-center text-sm text-slate-400'>
            Belegung wird geladen…
        </div>
        <div v-else-if='rooms.length === 0' class='py-10 text-center text-sm text-slate-400'>
            Keine Räume vorhanden.
        </div>
        <div v-else class='overflow-x-auto'>
            <div class='min-w-[72rem]'>
                <div class='grid border-b border-slate-200 bg-slate-50/80' :style='{ gridTemplateColumns: `16rem minmax(0, 1fr)` }'>
                    <div class='px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500'>
                        Raum
                    </div>
                    <div class='relative h-12' :style='{ width: timelineWidth }'>
                        <div
                            v-for='mark in hourMarks'
                            :key='mark.key'
                            class='absolute inset-y-0 border-l text-[11px]'
                            :class='mark.isNight ? "border-slate-100 text-slate-300" : "border-slate-200 text-slate-400"'
                            :style='{ left: mark.left }'
                        >
                            <span v-if='mark.showLabel' class='absolute left-2 top-2 whitespace-nowrap'>{{ mark.label }}</span>
                        </div>
                    </div>
                </div>

                <div
                    v-for='item in rooms'
                    :key='item.room.id'
                    :data-testid='`hotel-occupancy-room-${item.room.id}`'
                    class='grid border-b border-slate-100'
                    :style='{ gridTemplateColumns: `16rem minmax(0, 1fr)` }'
                >
                    <div class='border-r border-slate-100 px-4 py-4'>
                        <p class='font-medium text-slate-800'>
                            {{ item.room.name }}
                        </p>
                        <p class='text-sm text-slate-500'>
                            {{ formatSquareMeters(item.room.squareMeters) }} · Spitze {{ formatSquareMeters(item.peakRequiredSquareMeters) }}
                        </p>
                    </div>
                    <div class='relative h-16 bg-white' :style='{ width: timelineWidth }'>
                        <div
                            v-for='mark in hourMarks'
                            :key='`${item.room.id}-${mark.key}`'
                            class='absolute inset-y-0 border-l'
                            :class='mark.isNight ? "border-slate-50" : "border-slate-100"'
                            :style='{ left: mark.left }'
                        />
                        <div
                            v-for='segment in item.segments.filter(segment => segment.bookingCount > 0)'
                            :key='`${item.room.id}-${segment.startAt}-${segment.endAt}`'
                            class='absolute inset-y-2 overflow-hidden rounded-lg border px-2 py-1 text-[11px] leading-tight shadow-sm'
                            :class='segment.singleRoomActive
                                ? "border-amber-300 bg-amber-100/90 text-amber-950"
                                : "border-komm-200 bg-komm-100/85 text-komm-900"'
                            :style='segmentStyle(segment)'
                        >
                            <p class='font-medium'>
                                {{ formatSquareMeters(segment.usedSquareMeters) }}
                            </p>
                            <p class='truncate'>
                                {{ segment.dogNames.join(', ') }}<span v-if='segment.singleRoomActive'> · Einzelzimmer</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </UCard>
</div>
</template>

<script setup lang="ts">
import type { HotelOccupancyResponse, HotelOccupancyRoom, RoomOccupancySegment } from '~/types';

definePageMeta({ layout: 'admin' });

const api = useApi();
const { formatSquareMeters, futureDateTimeLocalValue } = useHelpers();

const rooms = ref<HotelOccupancyRoom[]>([]);
const loading = ref(true);
const fromValue = ref(futureDateTimeLocalValue(0, true));
const toValue = ref(futureDateTimeLocalValue(24, true));

const NIGHT_COMPRESSION_FACTOR = 0.2;

const weightedRangeMinutes = computed(() => Math.max(60, weightedMinutesBetween(new Date(fromValue.value), new Date(toValue.value))));
const timelineWidth = computed(() => `${Math.max(72, Math.ceil(weightedRangeMinutes.value / 60) * 5)}rem`);

const hourMarks = computed(() => {
    const marks: Array<{ key: string; label: string; left: string; isNight: boolean; showLabel: boolean }> = [];
    const start = new Date(fromValue.value);
    const end = new Date(toValue.value);
    const cursor = new Date(start);
    cursor.setMinutes(0, 0, 0);

    while (cursor <= end) {
        const isNight = isNightHour(cursor);
        marks.push({
            key: cursor.toISOString(),
            label: cursor.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' }),
            left: `${offsetPercent(start, cursor)}%`,
            isNight,
            showLabel: !isNight || cursor.getHours() === 0 || cursor.getHours() === 6 || cursor.getHours() === 22,
        });
        cursor.setHours(cursor.getHours() + 1);
    }

    return marks;
});

function applyPreset(hours: number): void {
    fromValue.value = futureDateTimeLocalValue(0, true);
    toValue.value = futureDateTimeLocalValue(hours, true);
    void loadOccupancy();
}

function isNightHour(date: Date): boolean {
    const hour = date.getHours();
    return hour < 6 || hour >= 22;
}

function nextHourBoundary(date: Date): Date {
    const boundary = new Date(date);
    boundary.setMinutes(0, 0, 0);
    boundary.setHours(boundary.getHours() + 1);

    return boundary;
}

function weightedMinutesBetween(start: Date, end: Date): number {
    if (end <= start) {
        return 0;
    }

    let total = 0;
    let cursor = new Date(start);

    while (cursor < end) {
        const boundary = nextHourBoundary(cursor);
        const segmentEnd = boundary < end ? boundary : end;
        const durationMinutes = (segmentEnd.getTime() - cursor.getTime()) / 60000;
        total += durationMinutes * (isNightHour(cursor) ? NIGHT_COMPRESSION_FACTOR : 1);
        cursor = segmentEnd;
    }

    return total;
}

function offsetPercent(rangeStart: Date, target: Date): number {
    const clampedTarget = target < rangeStart
        ? rangeStart
        : target;

    return Math.min(100, Math.max(0, (weightedMinutesBetween(rangeStart, clampedTarget) / weightedRangeMinutes.value) * 100));
}

function segmentStyle(segment: RoomOccupancySegment): Record<string, string> {
    const start = new Date(segment.startAt);
    const end = new Date(segment.endAt);
    const from = new Date(fromValue.value);
    const to = new Date(toValue.value);
    const clampedStart = start < from ? from : start > to ? to : start;
    const clampedEnd = end < clampedStart ? clampedStart : end > to ? to : end;
    const leftPercent = offsetPercent(from, clampedStart);
    const rawWidthPercent = (weightedMinutesBetween(clampedStart, clampedEnd) / weightedRangeMinutes.value) * 100;
    const widthPercent = rawWidthPercent <= 0
        ? 0
        : Math.min(100 - leftPercent, Math.max(0.75, rawWidthPercent));

    return {
        left: `${leftPercent}%`,
        width: `${widthPercent}%`,
    };
}

async function loadOccupancy(): Promise<void> {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            from: fromValue.value,
            to: toValue.value,
        });
        const response = await api.get<HotelOccupancyResponse>(`/api/admin/hotel/occupancy?${params.toString()}`);
        rooms.value = response.items;
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    void loadOccupancy();
});
</script>
