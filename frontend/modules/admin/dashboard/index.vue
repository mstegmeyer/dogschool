<template>
<div>
    <h1 class='text-2xl font-bold text-slate-800 mb-6'>
        Dashboard
    </h1>

    <StatsGrid :loading='loading' :stats='stats' />

    <div class='grid grid-cols-1 lg:grid-cols-2 gap-6'>
        <PendingContractsCard
            :loading='loading'
            :count='requestedContracts.length'
            :contracts='requestedContractPreview'
            @review='openReview'
        />
        <TodayScheduleCard :loading='loading' :course-dates='todaySchedule' />
    </div>

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
import type { ApiListResponse, Course, CourseDate, Contract } from '~/types';
import ReviewModal from '../contracts/components/ReviewModal.vue';
import PendingContractsCard from './components/PendingContractsCard.vue';
import StatsGrid from './components/StatsGrid.vue';
import TodayScheduleCard from './components/TodayScheduleCard.vue';
import type { DashboardStat } from './types';

definePageMeta({ layout: 'admin' });

const api = useApi();
const toast = useToast();
const { todayIso, getWeekMonday } = useHelpers();

const contracts = ref<Contract[]>([]);
const courses = ref<Course[]>([]);
const calendarItems = ref<CourseDate[]>([]);
const loading = ref(true);
const showReviewModal = ref(false);
const selectedContract = ref<Contract | null>(null);
const reviewApproving = ref(false);
const reviewDeclining = ref(false);
const reviewForm = reactive({ price: '', registrationFee: '', adminComment: '' });

const activeContracts = computed(() => contracts.value.filter(contract => contract.state === 'ACTIVE'));
const requestedContracts = computed(() => contracts.value.filter(contract => contract.state === 'REQUESTED'));
const requestedContractPreview = computed(() => requestedContracts.value.slice(0, 5));
const todaySchedule = computed(() => calendarItems.value
    .filter(courseDate => courseDate.date === todayIso())
    .sort((a, b) => a.startTime.localeCompare(b.startTime)));
const monthlyContractValue = computed(() => activeContracts.value.reduce((sum, contract) => {
    const rawValue = contract.priceMonthly ?? contract.price;
    const parsedValue = Number.parseFloat(rawValue ?? '0');
    return sum + (Number.isFinite(parsedValue) ? parsedValue : 0);
}, 0));

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

async function loadContracts(): Promise<void> {
    const response = await api.get<ApiListResponse<Contract>>('/api/admin/contracts');
    contracts.value = response.items;
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

const stats = computed<DashboardStat[]>(() => [
    {
        label: 'Aktive Kurse / Woche',
        value: courses.value.filter(course => !course.archived).length,
        icon: 'i-heroicons-academic-cap',
        bgClass: 'bg-komm-100',
        iconClass: 'text-komm-600',
    },
    {
        label: 'Aktive Verträge',
        value: activeContracts.value.length,
        icon: 'i-heroicons-document-check',
        bgClass: 'bg-blue-50',
        iconClass: 'text-blue-500',
    },
    {
        label: 'Monatlicher Vertragswert',
        value: formatCurrency(monthlyContractValue.value),
        icon: 'i-heroicons-banknotes',
        bgClass: 'bg-emerald-50',
        iconClass: 'text-emerald-500',
    },
]);

onMounted(async () => {
    try {
        const [, courseRes, calRes] = await Promise.all([
            loadContracts(),
            api.get<ApiListResponse<Course>>('/api/admin/courses'),
            api.get<ApiListResponse<CourseDate>>(`/api/admin/calendar?week=${getWeekMonday(todayIso())}`),
        ]);
        courses.value = courseRes.items;
        calendarItems.value = calRes.items;
    } finally {
        loading.value = false;
    }
});
</script>
