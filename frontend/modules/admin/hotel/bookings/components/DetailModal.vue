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

            <PricingBreakdown
                :snapshot='booking.pricingSnapshot'
                title='Preisübersicht'
                total-label='Aktueller Gesamtpreis'
                :total-value='booking.totalPrice'
            />

            <div class='grid gap-3 sm:grid-cols-2'>
                <div class='rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600'>
                    <span class='font-medium text-slate-700'>Typ:</span>
                    {{ hotelPricingKindLabel(booking.pricingKind) }} · {{ booking.billableDays }} Kalendertag(e)
                </div>
                <div class='rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600'>
                    <span class='font-medium text-slate-700'>Reiseschutz:</span>
                    {{ booking.includesTravelProtection ? formatMoney(booking.travelProtectionPrice) : 'Nicht gewählt' }}
                </div>
            </div>

            <div v-if='booking.customerComment' class='rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600'>
                <span class='font-medium text-slate-700'>Kundenkommentar:</span>
                {{ booking.customerComment }}
            </div>

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

            <UFormGroup label='Finaler Gesamtpreis'>
                <UInput
                    :model-value='finalPrice'
                    type='number'
                    step='0.01'
                    @update:model-value="emit('update:finalPrice', String($event ?? ''))"
                />
                <template #hint>
                    Automatischer Vorschlag: {{ formatMoney(booking.quotedTotalPrice) }}
                </template>
            </UFormGroup>

            <UFormGroup label='Admin-Kommentar'>
                <UTextarea
                    :model-value='adminComment'
                    :rows='4'
                    placeholder='Begründung für Preisänderungen oder Hinweise für den Kunden.'
                    @update:model-value="emit('update:adminComment', String($event ?? ''))"
                />
            </UFormGroup>

            <div v-if='pricingConfig' class='rounded-xl border border-slate-200 bg-slate-50/70 p-4'>
                <p class='mb-2 text-sm font-semibold text-slate-800'>
                    Referenzpreise für manuelle Zusatzwünsche
                </p>
                <div class='grid gap-2 text-sm text-slate-600 sm:grid-cols-2'>
                    <p>Einzelzimmer HUTA: {{ formatMoney(pricingConfig.hotelSingleRoomDaycareDailyPrice) }} / Tag</p>
                    <p>Einzelzimmer Hotel: {{ formatMoney(pricingConfig.hotelSingleRoomHotelDailyPrice) }} / Tag</p>
                    <p>Läufigkeit: {{ formatMoney(pricingConfig.hotelHeatCycleDailyPrice) }} / Tag</p>
                    <p>Medikamentengabe: {{ formatMoney(pricingConfig.hotelMedicationPerAdministrationPrice) }} / Gabe</p>
                    <p>Futtermittelzusätze: {{ formatMoney(pricingConfig.hotelSupplementPerAdministrationPrice) }} / Gabe</p>
                </div>
            </div>

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
import type { HotelBooking, PricingConfig } from '~/types';

defineProps<{
    modelValue: boolean,
    booking: HotelBooking | null,
    selectedRoomId: string,
    roomOptions: Array<{ label: string; value: string }>,
    assigning: boolean,
    confirming: boolean,
    declining: boolean,
    finalPrice: string,
    adminComment: string,
    pricingConfig: PricingConfig | null,
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
    (event: 'update:selectedRoomId', value: string): void,
    (event: 'update:finalPrice', value: string): void,
    (event: 'update:adminComment', value: string): void,
    (event: 'assign-room'): void,
    (event: 'confirm'): void,
    (event: 'decline'): void,
    (event: 'cancel'): void,
}>();

const { formatDateTime, formatMoney, formatSquareMeters, hotelAreaRequirementForHeight, hotelBookingStateColor, hotelBookingStateLabel, hotelPricingKindLabel } = useHelpers();
</script>
