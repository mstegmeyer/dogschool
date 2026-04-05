<template>
<div>
    <div class='mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between'>
        <div>
            <h1 class='text-2xl font-bold text-slate-800'>
                An- & Abreisen
            </h1>
            <p class='mt-1 text-sm text-slate-500'>
                Übersicht der nächsten Stunden oder Tage mit zugewiesenem Raum.
            </p>
        </div>
        <div class='flex flex-col gap-3 sm:flex-row sm:items-end'>
            <div class='flex gap-2'>
                <UButton
                    size='sm'
                    variant='soft'
                    label='24h'
                    @click='applyPreset(24)'
                />
                <UButton
                    size='sm'
                    variant='soft'
                    label='3 Tage'
                    @click='applyPreset(72)'
                />
                <UButton
                    size='sm'
                    variant='soft'
                    label='7 Tage'
                    @click='applyPreset(168)'
                />
            </div>
            <UFormGroup label='Von'>
                <UInput v-model='fromValue' type='datetime-local' />
            </UFormGroup>
            <UFormGroup label='Bis'>
                <UInput v-model='toValue' type='datetime-local' />
            </UFormGroup>
            <UButton label='Aktualisieren' @click='loadMovements' />
        </div>
    </div>

    <div class='grid gap-6 lg:grid-cols-2'>
        <UCard>
            <template #header>
                <h2 class='font-semibold text-slate-800'>
                    Anreisen
                </h2>
            </template>
            <div v-if='loading' class='py-10 text-center text-sm text-slate-400'>
                Lädt…
            </div>
            <div v-else-if='arrivals.length === 0' class='py-10 text-center text-sm text-slate-400'>
                Keine Anreisen im Zeitraum.
            </div>
            <div v-else class='space-y-3'>
                <div
                    v-for='booking in arrivals'
                    :key='`arrival-${booking.id}`'
                    :data-testid='`hotel-arrival-${booking.id}`'
                    class='rounded-xl border border-slate-200 bg-white p-4'
                >
                    <p class='font-medium text-slate-800'>
                        {{ booking.dogName || 'Hund' }} · {{ booking.customerName || 'Kunde' }}
                    </p>
                    <p class='text-sm text-slate-500'>
                        {{ formatDateTime(booking.startAt) }} · {{ booking.roomName || 'Kein Raum' }}
                    </p>
                </div>
            </div>
        </UCard>

        <UCard>
            <template #header>
                <h2 class='font-semibold text-slate-800'>
                    Abreisen
                </h2>
            </template>
            <div v-if='loading' class='py-10 text-center text-sm text-slate-400'>
                Lädt…
            </div>
            <div v-else-if='departures.length === 0' class='py-10 text-center text-sm text-slate-400'>
                Keine Abreisen im Zeitraum.
            </div>
            <div v-else class='space-y-3'>
                <div
                    v-for='booking in departures'
                    :key='`departure-${booking.id}`'
                    :data-testid='`hotel-departure-${booking.id}`'
                    class='rounded-xl border border-slate-200 bg-white p-4'
                >
                    <p class='font-medium text-slate-800'>
                        {{ booking.dogName || 'Hund' }} · {{ booking.customerName || 'Kunde' }}
                    </p>
                    <p class='text-sm text-slate-500'>
                        {{ formatDateTime(booking.endAt) }} · {{ booking.roomName || 'Kein Raum' }}
                    </p>
                </div>
            </div>
        </UCard>
    </div>
</div>
</template>

<script setup lang="ts">
import type { HotelBooking, HotelMovementsResponse } from '~/types';

definePageMeta({ layout: 'admin' });

const api = useApi();
const { formatDateTime, futureDateTimeLocalValue } = useHelpers();

const arrivals = ref<HotelBooking[]>([]);
const departures = ref<HotelBooking[]>([]);
const loading = ref(true);
const fromValue = ref(futureDateTimeLocalValue(0, true));
const toValue = ref(futureDateTimeLocalValue(24, true));

function applyPreset(hours: number): void {
    fromValue.value = futureDateTimeLocalValue(0, true);
    toValue.value = futureDateTimeLocalValue(hours, true);
    void loadMovements();
}

async function loadMovements(): Promise<void> {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            from: fromValue.value,
            to: toValue.value,
        });
        const response = await api.get<HotelMovementsResponse>(`/api/admin/hotel/movements?${params.toString()}`);
        arrivals.value = response.arrivals;
        departures.value = response.departures;
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    void loadMovements();
});
</script>
