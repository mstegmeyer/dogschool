<template>
<div>
    <div class='mb-6 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between'>
        <div>
            <h1 class='text-2xl font-bold text-slate-800'>
                Hotelbuchungen
            </h1>
            <p class='mt-1 text-sm text-slate-500'>
                Eine Buchung kann erst nach erfolgreicher Raumzuweisung bestätigt werden.
            </p>
        </div>
        <div class='grid gap-3 md:grid-cols-2 xl:grid-cols-[12rem_1fr_1fr_auto_auto] xl:items-end'>
            <UFormGroup label='Status'>
                <USelectMenu
                    v-model='stateFilter'
                    data-testid='hotel-booking-state-filter'
                    :options='stateOptions'
                    value-attribute='value'
                    class='w-full xl:w-48'
                />
            </UFormGroup>
            <UFormGroup label='Von'>
                <UInput v-model='fromFilter' data-testid='hotel-booking-from-filter' type='datetime-local' />
            </UFormGroup>
            <UFormGroup label='Bis'>
                <UInput v-model='toFilter' data-testid='hotel-booking-to-filter' type='datetime-local' />
            </UFormGroup>
            <UButton label='Aktualisieren' @click='applyFilters' />
            <UButton variant='ghost' label='Zurücksetzen' @click='resetFilters' />
        </div>
    </div>

    <HotelBookingsTable
        :loading='loading'
        :bookings='bookings'
        :result-summary='resultSummary'
        :show-pagination='showPagination'
        :current-page='currentPage'
        :page-size='pageSize'
        :total-bookings='totalBookings'
        @open='openDetail'
        @update:current-page='currentPage = $event'
    />

    <HotelBookingDetailModal
        v-model='showDetail'
        :booking='selectedBooking'
        :selected-room-id='selectedRoomId'
        :room-options='roomOptions'
        :assigning='assigning'
        :confirming='confirming'
        :declining='declining'
        @update:selected-room-id='selectedRoomId = $event'
        @assign-room='assignRoom'
        @confirm='confirmBooking'
        @decline='declineBooking'
        @cancel='closeDetail'
    />
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, HotelBooking } from '~/types';
import HotelBookingDetailModal from './components/DetailModal.vue';
import HotelBookingsTable from './components/BookingsTable.vue';

definePageMeta({ layout: 'admin' });

const api = useApi();
const toast = useToast();

const bookings = ref<HotelBooking[]>([]);
const loading = ref(true);
const assigning = ref(false);
const confirming = ref(false);
const declining = ref(false);
const showDetail = ref(false);
const stateFilter = ref('REQUESTED');
const fromFilter = ref('');
const toFilter = ref('');
const selectedBooking = ref<HotelBooking | null>(null);
const selectedRoomId = ref('');
const currentPage = ref(1);
const totalBookings = ref(0);
const totalPages = ref(1);

const pageSize = 20;

const stateOptions = [
    { label: 'Angefragt', value: 'REQUESTED' },
    { label: 'Alle', value: 'all' },
    { label: 'Bestätigt', value: 'CONFIRMED' },
    { label: 'Abgelehnt', value: 'DECLINED' },
];

const roomOptions = computed(() => {
    if (!selectedBooking.value?.availableRooms) {
        return [];
    }

    return selectedBooking.value.availableRooms
        .filter(room => room.available || room.roomId === selectedBooking.value?.roomId)
        .map(room => ({
            label: `${room.roomName} · ${room.squareMeters} m²`,
            value: room.roomId,
        }));
});

const showPagination = computed(() => totalBookings.value > pageSize);
const pageStart = computed(() => (totalBookings.value === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1));
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, totalBookings.value));
const resultSummary = computed(() => {
    if (totalBookings.value === 0) {
        return 'Keine Hotelbuchungen für diesen Filter gefunden.';
    }

    return `${pageStart.value}–${pageEnd.value} von ${totalBookings.value} Hotelbuchungen`;
});

function hasValidFilterRange(): boolean {
    if (!fromFilter.value || !toFilter.value) {
        return true;
    }

    if (new Date(toFilter.value) <= new Date(fromFilter.value)) {
        toast.add({
            title: 'Ungültiger Zeitraum',
            description: 'Das Ende muss nach dem Beginn liegen.',
            color: 'red',
        });
        return false;
    }

    return true;
}

async function loadBookings(): Promise<void> {
    if (!hasValidFilterRange()) {
        return;
    }

    loading.value = true;
    try {
        const params = new URLSearchParams({
            state: stateFilter.value,
            page: `${currentPage.value}`,
            limit: `${pageSize}`,
            sort: 'startAt',
            direction: 'asc',
        });
        if (fromFilter.value) {
            params.set('from', fromFilter.value);
        }
        if (toFilter.value) {
            params.set('to', toFilter.value);
        }
        const response = await api.get<ApiListResponse<HotelBooking>>(`/api/admin/hotel/bookings?${params.toString()}`);
        bookings.value = response.items;
        totalBookings.value = response.pagination?.total ?? response.items.length;
        totalPages.value = response.pagination?.pages ?? 1;
        if (currentPage.value > totalPages.value) {
            currentPage.value = totalPages.value;
        }
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Die Hotelbuchungen konnten nicht geladen werden.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        loading.value = false;
    }
}

function applyFilters(): void {
    if (!hasValidFilterRange()) {
        return;
    }

    if (currentPage.value !== 1) {
        currentPage.value = 1;
        return;
    }

    void loadBookings();
}

function resetFilters(): void {
    stateFilter.value = 'REQUESTED';
    fromFilter.value = '';
    toFilter.value = '';

    if (currentPage.value !== 1) {
        currentPage.value = 1;
        return;
    }

    void loadBookings();
}

async function openDetail(booking: HotelBooking): Promise<void> {
    const detail = await api.get<HotelBooking>(`/api/admin/hotel/bookings/${booking.id}`);
    selectedBooking.value = detail;
    selectedRoomId.value = detail.roomId || '';
    showDetail.value = true;
}

function closeDetail(): void {
    showDetail.value = false;
    selectedBooking.value = null;
    selectedRoomId.value = '';
}

async function assignRoom(): Promise<void> {
    if (!selectedBooking.value || !selectedRoomId.value) {
        return;
    }

    assigning.value = true;
    try {
        selectedBooking.value = await api.put<HotelBooking>(`/api/admin/hotel/bookings/${selectedBooking.value.id}/room`, {
            roomId: selectedRoomId.value,
        });
        toast.add({ title: 'Raum zugewiesen', color: 'green' });
        await loadBookings();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Der Raum konnte nicht zugewiesen werden.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        assigning.value = false;
    }
}

async function confirmBooking(): Promise<void> {
    if (!selectedBooking.value) {
        return;
    }

    confirming.value = true;
    try {
        await api.post(`/api/admin/hotel/bookings/${selectedBooking.value.id}/confirm`);
        toast.add({ title: 'Hotelbuchung bestätigt', color: 'green' });
        closeDetail();
        await loadBookings();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Die Buchung konnte nicht bestätigt werden.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        confirming.value = false;
    }
}

async function declineBooking(): Promise<void> {
    if (!selectedBooking.value) {
        return;
    }

    declining.value = true;
    try {
        await api.post(`/api/admin/hotel/bookings/${selectedBooking.value.id}/decline`);
        toast.add({ title: 'Hotelbuchung abgelehnt', color: 'amber' });
        closeDetail();
        await loadBookings();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Die Buchung konnte nicht abgelehnt werden.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        declining.value = false;
    }
}

watch(currentPage, () => {
    void loadBookings();
});

watch(stateFilter, () => {
    applyFilters();
});

onMounted(() => {
    void loadBookings();
});
</script>
