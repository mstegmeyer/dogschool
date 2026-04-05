<template>
<div>
    <div class='flex items-center justify-between mb-6'>
        <h1 class='text-2xl font-bold text-slate-800'>
            Verträge
        </h1>
        <UButton icon='i-heroicons-plus' label='Vertrag anfragen' @click='showRequest = true' />
    </div>

    <ContractsList
        :loading='loading'
        :contracts='contracts'
        :busy-id='reviewActionId'
        @accept='acceptPrice'
        @decline='declinePrice'
        @resubmit='openResubmitModal'
    />

    <RequestModal
        v-model='showRequest'
        :dog-options='dogOptions'
        :form='requestForm'
        :field-errors='fieldErrors'
        :form-error='formError'
        :saving='saving'
        :preview-loading='previewLoading'
        :preview='quotePreview'
        @submit='requestContract'
        @cancel='closeRequestModal'
        @normalize-start-date='normalizeRequestStartDate'
        @clear-field-error='clearFieldError'
    />

    <UModal :model-value='showResubmit' @update:model-value='showResubmit = $event'>
        <UCard v-if='contractToResubmit' data-testid='contract-resubmit-modal'>
            <template #header>
                <h3 class='font-semibold text-slate-800'>
                    Kommentar anpassen
                </h3>
            </template>
            <div class='space-y-4'>
                <PricingBreakdown
                    :snapshot='contractToResubmit.pricingSnapshot'
                    title='Aktuelle Preisübersicht'
                    total-label='Erste Rechnung'
                    :total-value='contractToResubmit.firstInvoiceTotal'
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
import type { ApiListResponse, Contract, ContractQuotePreview, Dog } from '~/types';
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
const previewLoading = ref(false);
const quotePreview = ref<ContractQuotePreview | null>(null);
const reviewActionId = ref<string | null>(null);
const showResubmit = ref(false);
const contractToResubmit = ref<Contract | null>(null);
const resubmitting = ref(false);

const requestForm = reactive({ dogId: '', coursesPerWeek: 2, startDate: firstDayOfNextMonthIso(), customerComment: '' });
const resubmitForm = reactive({ customerComment: '' });
const dogOptions = computed(() => dogs.value.map(d => ({ label: d.name, value: d.id })));
let previewTimer: ReturnType<typeof setTimeout> | null = null;
let previewRequestId = 0;

function clearPreviewTimer(): void {
    if (previewTimer !== null) {
        clearTimeout(previewTimer);
        previewTimer = null;
    }
}

function invalidatePreviewRequest(): number {
    previewRequestId += 1;

    return previewRequestId;
}

function normalizeRequestStartDate() {
    if (requestForm.startDate) {
        requestForm.startDate = toMonthStartIso(requestForm.startDate);
    }
    schedulePreview();
}

function closeRequestModal() {
    showRequest.value = false;
    clearPreviewTimer();
    invalidatePreviewRequest();
    clearFormErrors();
    previewLoading.value = false;
    quotePreview.value = null;
}

function closeResubmitModal(): void {
    showResubmit.value = false;
    contractToResubmit.value = null;
    resubmitForm.customerComment = '';
}

function openResubmitModal(contract: Contract): void {
    contractToResubmit.value = contract;
    resubmitForm.customerComment = contract.customerComment || '';
    showResubmit.value = true;
}

function canPreviewRequest(): boolean {
    return !!requestForm.dogId
        && requestForm.coursesPerWeek >= 1
        && requestForm.coursesPerWeek <= 7
        && !!requestForm.startDate
        && isFirstOfMonth(requestForm.startDate);
}

function schedulePreview(): void {
    clearPreviewTimer();
    const requestId = invalidatePreviewRequest();

    if (!showRequest.value || !canPreviewRequest()) {
        previewLoading.value = false;
        quotePreview.value = null;
        return;
    }

    previewLoading.value = true;
    previewTimer = window.setTimeout(() => {
        previewTimer = null;
        void loadPreview(requestId);
    }, 250);
}

async function loadPreview(requestId: number): Promise<void> {
    try {
        const preview = await api.post<ContractQuotePreview>('/api/customer/contracts/preview', {
            dogId: requestForm.dogId,
            coursesPerWeek: requestForm.coursesPerWeek,
            startDate: requestForm.startDate || null,
            customerComment: requestForm.customerComment || null,
        });
        if (!showRequest.value || requestId !== previewRequestId) {
            return;
        }

        quotePreview.value = preview;
    } catch {
        if (!showRequest.value || requestId !== previewRequestId) {
            return;
        }

        quotePreview.value = null;
    } finally {
        if (requestId === previewRequestId) {
            previewLoading.value = false;
        }
    }
}

async function requestContract() {
    clearFormErrors();

    if (!requestForm.dogId) {
        setFieldError('dogId', 'Bitte einen Hund auswählen.');
    }
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
            customerComment: requestForm.customerComment || null,
        });
        toast.add({ title: 'Vertrag angefragt', color: 'green' });
        closeRequestModal();
        requestForm.startDate = firstDayOfNextMonthIso();
        requestForm.customerComment = '';
        await loadContracts();
    } catch (cause) {
        applyApiError(cause, 'Die Anfrage konnte nicht gespeichert werden.');
    } finally {
        saving.value = false;
    }
}

async function acceptPrice(contract: Contract): Promise<void> {
    reviewActionId.value = contract.id;
    try {
        await api.post(`/api/customer/contracts/${contract.id}/accept-price`);
        toast.add({ title: 'Preis akzeptiert', color: 'green' });
        await loadContracts();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Der Preis konnte nicht bestätigt werden.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        reviewActionId.value = null;
    }
}

async function declinePrice(contract: Contract): Promise<void> {
    reviewActionId.value = contract.id;
    try {
        await api.post(`/api/customer/contracts/${contract.id}/decline-price`);
        toast.add({ title: 'Vertrag abgelehnt', color: 'amber' });
        await loadContracts();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Der Preis konnte nicht abgelehnt werden.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        reviewActionId.value = null;
    }
}

async function submitResubmission(): Promise<void> {
    if (!contractToResubmit.value) {
        return;
    }

    resubmitting.value = true;
    try {
        await api.post(`/api/customer/contracts/${contractToResubmit.value.id}/resubmit`, {
            customerComment: resubmitForm.customerComment || null,
        });
        toast.add({ title: 'Vertrag erneut eingereicht', color: 'green' });
        closeResubmitModal();
        await loadContracts();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Die erneute Einreichung ist fehlgeschlagen.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        resubmitting.value = false;
    }
}

async function loadContracts(): Promise<void> {
    loading.value = true;
    const res = await api.get<ApiListResponse<Contract>>('/api/customer/contracts');
    contracts.value = res.items;
    loading.value = false;
}

watch(
    () => [showRequest.value, requestForm.dogId, requestForm.coursesPerWeek, requestForm.startDate],
    () => {
        schedulePreview();
    },
);

onMounted(async () => {
    const [, dogRes] = await Promise.all([
        loadContracts(),
        api.get<ApiListResponse<Dog>>('/api/customer/dogs'),
    ]);
    dogs.value = dogRes.items;
});

onBeforeUnmount(() => {
    clearPreviewTimer();
    invalidatePreviewRequest();
});
</script>
