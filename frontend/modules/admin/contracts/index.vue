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

    <ReviewModal
        :model-value='showReviewModal'
        :contract='selectedContract'
        :price='reviewForm.price'
        :registration-fee='reviewForm.registrationFee'
        :admin-comment='reviewForm.adminComment'
        :approving='reviewApproving'
        :declining='reviewDeclining'
        @update:model-value='handleReviewModalModelUpdate'
        @update:price='reviewForm.price = $event'
        @update:registration-fee='reviewForm.registrationFee = $event'
        @update:admin-comment='reviewForm.adminComment = $event'
        @approve='approveSelectedContract'
        @decline='declineSelectedContract'
    />
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Contract } from '~/types';
import CancelModal from './components/CancelModal.vue';
import ContractsTable from './components/ContractsTable.vue';
import ReviewModal from './components/ReviewModal.vue';

definePageMeta({ layout: 'admin' });

const api = useApi();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const { toMonthEndIso, isLastOfMonth } = useHelpers();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError, errorFor } = useFormFeedback();

const allowedStateFilters = ['ACTIVE', 'open', 'all', 'REQUESTED', 'PENDING_CUSTOMER_APPROVAL', 'DECLINED', 'CANCELLED'] as const;
type ContractStateFilter = typeof allowedStateFilters[number];

const contracts = ref<Contract[]>([]);
const loading = ref(true);
const stateFilter = ref<ContractStateFilter>(resolveStateFilter(route.query.state));
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
    { label: 'Offen', value: 'open' },
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

function resolveStateFilter(value: unknown): ContractStateFilter {
    return typeof value === 'string' && allowedStateFilters.includes(value as ContractStateFilter)
        ? value as ContractStateFilter
        : 'ACTIVE';
}

function syncStateFilterQuery(): void {
    const nextQuery = { ...route.query };

    if (stateFilter.value === 'ACTIVE') {
        delete nextQuery.state;
    } else {
        nextQuery.state = stateFilter.value;
    }

    const currentQueryState = typeof route.query.state === 'string'
        ? route.query.state
        : undefined;
    const nextQueryState = stateFilter.value === 'ACTIVE'
        ? undefined
        : stateFilter.value;

    if (currentQueryState === nextQueryState) {
        return;
    }

    void router.replace({ query: nextQuery });
}

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

function handleReviewModalModelUpdate(value: boolean): void {
    if (value) {
        showReviewModal.value = true;
        return;
    }

    closeReviewModal();
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
    syncStateFilterQuery();

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
    syncStateFilterQuery();
    void loadContracts();
});
</script>
