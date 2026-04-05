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
                    <p class='mt-1 text-sm text-slate-500'>
                        {{ hotelPricingKindLabel(booking.pricingKind) }} · {{ booking.billableDays }} Kalendertag(e)
                    </p>
                </div>
                <UBadge :color='hotelBookingStateColor(booking.state)' variant='soft'>
                    {{ hotelBookingStateLabel(booking.state) }}
                </UBadge>
            </div>
            <div class='mt-4 space-y-3'>
                <PricingBreakdown
                    :snapshot='booking.pricingSnapshot'
                    title='Preisaufschlüsselung'
                    total-label='Gesamt'
                    :total-value='booking.totalPrice'
                />
                <div v-if='booking.customerComment' class='rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600'>
                    <span class='font-medium text-slate-700'>Dein Kommentar:</span>
                    {{ booking.customerComment }}
                </div>
                <div v-if='booking.adminComment' class='rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-800'>
                    <span class='font-medium'>Team-Hinweis:</span>
                    {{ booking.adminComment }}
                </div>
                <div v-if="booking.state === 'PENDING_CUSTOMER_APPROVAL'" class='flex flex-wrap gap-2'>
                    <UButton
                        color='green'
                        :loading='busyId === booking.id'
                        label='Preis akzeptieren'
                        @click="emit('accept', booking)"
                    />
                    <UButton
                        color='red'
                        variant='soft'
                        :loading='busyId === booking.id'
                        label='Ablehnen'
                        @click="emit('decline', booking)"
                    />
                    <UButton
                        variant='soft'
                        :loading='busyId === booking.id'
                        label='Kommentar anpassen'
                        @click="emit('resubmit', booking)"
                    />
                </div>
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
    busyId?: string | null,
}>();

const emit = defineEmits<{
    (event: 'accept', value: HotelBooking): void,
    (event: 'decline', value: HotelBooking): void,
    (event: 'resubmit', value: HotelBooking): void,
}>();

const { formatDateTime, hotelBookingStateColor, hotelBookingStateLabel, hotelPricingKindLabel } = useHelpers();
</script>
