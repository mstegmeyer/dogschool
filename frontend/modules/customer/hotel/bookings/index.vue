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

    <HotelBookingsList :loading='loading' :bookings='bookings' />

    <HotelBookingRequestModal
        v-model='showRequest'
        :dog-options='dogOptions'
        :selected-dog-name='selectedDog?.name || ""'
        :stored-shoulder-height-cm='selectedDog?.shoulderHeightCm || 0'
        :form='requestForm'
        :field-errors='fieldErrors'
        :form-error='formError'
        :saving='saving'
        @submit='requestBooking'
        @cancel='closeRequestModal'
        @clear-field-error='clearFieldError'
    />
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Dog, HotelBooking } from '~/types';
import HotelBookingsList from './components/BookingsList.vue';
import HotelBookingRequestModal from './components/RequestModal.vue';

definePageMeta({ layout: 'customer' });

const api = useApi();
const toast = useToast();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError } = useFormFeedback();

const bookings = ref<HotelBooking[]>([]);
const dogs = ref<Dog[]>([]);
const loading = ref(true);
const saving = ref(false);
const showRequest = ref(false);

const requestForm = reactive({
    dogId: '',
    startAt: defaultBookingDateTime(1, 8),
    endAt: defaultBookingDateTime(1, 18),
    currentShoulderHeightCm: 0,
});

const selectedDog = computed(() => dogs.value.find(dog => dog.id === requestForm.dogId) || null);
const dogOptions = computed(() => dogs.value.map(dog => ({ label: dog.name, value: dog.id })));

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

function toDateTimeLocalValue(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function closeRequestModal(): void {
    showRequest.value = false;
    clearFormErrors();
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
        });
        toast.add({ title: 'Hotelbuchung angefragt', color: 'green' });
        closeRequestModal();
        requestForm.dogId = '';
        requestForm.startAt = defaultBookingDateTime(1, 8);
        requestForm.endAt = defaultBookingDateTime(1, 18);
        requestForm.currentShoulderHeightCm = 0;
        await loadBookings();
        const dogResponse = await api.get<ApiListResponse<Dog>>('/api/customer/dogs');
        dogs.value = dogResponse.items;
    } catch (cause) {
        applyApiError(cause, 'Die Hotelbuchung konnte nicht angefragt werden.');
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    const [bookingResponse, dogResponse] = await Promise.all([
        api.get<ApiListResponse<HotelBooking>>('/api/customer/hotel-bookings'),
        api.get<ApiListResponse<Dog>>('/api/customer/dogs'),
    ]);
    bookings.value = bookingResponse.items;
    dogs.value = dogResponse.items;
    loading.value = false;
});
</script>
