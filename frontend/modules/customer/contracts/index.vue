<template>
<div>
    <div class='flex items-center justify-between mb-6'>
        <h1 class='text-2xl font-bold text-slate-800'>
            Verträge
        </h1>
        <UButton icon='i-heroicons-plus' label='Vertrag anfragen' @click='showRequest = true' />
    </div>

    <ContractsList :loading='loading' :contracts='contracts' />

    <RequestModal
        v-model='showRequest'
        :dog-options='dogOptions'
        :form='requestForm'
        :field-errors='fieldErrors'
        :form-error='formError'
        :saving='saving'
        @submit='requestContract'
        @cancel='closeRequestModal'
        @normalize-start-date='normalizeRequestStartDate'
        @clear-field-error='clearFieldError'
    />
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Contract, Dog } from '~/types';
import ContractsList from './components/ContractsList.vue';
import RequestModal from './components/RequestModal.vue';

definePageMeta({ layout: 'customer' });

const api = useApi();
const toast = useToast();
const { toMonthStartIso, isFirstOfMonth, firstDayOfNextMonthIso } = useHelpers();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError, errorFor } = useFormFeedback();

const contracts = ref<Contract[]>([]);
const dogs = ref<Dog[]>([]);
const loading = ref(true);
const showRequest = ref(false);
const saving = ref(false);

const requestForm = reactive({ dogId: '', coursesPerWeek: 2, startDate: firstDayOfNextMonthIso() });
const dogOptions = computed(() => dogs.value.map(d => ({ label: d.name, value: d.id })));

function normalizeRequestStartDate() {
    if (requestForm.startDate) {
        requestForm.startDate = toMonthStartIso(requestForm.startDate);
    }
}

function closeRequestModal() {
    showRequest.value = false;
    clearFormErrors();
}

async function requestContract() {
    clearFormErrors();

    if (!requestForm.dogId) {setFieldError('dogId', 'Bitte einen Hund auswählen.');}
    if (!requestForm.coursesPerWeek || requestForm.coursesPerWeek < 1 || requestForm.coursesPerWeek > 7) {
        setFieldError('coursesPerWeek', 'Bitte 1 bis 7 Kurse pro Woche angeben.');
    }
    if (!requestForm.startDate) {
        setFieldError('startDate', 'Bitte ein Startdatum wählen.');
    } else if (!isFirstOfMonth(requestForm.startDate)) {
        setFieldError('startDate', 'Bitte einen Monatsersten als Startdatum wählen.');
    }
    if (errorFor('dogId') || errorFor('coursesPerWeek') || errorFor('startDate')) {
        setFormError('Bitte prüfe die markierten Felder.');
        return;
    }

    saving.value = true;
    try {
        await api.post('/api/customer/contracts', {
            dogId: requestForm.dogId,
            coursesPerWeek: requestForm.coursesPerWeek,
            startDate: requestForm.startDate || null,
        });
        toast.add({ title: 'Vertrag angefragt', color: 'green' });
        closeRequestModal();
        requestForm.startDate = firstDayOfNextMonthIso();
        await loadContracts();
    } catch (cause) {
        applyApiError(cause, 'Die Anfrage konnte nicht gespeichert werden.');
    } finally {
        saving.value = false;
    }
}

async function loadContracts(): Promise<void> {
    loading.value = true;
    const res = await api.get<ApiListResponse<Contract>>('/api/customer/contracts');
    contracts.value = res.items;
    loading.value = false;
}

onMounted(async () => {
    const [, dogRes] = await Promise.all([
        loadContracts(),
        api.get<ApiListResponse<Dog>>('/api/customer/dogs'),
    ]);
    dogs.value = dogRes.items;
});
</script>
