<template>
<UModal :model-value='modelValue' @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid='hotel-booking-detail-modal'>
        <template #header>
            <div class='flex items-start justify-between gap-3'>
                <div>
                    <h3 class='font-semibold text-slate-800'>
                        {{ booking?.dogName || 'Hotelbuchung' }}
                    </h3>
                    <p v-if='booking' class='text-sm text-slate-500'>
                        {{ booking.customerName }} · {{ formatDateTime(booking.startAt) }} – {{ formatDateTime(booking.endAt) }}
                    </p>
                </div>
                <UBadge v-if='booking' :color='hotelBookingStateColor(booking.state)' variant='soft'>
                    {{ hotelBookingStateLabel(booking.state) }}
                </UBadge>
            </div>
        </template>

        <div v-if='booking' class='space-y-4'>
            <UAlert
                color='blue'
                variant='soft'
                icon='i-heroicons-information-circle'
                :title='`Aktueller Platzbedarf: ${booking.dogShoulderHeightCm ? hotelAreaRequirementForHeight(booking.dogShoulderHeightCm) : 0} m²`'
                :description='booking.roomName ? `Aktuell zugewiesen: ${booking.roomName}` : "Die Buchung braucht vor der Bestätigung eine Raumzuweisung."'
            />

            <UFormGroup label='Raum auswählen'>
                <USelectMenu
                    :model-value='selectedRoomId'
                    data-testid='hotel-booking-room-select'
                    :options='roomOptions'
                    value-attribute='value'
                    placeholder='Raum auswählen'
                    @update:model-value="emit('update:selectedRoomId', $event)"
                />
            </UFormGroup>

            <div v-if='booking.availableRooms?.length' class='space-y-3'>
                <div
                    v-for='room in booking.availableRooms'
                    :key='room.roomId'
                    class='rounded-xl border p-4'
                    :class="room.roomId === booking.roomId ? 'border-green-300 bg-green-50/60' : room.available ? 'border-slate-200 bg-white' : 'border-red-200 bg-red-50/60'"
                >
                    <div class='flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between'>
                        <div>
                            <p class='font-medium text-slate-800'>
                                {{ room.roomName }}
                            </p>
                            <p class='text-sm text-slate-500'>
                                {{ formatSquareMeters(room.squareMeters) }} · Spitze {{ formatSquareMeters(room.peakRequiredSquareMeters) }} · Rest {{ formatSquareMeters(room.remainingSquareMeters) }}
                            </p>
                        </div>
                        <UBadge :color='room.available ? "green" : "red"' variant='soft'>
                            {{ room.available ? 'Verfügbar' : 'Belegt' }}
                        </UBadge>
                    </div>
                    <div v-if='room.segments.some(segment => segment.bookingCount > 0)' class='mt-3 space-y-2'>
                        <div
                            v-for='segment in room.segments.filter(segment => segment.bookingCount > 0)'
                            :key='`${room.roomId}-${segment.startAt}-${segment.endAt}`'
                            class='rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600'
                        >
                            {{ formatDateTime(segment.startAt) }} – {{ formatDateTime(segment.endAt) }} ·
                            {{ formatSquareMeters(segment.usedSquareMeters) }} ·
                            {{ segment.dogNames.join(', ') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class='flex flex-wrap justify-end gap-2'>
                <UButton variant='ghost' label='Schließen' @click="emit('cancel')" />
                <UButton
                    data-testid='decline-hotel-booking'
                    label='Ablehnen'
                    color='red'
                    variant='soft'
                    :loading='declining'
                    @click="emit('decline')"
                />
                <UButton
                    data-testid='assign-hotel-booking-room'
                    label='Raum zuweisen'
                    color='primary'
                    variant='soft'
                    :disabled='!selectedRoomId'
                    :loading='assigning'
                    @click="emit('assign-room')"
                />
                <UButton
                    data-testid='confirm-hotel-booking'
                    label='Bestätigen'
                    color='green'
                    :disabled='!booking.roomId'
                    :loading='confirming'
                    @click="emit('confirm')"
                />
            </div>
        </div>
    </UCard>
</UModal>
</template>

<script setup lang="ts">
import type { HotelBooking } from '~/types';

defineProps<{
    modelValue: boolean,
    booking: HotelBooking | null,
    selectedRoomId: string,
    roomOptions: Array<{ label: string; value: string }>,
    assigning: boolean,
    confirming: boolean,
    declining: boolean,
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
    (event: 'update:selectedRoomId', value: string): void,
    (event: 'assign-room'): void,
    (event: 'confirm'): void,
    (event: 'decline'): void,
    (event: 'cancel'): void,
}>();

const { formatDateTime, formatSquareMeters, hotelAreaRequirementForHeight, hotelBookingStateColor, hotelBookingStateLabel } = useHelpers();
</script>
