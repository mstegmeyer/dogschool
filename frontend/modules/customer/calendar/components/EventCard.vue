<template>
<div class='flex h-full min-w-0 flex-col gap-1'>
    <div class='flex items-start justify-between gap-2'>
        <p class='min-w-0 font-semibold leading-4' :class="courseDate.cancelled ? 'text-red-600 line-through' : 'text-slate-700'">
            <span
                class='block truncate'
                :class="condensed ? 'text-[11px]' : 'text-[13px] sm:text-sm'"
                :title='formatCourseTitleWithLevel(courseDate.courseType?.name, courseDate.level)'
            >
                {{ formatCourseTitleWithLevel(courseDate.courseType?.name, courseDate.level) }}
            </span>
        </p>
        <UBadge
            v-if='courseDate.cancelled'
            color='red'
            variant='soft'
            size='xs'
        >
            Abgesagt
        </UBadge>
        <UBadge
            v-else-if='courseDate.booked'
            color='primary'
            variant='soft'
            size='xs'
        >
            Gebucht
        </UBadge>
    </div>

    <template v-if='condensed'>
        <div class='space-y-0.5 text-[10px] leading-4 text-slate-500'>
            <p>{{ courseDate.startTime }} – {{ courseDate.endTime }}</p>
            <p class='break-words'>
                {{ courseDate.trainer?.fullName || 'Trainer offen' }}
            </p>
        </div>
    </template>
    <template v-else>
        <p class='text-[11px] font-medium text-slate-500'>
            {{ courseDate.startTime }} – {{ courseDate.endTime }}
        </p>
        <p class='text-[11px] leading-4 text-slate-500'>
            Trainer: {{ courseDate.trainer?.fullName || 'Wird noch zugewiesen' }}
        </p>
    </template>

    <div v-if='courseDate.cancelled && !condensed' class='mt-auto text-[11px] font-medium text-red-600'>
        Dieser Termin findet nicht statt.
    </div>
    <div v-else-if='courseDate.booked' class='mt-auto space-y-1'>
        <p class='truncate text-[11px] font-medium text-slate-800'>
            <span class='font-normal text-slate-500'>für </span>{{ bookedDogLabel }}
        </p>
        <button
            v-if='!courseDate.bookingWindowClosed'
            type='button'
            class='inline-flex w-full items-center justify-center rounded-md border border-red-200 bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700 shadow-sm transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-200 focus:ring-offset-1'
            @click="emit('cancel-booking', courseDate)"
        >
            {{ condensed ? 'Storno' : 'Stornieren' }}
        </button>
    </div>
    <div v-else-if='courseDate.bookingWindowClosed' class='mt-auto'>
        <p v-if='!condensed' class='text-[11px] font-medium text-slate-400'>
            Buchung geschlossen
        </p>
    </div>
    <div v-else class='mt-auto space-y-1'>
        <p v-if='dogs.length === 0' class='text-[11px] text-slate-400'>
            Kein Hund verfügbar
        </p>
        <template v-else>
            <p v-if='!condensed' class='truncate text-[10px] font-medium text-slate-500'>
                {{ bookingSummaryLabel }}
            </p>
            <button
                type='button'
                class='inline-flex w-full items-center justify-center rounded-md bg-komm-700 px-2 py-1 text-[10px] font-semibold text-white shadow-sm transition hover:bg-komm-800 focus:outline-none focus:ring-2 focus:ring-komm-300 focus:ring-offset-1'
                :data-testid='`open-booking-${courseDate.id}`'
                @click="emit('open-booking', courseDate)"
            >
                {{ bookingTriggerLabel }}
            </button>
        </template>
    </div>
</div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { CourseDate, Dog } from '~/types';

const props = defineProps<{
    courseDate: CourseDate,
    condensed: boolean,
    dogs: Dog[],
}>();

const emit = defineEmits<{
    (event: 'open-booking', value: CourseDate): void,
    (event: 'cancel-booking', value: CourseDate): void,
}>();

const { formatCourseTitleWithLevel } = useHelpers();

const bookedDogLabel = computed(() => {
    const booking = props.courseDate.bookings?.[0];
    if (!booking) {
        return '–';
    }
    if (booking.dogName) {
        return booking.dogName;
    }

    const dog = props.dogs.find(candidate => candidate.id === booking.dogId);
    return dog?.name ?? '–';
});

const bookingSummaryLabel = computed(() => {
    if (props.dogs.length === 1) {
        return `für ${props.dogs[0]?.name ?? 'Hund'}`;
    }
    if (props.condensed) {
        return `${props.dogs.length} Hunde`;
    }
    return `${props.dogs.length} Hunde verfügbar`;
});

const bookingTriggerLabel = computed(() => (
    props.dogs.length > 1 && !props.condensed ? 'Hund wählen' : 'Buchen'
));
</script>
