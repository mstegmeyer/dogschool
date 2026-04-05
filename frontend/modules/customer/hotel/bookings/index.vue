<template>
<div>
    <div class='mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'>
        <div>
            <h1 class='text-2xl font-bold text-slate-800'>
                Hotelbuchungen
            </h1>
            <p class='mt-1 text-sm text-slate-500'>
                Anfragen werden erst nach Raumzuweisung und Bestätigung durch das Team verbindlich.
            </p>
        </div>
        <UButton
            data-testid='open-hotel-booking-request'
            icon='i-heroicons-plus'
            label='Buchung anfragen'
            @click='showRequest = true'
        />
    </div>

    <HotelBookingsList
        :loading='loading'
        :bookings='bookings'
        :busy-id='reviewActionId'
        @accept='acceptPrice'
        @decline='declinePrice'
        @resubmit='openResubmitModal'
    />

    <HotelBookingRequestModal
        v-model='showRequest'
        :dog-options='dogOptions'
        :selected-dog-name='selectedDog?.name || ""'
        :stored-shoulder-height-cm='selectedDog?.shoulderHeightCm || 0'
        :form='requestForm'
        :field-errors='fieldErrors'
        :form-error='formError'
        :saving='saving'
        :preview-loading='previewLoading'
        :preview='quotePreview'
        @submit='requestBooking'
        @cancel='closeRequestModal'
        @clear-field-error='clearFieldError'
    />

    <UModal :model-value='showResubmit' @update:model-value='showResubmit = $event'>
        <UCard v-if='bookingToResubmit' data-testid='hotel-booking-resubmit-modal'>
            <template #header>
                <h3 class='font-semibold text-slate-800'>
                    Kommentar anpassen
                </h3>
            </template>
            <div class='space-y-4'>
                <PricingBreakdown
                    :snapshot='bookingToResubmit.pricingSnapshot'
                    title='Aktuelle Preisübersicht'
                    total-label='Gesamt'
                    :total-value='bookingToResubmit.totalPrice'
                />
                <UFormGroup label='Neuer Kommentar'>
                    <UTextarea
                        v-model='resubmitForm.customerComment'
                        :rows='4'
                        placeholder='Beschreibe hier deine Änderungswünsche für das Team.'
                    />
                </UFormGroup>
                <div class='flex justify-end gap-2'>
                    <UButton variant='ghost' label='Abbrechen' @click='closeResubmitModal' />
                    <UButton :loading='resubmitting' label='Erneut einreichen' @click='submitResubmission' />
                </div>
            </div>
        </UCard>
    </UModal>
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Dog, HotelBooking, HotelBookingQuotePreview } from '~/types';
import HotelBookingsList from './components/BookingsList.vue';
import HotelBookingRequestModal from './components/RequestModal.vue';

definePageMeta({ layout: 'customer' });

const api = useApi();
const toast = useToast();
const { toDateTimeLocalValue } = useHelpers();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError } = useFormFeedback();

const bookings = ref<HotelBooking[]>([]);
const dogs = ref<Dog[]>([]);
const loading = ref(true);
const saving = ref(false);
const showRequest = ref(false);
const previewLoading = ref(false);
const quotePreview = ref<HotelBookingQuotePreview | null>(null);
const reviewActionId = ref<string | null>(null);
const showResubmit = ref(false);
const bookingToResubmit = ref<HotelBooking | null>(null);
const resubmitting = ref(false);

const requestForm = reactive({
    dogId: '',
    startAt: defaultBookingDateTime(1, 8),
    endAt: defaultBookingDateTime(1, 18),
    currentShoulderHeightCm: 0,
    includesTravelProtection: false,
    customerComment: '',
});
const resubmitForm = reactive({ customerComment: '' });

const selectedDog = computed(() => dogs.value.find(dog => dog.id === requestForm.dogId) || null);
const dogOptions = computed(() => dogs.value.map(dog => ({ label: dog.name, value: dog.id })));
let previewTimer: ReturnType<typeof setTimeout> | null = null;

watch(() => requestForm.dogId, () => {
    if (selectedDog.value) {
        requestForm.currentShoulderHeightCm = selectedDog.value.shoulderHeightCm;
    } else {
        requestForm.currentShoulderHeightCm = 0;
    }
});

function defaultBookingDateTime(dayOffset: number, hour: number): string {
    const date = new Date();
    date.setDate(date.getDate() + dayOffset);
    date.setHours(hour, 0, 0, 0);

    return toDateTimeLocalValue(date);
}

function closeRequestModal(): void {
    showRequest.value = false;
    clearFormErrors();
    previewLoading.value = false;
    quotePreview.value = null;
}

function resetRequestForm(): void {
    requestForm.dogId = '';
    requestForm.startAt = defaultBookingDateTime(1, 8);
    requestForm.endAt = defaultBookingDateTime(1, 18);
    requestForm.currentShoulderHeightCm = 0;
    requestForm.includesTravelProtection = false;
    requestForm.customerComment = '';
}

function openResubmitModal(booking: HotelBooking): void {
    bookingToResubmit.value = booking;
    resubmitForm.customerComment = booking.customerComment || '';
    showResubmit.value = true;
}

function closeResubmitModal(): void {
    showResubmit.value = false;
    bookingToResubmit.value = null;
    resubmitForm.customerComment = '';
}

function canPreviewRequest(): boolean {
    return !!requestForm.dogId
        && !!requestForm.startAt
        && !!requestForm.endAt
        && new Date(requestForm.endAt) > new Date(requestForm.startAt);
}

function schedulePreview(): void {
    if (previewTimer !== null) {
        clearTimeout(previewTimer);
    }

    if (!showRequest.value || !canPreviewRequest()) {
        previewLoading.value = false;
        quotePreview.value = null;
        return;
    }

    previewLoading.value = true;
    previewTimer = window.setTimeout(() => {
        void loadPreview();
    }, 250);
}

async function loadPreview(): Promise<void> {
    try {
        quotePreview.value = await api.post<HotelBookingQuotePreview>('/api/customer/hotel-bookings/preview', {
            dogId: requestForm.dogId,
            startAt: requestForm.startAt,
            endAt: requestForm.endAt,
            currentShoulderHeightCm: requestForm.currentShoulderHeightCm || null,
            includesTravelProtection: requestForm.includesTravelProtection,
            customerComment: requestForm.customerComment || null,
        });
    } catch {
        quotePreview.value = null;
    } finally {
        previewLoading.value = false;
    }
}

function validateForm(): boolean {
    clearFormErrors();

    if (!requestForm.dogId) {
        setFieldError('dogId', 'Bitte einen Hund auswählen.');
    }
    if (selectedDog.value && (!requestForm.currentShoulderHeightCm || requestForm.currentShoulderHeightCm <= 0)) {
        setFieldError('currentShoulderHeightCm', 'Bitte eine aktuelle Schulterhöhe angeben.');
    }
    if (!requestForm.startAt) {
        setFieldError('startAt', 'Bitte einen Beginn auswählen.');
    }
    if (!requestForm.endAt) {
        setFieldError('endAt', 'Bitte ein Ende auswählen.');
    }

    if (requestForm.startAt) {
        const start = new Date(requestForm.startAt);
        const startMinutes = (start.getHours() * 60) + start.getMinutes();
        if (startMinutes < 360 || startMinutes > 1320) {
            setFieldError('startAt', 'Der Beginn muss zwischen 06:00 und 22:00 Uhr liegen.');
        }
    }

    if (requestForm.endAt) {
        const end = new Date(requestForm.endAt);
        const endMinutes = (end.getHours() * 60) + end.getMinutes();
        if (endMinutes < 360 || endMinutes > 1320) {
            setFieldError('endAt', 'Das Ende muss zwischen 06:00 und 22:00 Uhr liegen.');
        }
    }

    if (requestForm.startAt && requestForm.endAt && new Date(requestForm.endAt) <= new Date(requestForm.startAt)) {
        setFieldError('endAt', 'Das Ende muss nach dem Beginn liegen.');
    }

    if (Object.keys(fieldErrors.value).length > 0) {
        setFormError('Bitte prüfe die markierten Felder.');
        return false;
    }

    return true;
}

async function loadBookings(): Promise<void> {
    loading.value = true;
    try {
        const response = await api.get<ApiListResponse<HotelBooking>>('/api/customer/hotel-bookings');
        bookings.value = response.items;
    } finally {
        loading.value = false;
    }
}

async function loadDogs(): Promise<void> {
    const response = await api.get<ApiListResponse<Dog>>('/api/customer/dogs');
    dogs.value = response.items;
}

async function requestBooking(): Promise<void> {
    if (!validateForm()) {
        return;
    }

    saving.value = true;
    try {
        await api.post('/api/customer/hotel-bookings', {
            dogId: requestForm.dogId,
            startAt: requestForm.startAt,
            endAt: requestForm.endAt,
            currentShoulderHeightCm: requestForm.currentShoulderHeightCm,
            includesTravelProtection: requestForm.includesTravelProtection,
            customerComment: requestForm.customerComment || null,
        });
        toast.add({ title: 'Hotelbuchung angefragt', color: 'green' });
        closeRequestModal();
        resetRequestForm();
        await Promise.all([loadBookings(), loadDogs()]);
    } catch (cause) {
        applyApiError(cause, 'Die Hotelbuchung konnte nicht angefragt werden.');
    } finally {
        saving.value = false;
    }
}

async function acceptPrice(booking: HotelBooking): Promise<void> {
    reviewActionId.value = booking.id;
    try {
        await api.post(`/api/customer/hotel-bookings/${booking.id}/accept-price`);
        toast.add({ title: 'Preis akzeptiert', color: 'green' });
        await loadBookings();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Der Preis konnte nicht bestätigt werden.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        reviewActionId.value = null;
    }
}

async function declinePrice(booking: HotelBooking): Promise<void> {
    reviewActionId.value = booking.id;
    try {
        await api.post(`/api/customer/hotel-bookings/${booking.id}/decline-price`);
        toast.add({ title: 'Hotelbuchung abgelehnt', color: 'amber' });
        await loadBookings();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Der Preis konnte nicht abgelehnt werden.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        reviewActionId.value = null;
    }
}

async function submitResubmission(): Promise<void> {
    if (!bookingToResubmit.value) {
        return;
    }

    resubmitting.value = true;
    try {
        await api.post(`/api/customer/hotel-bookings/${bookingToResubmit.value.id}/resubmit`, {
            customerComment: resubmitForm.customerComment || null,
        });
        toast.add({ title: 'Hotelbuchung erneut eingereicht', color: 'green' });
        closeResubmitModal();
        await loadBookings();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Die erneute Einreichung ist fehlgeschlagen.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        resubmitting.value = false;
    }
}

onMounted(async () => {
    await Promise.all([loadBookings(), loadDogs()]);
});

watch(
    () => [showRequest.value, requestForm.dogId, requestForm.startAt, requestForm.endAt, requestForm.includesTravelProtection],
    () => {
        schedulePreview();
    },
);
</script>
