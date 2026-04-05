<template>
<UCard>
    <AppSkeletonCollection
        v-if='loading'
        :mobile-cards='4'
        :desktop-rows='4'
        :desktop-columns='5'
        :meta-columns='2'
        :show-actions='false'
    />
    <div v-else-if='bookings.length === 0' class='py-10 text-center text-sm text-slate-400'>
        Noch keine Hotelbuchungen vorhanden.
    </div>
    <div v-else class='space-y-3'>
        <div
            v-for='booking in bookings'
            :key='booking.id'
            :data-testid='`hotel-booking-card-${booking.id}`'
            class='rounded-xl border border-slate-200 bg-white p-4'
        >
            <div class='flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between'>
                <div class='min-w-0'>
                    <p class='font-medium text-slate-800'>
                        {{ booking.dogName || 'Hund' }}
                    </p>
                    <p class='text-sm text-slate-500'>
                        {{ formatDateTime(booking.startAt) }} – {{ formatDateTime(booking.endAt) }}
                    </p>
                    <p class='mt-1 text-sm text-slate-500'>
                        Raum: {{ booking.roomName || 'Noch nicht zugewiesen' }}
                    </p>
                </div>
                <UBadge :color='hotelBookingStateColor(booking.state)' variant='soft'>
                    {{ hotelBookingStateLabel(booking.state) }}
                </UBadge>
            </div>
        </div>
    </div>
</UCard>
</template>

<script setup lang="ts">
import type { HotelBooking } from '~/types';

defineProps<{
    loading: boolean,
    bookings: HotelBooking[],
}>();

const { formatDateTime, hotelBookingStateColor, hotelBookingStateLabel } = useHelpers();
</script>
