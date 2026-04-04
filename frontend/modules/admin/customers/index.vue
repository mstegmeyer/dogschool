<template>
<div>
    <div class='mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'>
        <h1 class='text-2xl font-bold text-slate-800'>
            Kunden
        </h1>
        <UInput
            v-model='search'
            data-testid='customer-search'
            icon='i-heroicons-magnifying-glass'
            placeholder='Suchen…'
            class='w-full sm:w-64'
        />
    </div>

    <CustomersList
        :loading='loading'
        :customers='customers'
        :sort='sort'
        :columns='columns'
        :result-summary='resultSummary'
        :show-pagination='showPagination'
        :current-page='currentPage'
        :page-size='pageSize'
        :total-customers='totalCustomers'
        @select='onSelect'
        @update:sort='sort = $event'
        @update:current-page='currentPage = $event'
    />
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Customer } from '~/types';
import CustomersList from './components/List.vue';

definePageMeta({ layout: 'admin' });

const api = useApi();

const customers = ref<Customer[]>([]);
const search = ref('');
const loading = ref(true);
const currentPage = ref(1);
const debouncedSearch = ref('');
const totalCustomers = ref(0);
const totalPages = ref(1);
const sort = ref<{ column: string | null; direction: 'asc' | 'desc' }>({
    column: 'createdAt',
    direction: 'desc',
});

const pageSize = 20;
let searchDebounceTimeout: ReturnType<typeof setTimeout> | null = null;
let latestLoadId = 0;

const columns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'E-Mail', sortable: true },
    { key: 'address', label: 'Ort' },
    { key: 'createdAt', label: 'Registriert', sortable: true },
];

const showPagination = computed(() => totalCustomers.value > pageSize);
const pageStart = computed(() => (totalCustomers.value === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1));
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, totalCustomers.value));
const resultSummary = computed(() => {
    if (totalCustomers.value === 0) {
        return '0 Kunden';
    }
    if (totalPages.value <= 1) {
        return `${totalCustomers.value} Kunden`;
    }

    return `${pageStart.value}–${pageEnd.value} von ${totalCustomers.value} Kunden`;
});

function onSelect(row: Customer) {
    navigateTo(`/admin/customers/${row.id}`);
}

async function loadCustomers(): Promise<void> {
    const loadId = ++latestLoadId;
    loading.value = true;

    const params = new URLSearchParams({
        page: `${currentPage.value}`,
        limit: `${pageSize}`,
    });
    if (debouncedSearch.value) {
        params.set('q', debouncedSearch.value);
    }
    if (sort.value.column) {
        params.set('sort', sort.value.column);
        params.set('direction', sort.value.direction);
    }

    const res = await api.get<ApiListResponse<Customer>>(`/api/admin/customers?${params.toString()}`);
    if (loadId !== latestLoadId) {
        return;
    }

    customers.value = res.items;
    totalCustomers.value = res.pagination?.total ?? res.items.length;
    totalPages.value = res.pagination?.pages ?? 1;
    loading.value = false;
}

watch(currentPage, () => {
    void loadCustomers();
});

watch(search, (value) => {
    if (searchDebounceTimeout !== null) {
        clearTimeout(searchDebounceTimeout);
    }

    searchDebounceTimeout = setTimeout(() => {
        debouncedSearch.value = value.trim();

        if (currentPage.value !== 1) {
            currentPage.value = 1;
            return;
        }

        void loadCustomers();
    }, 250);
});

watch(sort, () => {
    if (currentPage.value !== 1) {
        currentPage.value = 1;
        return;
    }

    void loadCustomers();
}, { deep: true });

onBeforeUnmount(() => {
    if (searchDebounceTimeout !== null) {
        clearTimeout(searchDebounceTimeout);
    }
});

onMounted(() => {
    void loadCustomers();
});
</script>
