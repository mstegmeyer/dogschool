<template>
<UCard class='xl:min-h-[32rem]'>
    <template #header>
        <div class='flex flex-wrap items-start justify-between gap-3'>
            <div>
                <h3 class='font-semibold text-slate-800'>
                    Nächste Termine
                </h3>
                <p class='mt-1 text-xs text-slate-400'>
                    Deine kommenden Buchungen und offenen Termine der nächsten 14 Tage.
                </p>
            </div>
            <UButton variant='ghost' size='xs' to='/customer/calendar'>
                Kalender
            </UButton>
        </div>
    </template>

    <AppSkeletonCollection
        v-if='loading'
        :show-desktop-table='false'
        :mobile-cards='5'
        :meta-columns='0'
        :content-lines='2'
        :show-badge='true'
    />
    <div v-else-if='upcomingDates.length === 0' class='py-4 text-center text-sm text-slate-400'>
        Keine anstehenden Termine
    </div>
    <div v-else class='divide-y divide-slate-100'>
        <div v-for='courseDate in upcomingDates' :key='courseDate.id' class='py-3'>
            <div class='flex items-center justify-between gap-2'>
                <div class='min-w-0'>
                    <p class='text-sm font-medium text-slate-700'>
                        {{ courseDate.courseType?.name || 'Kurs' }}
                    </p>
                    <p class='text-xs text-slate-400'>
                        {{ dayName(courseDate.dayOfWeek) }}, {{ formatDate(courseDate.date) }} · {{ courseDate.startTime }} – {{ courseDate.endTime }}
                    </p>
                    <p v-if='courseDate.comment' class='mt-1 text-xs font-medium text-amber-700'>
                        {{ courseDate.comment }}
                    </p>
                </div>
                <div v-if='courseDate.cancelled' class='shrink-0'>
                    <UBadge color='red' variant='soft' size='xs'>
                        Abgesagt
                    </UBadge>
                </div>
                <UBadge
                    v-else-if='courseDate.booked'
                    color='primary'
                    variant='soft'
                    size='xs'
                    class='shrink-0 max-w-[11rem] whitespace-normal text-center leading-tight'
                >
                    Gebucht für {{ bookedDogLabel(courseDate) }}
                </UBadge>
                <div v-else-if='courseDate.subscribed && !courseDate.bookingWindowClosed' class='flex shrink-0 items-center gap-1.5'>
                    <USelectMenu
                        v-if='dogs.length > 1'
                        :data-testid='`dashboard-dog-select-${courseDate.id}`'
                        :model-value='dogIdByCourseDate[courseDate.id]'
                        :options='dogOptions'
                        value-attribute='value'
                        placeholder='Hund …'
                        size='xs'
                        class='w-24'
                        @update:model-value="emit('update:dog-id', { courseDateId: courseDate.id, dogId: $event })"
                    />
                    <UButton
                        :data-testid='`dashboard-book-${courseDate.id}`'
                        size='xs'
                        label='Buchen'
                        :disabled='!dogIdForBooking(courseDate)'
                        :loading='bookingInProgress === courseDate.id'
                        @click="emit('book', courseDate)"
                    />
                </div>
            </div>
        </div>
    </div>
</UCard>
</template>

<script setup lang="ts">
import type { CourseDate, Dog } from '~/types';

const props = defineProps<{
    loading: boolean,
    upcomingDates: CourseDate[],
    dogs: Dog[],
    dogOptions: Array<{ label: string; value: string }>,
    dogIdByCourseDate: Record<string, string>,
    bookingInProgress: string | null,
}>();

const emit = defineEmits<{
    (event: 'update:dog-id', value: { courseDateId: string; dogId: string }): void,
    (event: 'book', value: CourseDate): void,
}>();

const { dayName, formatDate } = useHelpers();

function dogIdForBooking(courseDate: CourseDate): string {
    if (props.dogs.length === 0) {
        return '';
    }
    if (props.dogs.length === 1) {
        return props.dogs[0]?.id ?? '';
    }
    return props.dogIdByCourseDate[courseDate.id] || '';
}

function bookedDogLabel(courseDate: CourseDate): string {
    const booking = courseDate.bookings?.[0];
    if (!booking) {
        return '–';
    }
    if (booking.dogName) {
        return booking.dogName;
    }

    const dog = props.dogs.find(candidate => candidate.id === booking.dogId);
    return dog?.name ?? '–';
}
</script>
