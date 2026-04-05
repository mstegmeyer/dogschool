<template>
<div>
    <div class='mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'>
        <h1 class='text-2xl font-bold text-slate-800'>
            Verträge
        </h1>
        <USelectMenu
            v-model='stateFilter'
            data-testid='contract-state-filter'
            :options='stateOptions'
            value-attribute='value'
            class='w-full sm:w-52'
        />
    </div>

    <ContractsTable
        :loading='loading'
        :contracts='contracts'
        :sort='sort'
        :columns='columns'
        :result-summary='resultSummary'
        :show-pagination='showPagination'
        :current-page='currentPage'
        :page-size='pageSize'
        :total-contracts='totalContracts'
        @review='openReview'
        @cancel='openCancelConfirm'
        @update:sort='sort = $event'
        @update:current-page='currentPage = $event'
    />

    <CancelModal
        v-model='showCancelModal'
        :contract='contractToCancel'
        :end-date='cancelForm.endDate'
        :end-date-error="errorFor('endDate')"
        :form-error='formError'
        :saving='cancelling'
        @cancel='closeCancelModal'
        @submit='confirmCancel'
        @normalize-end-date='normalizeCancelEndDate'
        @clear-end-date-error="clearFieldError('endDate')"
        @update:end-date='cancelForm.endDate = $event'
    />

    <UModal :model-value='showReviewModal' @update:model-value='showReviewModal = $event'>
        <UCard v-if='selectedContract' data-testid='contract-review-modal'>
            <template #header>
                <div class='flex items-start justify-between gap-3'>
                    <div>
                        <h3 class='font-semibold text-slate-800'>
                            Vertrag prüfen
                        </h3>
                        <p class='text-sm text-slate-500'>
                            {{ selectedContract.dogName || 'Hund' }} · {{ selectedContract.customerName || 'Kunde' }}
                        </p>
                    </div>
                    <UBadge :color='contractStateColor(selectedContract.state)' variant='soft'>
                        {{ contractStateLabel(selectedContract.state) }}
                    </UBadge>
                </div>
            </template>

            <div class='space-y-4'>
                <PricingBreakdown
                    :snapshot='selectedContract.pricingSnapshot'
                    title='Aktuelle Preisübersicht'
                    total-label='Erste Rechnung'
                    :total-value='reviewFirstInvoiceTotal'
                />

                <div v-if='selectedContract.customerComment' class='rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600'>
                    <span class='font-medium text-slate-700'>Kundenkommentar:</span>
                    {{ selectedContract.customerComment }}
                </div>

                <UFormGroup label='Monatspreis'>
                    <UInput v-model='reviewForm.price' type='number' step='0.01' />
                    <template #hint>
                        Automatischer Vorschlag: {{ formatContractMonthlyPrice(selectedContract.quotedMonthlyPrice, selectedContract.type) }}
                    </template>
                </UFormGroup>

                <UFormGroup label='Anmeldegebühr'>
                    <UInput v-model='reviewForm.registrationFee' type='number' step='0.01' />
                    <template #hint>
                        Automatischer Vorschlag: {{ formatMoney(reviewSuggestedRegistrationFee) }}
                    </template>
                </UFormGroup>

                <UFormGroup label='Admin-Kommentar'>
                    <UTextarea
                        v-model='reviewForm.adminComment'
                        :rows='4'
                        placeholder='Begründung für Preisänderungen oder Hinweise zur Anfrage.'
                    />
                </UFormGroup>

                <div class='flex justify-end gap-2'>
                    <UButton variant='ghost' label='Schließen' @click='closeReviewModal' />
                    <UButton
                        data-testid='decline-contract-review'
                        color='red'
                        variant='soft'
                        :loading='reviewDeclining'
                        label='Ablehnen'
                        @click='declineSelectedContract'
                    />
                    <UButton
                        data-testid='approve-contract-review'
                        color='green'
                        :loading='reviewApproving'
                        label='Bestätigen'
                        @click='approveSelectedContract'
                    />
                </div>
            </div>
        </UCard>
    </UModal>
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Contract } from '~/types';
import CancelModal from './components/CancelModal.vue';
import ContractsTable from './components/ContractsTable.vue';

definePageMeta({ layout: 'admin' });

const api = useApi();
const toast = useToast();
const { toMonthEndIso, isLastOfMonth, contractStateLabel, contractStateColor, formatContractMonthlyPrice, formatMoney } = useHelpers();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError, errorFor } = useFormFeedback();

const contracts = ref<Contract[]>([]);
const loading = ref(true);
const stateFilter = ref('ACTIVE');
const currentPage = ref(1);
const totalContracts = ref(0);
const totalPages = ref(1);
const sort = ref<{ column: string | null; direction: 'asc' | 'desc' }>({
    column: 'createdAt',
    direction: 'desc',
});

const showCancelModal = ref(false);
const contractToCancel = ref<Contract | null>(null);
const cancelling = ref(false);
const cancelForm = reactive({ endDate: '' });

const showReviewModal = ref(false);
const selectedContract = ref<Contract | null>(null);
const reviewApproving = ref(false);
const reviewDeclining = ref(false);
const reviewForm = reactive({ price: '', registrationFee: '', adminComment: '' });

const pageSize = 20;

const stateOptions = [
    { label: 'Aktiv', value: 'ACTIVE' },
    { label: 'Alle', value: 'all' },
    { label: 'Angefragt', value: 'REQUESTED' },
    { label: 'Preisprüfung', value: 'PENDING_CUSTOMER_APPROVAL' },
    { label: 'Abgelehnt', value: 'DECLINED' },
    { label: 'Gekündigt', value: 'CANCELLED' },
];

const columns = [
    { key: 'participant', label: 'Hund · Halter' },
    { key: 'state', label: 'Status', sortable: true },
    { key: 'coursesPerWeek', label: 'Kurse' },
    { key: 'price', label: 'Preis' },
    { key: 'dates', label: 'Zeitraum' },
    { key: 'createdAt', label: 'Erstellt', sortable: true },
    { key: 'actions', label: 'Aktion' },
];

const showPagination = computed(() => totalContracts.value > pageSize);
const pageStart = computed(() => (totalContracts.value === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1));
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, totalContracts.value));
const resultSummary = computed(() => {
    if (totalContracts.value === 0) {
        return '0 Verträge';
    }
    if (totalPages.value <= 1) {
        return `${totalContracts.value} Verträge`;
    }

    return `${pageStart.value}–${pageEnd.value} von ${totalContracts.value} Verträgen`;
});

const reviewSuggestedRegistrationFee = computed(() => {
    if (!selectedContract.value) {
        return '0.00';
    }

    const snapshotRegistrationFee = selectedContract.value.pricingSnapshot.quotedRegistrationFee;

    return typeof snapshotRegistrationFee === 'string'
        ? snapshotRegistrationFee
        : selectedContract.value.registrationFee;
});

const reviewFirstInvoiceTotal = computed(() => formatAmount(
    amountToCents(reviewForm.price || selectedContract.value?.price || '0.00')
    + amountToCents(reviewForm.registrationFee || selectedContract.value?.registrationFee || '0.00'),
));

function openCancelConfirm(contract: Contract): void {
    contractToCancel.value = contract;
    cancelForm.endDate = contract.endDate ?? '';
    clearFormErrors();
    showCancelModal.value = true;
}

function closeCancelModal(): void {
    showCancelModal.value = false;
    contractToCancel.value = null;
    cancelForm.endDate = '';
    clearFormErrors();
}

function closeReviewModal(): void {
    showReviewModal.value = false;
    selectedContract.value = null;
    reviewForm.price = '';
    reviewForm.registrationFee = '';
    reviewForm.adminComment = '';
}

async function openReview(contract: Contract): Promise<void> {
    selectedContract.value = await api.get<Contract>(`/api/admin/contracts/${contract.id}`);
    reviewForm.price = selectedContract.value.price;
    reviewForm.registrationFee = selectedContract.value.registrationFee;
    reviewForm.adminComment = selectedContract.value.adminComment || '';
    showReviewModal.value = true;
}

function normalizeCancelEndDate(): void {
    if (cancelForm.endDate) {
        cancelForm.endDate = toMonthEndIso(cancelForm.endDate);
    }
}

async function confirmCancel(): Promise<void> {
    if (!contractToCancel.value) {
        return;
    }

    clearFormErrors();
    if (!cancelForm.endDate) {
        setFieldError('endDate', 'Bitte ein Enddatum wählen.');
    } else if (!isLastOfMonth(cancelForm.endDate)) {
        setFieldError('endDate', 'Bitte den letzten Tag eines Monats wählen.');
    }
    if (Object.keys(fieldErrors.value).length > 0) {
        setFormError('Bitte prüfe die markierten Felder.');
        return;
    }

    cancelling.value = true;
    try {
        await api.post(`/api/admin/contracts/${contractToCancel.value.id}/cancel`, {
            endDate: cancelForm.endDate,
        });
        toast.add({ title: 'Vertrag gekündigt', color: 'red' });
        closeCancelModal();
        await loadContracts();
    } catch (cause) {
        applyApiError(cause, 'Der Vertrag konnte nicht gekündigt werden.');
    } finally {
        cancelling.value = false;
    }
}

async function approveSelectedContract(): Promise<void> {
    if (!selectedContract.value) {
        return;
    }

    reviewApproving.value = true;
    try {
        await api.post(`/api/admin/contracts/${selectedContract.value.id}/approve`, {
            price: reviewForm.price || null,
            registrationFee: reviewForm.registrationFee || null,
            adminComment: reviewForm.adminComment || null,
        });
        toast.add({ title: 'Vertrag geprüft', color: 'green' });
        closeReviewModal();
        await loadContracts();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Der Vertrag konnte nicht geprüft werden.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        reviewApproving.value = false;
    }
}

async function declineSelectedContract(): Promise<void> {
    if (!selectedContract.value) {
        return;
    }

    reviewDeclining.value = true;
    try {
        await api.post(`/api/admin/contracts/${selectedContract.value.id}/decline`, {
            adminComment: reviewForm.adminComment || null,
        });
        toast.add({ title: 'Vertrag abgelehnt', color: 'amber' });
        closeReviewModal();
        await loadContracts();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Der Vertrag konnte nicht abgelehnt werden.', { preferFieldSummary: false }), color: 'red' });
    } finally {
        reviewDeclining.value = false;
    }
}

async function loadContracts(): Promise<void> {
    loading.value = true;
    const params = new URLSearchParams({
        page: `${currentPage.value}`,
        limit: `${pageSize}`,
        state: stateFilter.value,
    });
    if (sort.value.column) {
        params.set('sort', sort.value.column);
        params.set('direction', sort.value.direction);
    }
    const res = await api.get<ApiListResponse<Contract>>(`/api/admin/contracts?${params.toString()}`);
    contracts.value = res.items;
    totalContracts.value = res.pagination?.total ?? res.items.length;
    totalPages.value = res.pagination?.pages ?? 1;
    loading.value = false;
}

watch(currentPage, () => {
    void loadContracts();
});

watch(stateFilter, () => {
    if (currentPage.value !== 1) {
        currentPage.value = 1;
        return;
    }

    void loadContracts();
});

watch(sort, () => {
    if (currentPage.value !== 1) {
        currentPage.value = 1;
        return;
    }

    void loadContracts();
}, { deep: true });

onMounted(() => {
    void loadContracts();
});

function amountToCents(value: string): number {
    const normalized = value.trim().replace(',', '.');
    if (normalized === '') {
        return 0;
    }

    const numericValue = Number.parseFloat(normalized);
    if (Number.isNaN(numericValue)) {
        return 0;
    }

    return Math.round(numericValue * 100);
}

function formatAmount(cents: number): string {
    return (cents / 100).toFixed(2);
}
</script>
