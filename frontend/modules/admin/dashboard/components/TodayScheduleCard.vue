<template>
<UCard>
    <template #header>
        <div class='flex items-center justify-between'>
            <div class='flex items-center gap-2'>
                <h3 class='font-semibold text-slate-800'>
                    Heutige Termine
                </h3>
                <UBadge color='primary' variant='soft'>
                    {{ courseDates.length }}
                </UBadge>
            </div>
            <UButton variant='ghost' size='xs' to='/admin/calendar'>
                Zum Kalender
            </UButton>
        </div>
    </template>

    <AppSkeletonCollection
        v-if='loading'
        :show-desktop-table='false'
        :mobile-cards='4'
        :meta-columns='0'
        :content-lines='2'
    />
    <div v-else-if='courseDates.length === 0' class='py-4 text-center text-sm text-slate-400'>
        Keine Termine heute
    </div>
    <div v-else class='divide-y divide-slate-100'>
        <div
            v-for='courseDate in courseDates'
            :key='courseDate.id'
            class='flex items-center justify-between gap-3 py-3'
        >
            <div class='min-w-0'>
                <p
                    class='truncate text-sm font-medium'
                    :class="courseDate.cancelled ? 'text-red-600 line-through' : 'text-slate-700'"
                >
                    {{ courseDate.courseType?.name || 'Kurs' }}
                </p>
                <p class='text-xs text-slate-400'>
                    {{ courseDate.startTime }} – {{ courseDate.endTime }}
                </p>
            </div>
            <div class='flex shrink-0 items-center gap-3'>
                <UTooltip
                    v-if='courseDate.bookingCount'
                    :text="courseDate.bookings?.map(booking => `${booking.dogName} (${booking.customerName})`).join(', ') || ''"
                >
                    <div class='flex items-center gap-1 text-slate-500'>
                        <UIcon name='i-heroicons-user-group' class='h-4 w-4 text-slate-400' />
                        <span class='text-sm font-medium'>{{ courseDate.bookingCount }}</span>
                    </div>
                </UTooltip>
                <div v-else class='flex items-center gap-1 text-slate-500'>
                    <UIcon name='i-heroicons-user-group' class='h-4 w-4 text-slate-400' />
                    <span class='text-sm font-medium'>0</span>
                </div>
                <UBadge
                    v-if='courseDate.cancelled'
                    color='red'
                    variant='soft'
                    size='xs'
                >
                    Abgesagt
                </UBadge>
            </div>
        </div>
    </div>
</UCard>
</template>

<script setup lang="ts">
import type { CourseDate } from '~/types';

defineProps<{
    loading: boolean,
    courseDates: CourseDate[],
}>();
</script>
