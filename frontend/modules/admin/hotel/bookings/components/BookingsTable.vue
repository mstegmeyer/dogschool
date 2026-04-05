<template>
<UCard>
    <AppSkeletonCollection
        v-if='loading'
        :mobile-cards='2'
        :desktop-rows='6'
        :desktop-columns='6'
        :meta-columns='2'
        :show-actions='true'
    />
    <div v-else-if='bookings.length === 0' class='py-10 text-center text-sm text-slate-400'>
        Keine Hotelbuchungen für diesen Filter gefunden.
    </div>
    <template v-else>
        <div class='overflow-x-auto'>
            <table class='min-w-[72rem] w-full border-separate border-spacing-0 text-sm'>
                <thead>
                    <tr class='text-left text-xs font-semibold uppercase tracking-wide text-slate-500'>
                        <th class='border-b border-slate-200 px-4 py-3'>
                            Hund / Kunde
                        </th>
                        <th class='border-b border-slate-200 px-4 py-3'>
                            Zeitraum
                        </th>
                        <th class='border-b border-slate-200 px-4 py-3'>
                            Raum
                        </th>
                        <th class='border-b border-slate-200 px-4 py-3'>
                            Status
                        </th>
                        <th class='border-b border-slate-200 px-4 py-3'>
                            Erstellt
                        </th>
                        <th class='border-b border-slate-200 px-4 py-3 text-right'>
                            Aktion
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for='booking in bookings'
                        :key='booking.id'
                        :data-testid='`hotel-booking-row-${booking.id}`'
                        class='align-top'
                    >
                        <td class='border-b border-slate-100 px-4 py-3'>
                            <p class='font-medium text-slate-800'>
                                {{ booking.dogName || 'Hund' }}
                            </p>
                            <p class='text-xs text-slate-500'>
                                {{ booking.customerName || 'Kunde' }}
                            </p>
                        </td>
                        <td class='border-b border-slate-100 px-4 py-3 text-slate-600'>
                            <p class='whitespace-nowrap'>
                                {{ formatDateTime(booking.startAt) }}
                            </p>
                            <p class='whitespace-nowrap text-xs text-slate-500'>
                                bis {{ formatDateTime(booking.endAt) }}
                            </p>
                        </td>
                        <td class='border-b border-slate-100 px-4 py-3 text-slate-600'>
                            {{ booking.roomName || 'Noch nicht zugewiesen' }}
                        </td>
                        <td class='border-b border-slate-100 px-4 py-3'>
                            <UBadge :color='hotelBookingStateColor(booking.state)' variant='soft' size='xs'>
                                {{ hotelBookingStateLabel(booking.state) }}
                            </UBadge>
                        </td>
                        <td class='border-b border-slate-100 px-4 py-3 text-slate-600'>
                            <span class='whitespace-nowrap'>{{ formatDateTime(booking.createdAt) }}</span>
                        </td>
                        <td class='border-b border-slate-100 px-4 py-3 text-right'>
                            <UButton
                                :data-testid='`open-hotel-booking-${booking.id}`'
                                size='xs'
                                variant='soft'
                                label='Prüfen'
                                @click="emit('open', booking)"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class='mt-6 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between'>
            <p>{{ resultSummary }}</p>
            <UPagination
                v-if='showPagination'
                :model-value='currentPage'
                :page-count='pageSize'
                :total='totalBookings'
                :show-first='true'
                :show-last='true'
                @update:model-value="emit('update:currentPage', $event)"
            />
        </div>
    </template>
</UCard>
</template>

<script setup lang="ts">
import type { HotelBooking } from '~/types';

defineProps<{
    loading: boolean,
    bookings: HotelBooking[],
    resultSummary: string,
    showPagination: boolean,
    currentPage: number,
    pageSize: number,
    totalBookings: number,
}>();

const emit = defineEmits<{
    (event: 'open', value: HotelBooking): void,
    (event: 'update:currentPage', value: number): void,
}>();

const { formatDateTime, hotelBookingStateColor, hotelBookingStateLabel } = useHelpers();
</script>
