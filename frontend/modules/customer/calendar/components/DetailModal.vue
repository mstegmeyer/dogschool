<template>
<UModal :model-value='modelValue' @update:model-value="emit('update:modelValue', $event)">
    <UCard v-if='courseDate' data-testid='customer-calendar-detail-modal'>
        <template #header>
            <div class='flex items-start justify-between gap-3'>
                <div class='min-w-0'>
                    <h2 class='text-lg font-semibold text-slate-800'>
                        {{ formatCourseTitleWithLevel(courseDate.courseType?.name, courseDate.level) }}
                    </h2>
                    <p class='mt-1 text-sm text-slate-500'>
                        {{ formatDate(courseDate.date) }}
                    </p>
                </div>
                <div class='flex items-center gap-2'>
                    <UBadge
                        v-if='statusBadge'
                        :color='statusBadge.color'
                        variant='soft'
                        size='sm'
                    >
                        {{ statusBadge.label }}
                    </UBadge>
                    <UButton
                        icon='i-heroicons-x-mark'
                        color='gray'
                        variant='ghost'
                        size='sm'
                        aria-label='Schließen'
                        @click="emit('update:modelValue', false)"
                    />
                </div>
            </div>
        </template>

        <div class='space-y-4 text-sm text-slate-600'>
            <div class='grid gap-3 sm:grid-cols-2'>
                <div class='rounded-lg border border-slate-200 bg-slate-50 px-4 py-3'>
                    <p class='text-xs font-semibold uppercase tracking-wide text-slate-500'>
                        Uhrzeit
                    </p>
                    <p class='mt-1 text-sm font-medium text-slate-900'>
                        {{ courseDate.startTime }} – {{ courseDate.endTime }}
                    </p>
                </div>

                <div class='rounded-lg border border-slate-200 bg-slate-50 px-4 py-3'>
                    <p class='text-xs font-semibold uppercase tracking-wide text-slate-500'>
                        Trainer
                    </p>
                    <p class='mt-1 text-sm font-medium text-slate-900'>
                        {{ courseDate.trainer?.fullName || 'Wird noch zugewiesen' }}
                    </p>
                </div>
            </div>

            <div class='rounded-lg border border-slate-200 bg-white px-4 py-3'>
                <p class='text-xs font-semibold uppercase tracking-wide text-slate-500'>
                    Buchung
                </p>
                <p class='mt-1 text-sm font-medium text-slate-900'>
                    {{ bookingStateLabel }}
                </p>
            </div>

            <div
                v-if='courseDate.comment'
                class='rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900'
            >
                <p class='text-xs font-semibold uppercase tracking-wide text-amber-700'>
                    Hinweis
                </p>
                <p class='mt-1 text-sm'>
                    {{ courseDate.comment }}
                </p>
            </div>
        </div>

        <template #footer>
            <div class='flex justify-end'>
                <UButton
                    label='Schließen'
                    color='gray'
                    variant='ghost'
                    @click="emit('update:modelValue', false)"
                />
            </div>
        </template>
    </UCard>
</UModal>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { CourseDate, Dog } from '~/types';

const props = defineProps<{
    modelValue: boolean,
    courseDate: CourseDate | null,
    dogs: Dog[],
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
}>();

const { formatCourseTitleWithLevel, formatDate } = useHelpers();

const bookedDogLabel = computed((): string => {
    const booking = props.courseDate?.bookings?.[0];
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
    if (!props.courseDate) {
        return null;
    }
    if (props.courseDate.cancelled) {
        return { color: 'red', label: 'Abgesagt' };
    }
    if (props.courseDate.booked) {
        return { color: 'primary', label: 'Gebucht' };
    }
    if (props.courseDate.bookingWindowClosed) {
        return { color: 'gray', label: 'Geschlossen' };
    }
    return null;
});

const bookingStateLabel = computed((): string => {
    if (!props.courseDate) {
        return '';
    }
    if (props.courseDate.cancelled) {
        return 'Dieser Termin findet nicht statt.';
    }
    if (props.courseDate.booked) {
        return bookedDogLabel.value !== '–' ? `Gebucht für ${bookedDogLabel.value}` : 'Bereits gebucht';
    }
    if (props.courseDate.bookingWindowClosed) {
        return 'Das Buchungsfenster ist bereits geschlossen.';
    }
    if (props.dogs.length === 0) {
        return 'Für die Buchung ist zuerst ein Hund erforderlich.';
    }
    return 'Buchung möglich';
});
</script>
