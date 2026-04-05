<template>
<div class='flex h-full min-w-0 flex-col gap-2'>
    <div class='flex items-start gap-2'>
        <button
            type='button'
            class='min-w-0 flex-1 text-left font-semibold leading-4 underline-offset-2 transition hover:text-komm-700 hover:underline focus:outline-none focus:ring-2 focus:ring-komm-300 focus:ring-offset-1'
            :class="courseDate.cancelled ? 'text-red-600 line-through' : 'text-slate-700'"
            :data-testid='`open-course-date-details-${courseDate.id}`'
            @click="emit('open-details', courseDate)"
        >
            <span
                class='block truncate'
                :class="condensed ? 'text-[12px]' : 'text-sm sm:text-[15px]'"
                :title='formatCourseTitleWithLevel(courseDate.courseType?.name, courseDate.level)'
            >
                {{ formatCourseTitleWithLevel(courseDate.courseType?.name, courseDate.level) }}
            </span>
        </button>
        <UBadge
            v-if='statusBadge'
            class='shrink-0'
            :color='statusBadge.color'
            variant='soft'
            size='xs'
        >
            {{ statusBadge.label }}
        </UBadge>
    </div>

    <div class='mt-auto space-y-1'>
        <p
            v-if='courseDate.booked'
            class='truncate text-[12px] font-medium'
            :class="condensed ? 'text-komm-700' : 'text-slate-800'"
        >
            {{ bookedDogLabel }}
        </p>
        <button
            type='button'
            class='inline-flex w-full items-center justify-center rounded-md px-2 py-1 text-[11px] font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:cursor-not-allowed disabled:shadow-none'
            :class='actionButtonClass'
            :data-testid='actionTestId'
            :disabled='actionDisabled'
            @click='handleActionClick'
        >
            {{ actionLabel }}
        </button>
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
    (event: 'open-details', value: CourseDate): void,
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

const statusBadge = computed((): { color: string; label: string } | null => {
    if (props.courseDate.cancelled) {
        return { color: 'red', label: 'Abgesagt' };
    }
    if (props.courseDate.booked) {
        if (props.condensed) {
            return null;
        }
        return { color: 'primary', label: 'Gebucht' };
    }
    if (props.courseDate.bookingWindowClosed) {
        return { color: 'gray', label: 'Geschlossen' };
    }
    return null;
});

const bookingTriggerLabel = computed(() => (
    props.dogs.length > 1 && !props.condensed ? 'Hund wählen' : 'Buchen'
));

const canOpenBooking = computed(() => (
    props.dogs.length > 0
        && !props.courseDate.cancelled
        && !props.courseDate.booked
        && !props.courseDate.bookingWindowClosed
));

const canCancelBooking = computed(() => (
    props.courseDate.booked
        && !props.courseDate.bookingWindowClosed
));

const actionDisabled = computed(() => (
    !canOpenBooking.value && !canCancelBooking.value
));

const actionLabel = computed(() => {
    if (canCancelBooking.value) {
        return props.condensed ? 'Storno' : 'Stornieren';
    }
    if (canOpenBooking.value) {
        return bookingTriggerLabel.value;
    }
    if (props.courseDate.cancelled) {
        return 'Abgesagt';
    }
    if (props.courseDate.booked) {
        return 'Gebucht';
    }
    if (props.courseDate.bookingWindowClosed) {
        return 'Geschlossen';
    }
    return 'Kein Hund';
});

const actionButtonClass = computed(() => {
    if (canCancelBooking.value) {
        return 'border border-red-200 bg-white text-red-700 hover:bg-red-50 focus:ring-red-200';
    }
    if (canOpenBooking.value) {
        return 'bg-komm-700 text-white hover:bg-komm-800 focus:ring-komm-300';
    }
    return 'border border-slate-200 bg-slate-100 text-slate-400';
});

const actionTestId = computed(() => (
    canOpenBooking.value ? `open-booking-${props.courseDate.id}` : 'calendar-event-action'
));

function handleActionClick(): void {
    if (canCancelBooking.value) {
        emit('cancel-booking', props.courseDate);
        return;
    }

    if (canOpenBooking.value) {
        emit('open-booking', props.courseDate);
    }
}
</script>
