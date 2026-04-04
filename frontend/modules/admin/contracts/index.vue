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
            class='w-full sm:w-44'
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
        @approve='approve'
        @decline='decline'
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
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Contract } from '~/types';
import CancelModal from './components/CancelModal.vue';
import ContractsTable from './components/ContractsTable.vue';

definePageMeta({ layout: 'admin' });

const api = useApi();
const toast = useToast();
const { toMonthEndIso, isLastOfMonth } = useHelpers();
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
const pageSize = 20;

const stateOptions = [
    { label: 'Aktiv', value: 'ACTIVE' },
    { label: 'Alle', value: 'all' },
    { label: 'Angefragt', value: 'REQUESTED' },
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
    if (totalContracts.value === 0) {return '0 Verträge';}
    if (totalPages.value <= 1) {return `${totalContracts.value} Verträge`;}

    return `${pageStart.value}–${pageEnd.value} von ${totalContracts.value} Verträgen`;
});

function openCancelConfirm(contract: Contract) {
    contractToCancel.value = contract;
    cancelForm.endDate = contract.endDate ?? '';
    clearFormErrors();
    showCancelModal.value = true;
}

function closeCancelModal() {
    showCancelModal.value = false;
    contractToCancel.value = null;
    cancelForm.endDate = '';
    clearFormErrors();
}

function normalizeCancelEndDate() {
    if (cancelForm.endDate) {
        cancelForm.endDate = toMonthEndIso(cancelForm.endDate);
    }
}

async function confirmCancel() {
    if (!contractToCancel.value) {return;}
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

async function approve(contract: Contract) {
    try {
        await api.post(`/api/admin/contracts/${contract.id}/approve`);
        toast.add({ title: 'Vertrag genehmigt', color: 'green' });
        await loadContracts();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Der Vertrag konnte nicht genehmigt werden.', { preferFieldSummary: false }), color: 'red' });
    }
}

async function decline(contract: Contract) {
    try {
        await api.post(`/api/admin/contracts/${contract.id}/decline`);
        toast.add({ title: 'Vertrag abgelehnt', color: 'amber' });
        await loadContracts();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Der Vertrag konnte nicht abgelehnt werden.', { preferFieldSummary: false }), color: 'red' });
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
</script>
