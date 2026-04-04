<template>
<UCard>
    <AppSkeletonCollection
        v-if='loading'
        :mobile-cards='4'
        :desktop-rows='6'
        :desktop-columns='4'
        :meta-columns='1'
        :show-badge='false'
    />
    <div v-else-if='customers.length === 0' class='text-sm text-slate-400'>
        Keine passenden Kunden gefunden.
    </div>
    <template v-else>
        <div class='space-y-3 md:hidden'>
            <button
                v-for='customer in customers'
                :key='customer.id'
                type='button'
                :data-testid='`customer-row-${customer.id}`'
                class='w-full rounded-lg border border-slate-200 bg-white p-4 text-left transition hover:border-slate-300'
                @click="emit('select', customer)"
            >
                <div class='flex items-start justify-between gap-3'>
                    <div class='min-w-0'>
                        <p class='font-medium text-slate-800'>
                            {{ customer.name }}
                        </p>
                        <p class='truncate text-sm text-slate-500'>
                            {{ customer.email }}
                        </p>
                    </div>
                    <span class='text-xs text-slate-400'>{{ formatDate(customer.createdAt) }}</span>
                </div>
                <p class='mt-3 text-sm text-slate-600'>
                    {{ customer.address.city ? `${customer.address.postalCode} ${customer.address.city}` : 'Kein Ort hinterlegt' }}
                </p>
            </button>
        </div>
        <div class='hidden md:block'>
            <UTable
                :sort='sort'
                :columns='columns'
                :rows='customers'
                sort-mode='manual'
                @select="emit('select', $event)"
                @update:sort="emit('update:sort', $event)"
            >
                <template #name-data='{ row }'>
                    <span :data-testid='`customer-name-${row.id}`' class='font-medium text-slate-800'>{{ row.name }}</span>
                </template>
                <template #createdAt-data='{ row }'>
                    {{ formatDate(row.createdAt) }}
                </template>
                <template #address-data='{ row }'>
                    <span v-if='row.address.city'>{{ row.address.postalCode }} {{ row.address.city }}</span>
                    <span v-else class='text-slate-400'>–</span>
                </template>
            </UTable>
        </div>
        <div class='mt-6 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between'>
            <p class='text-sm text-slate-500'>
                {{ resultSummary }}
            </p>
            <UPagination
                v-if='showPagination'
                :model-value='currentPage'
                :page-count='pageSize'
                :total='totalCustomers'
                :show-first='true'
                :show-last='true'
                @update:model-value="emit('update:currentPage', $event)"
            />
        </div>
    </template>
</UCard>
</template>

<script setup lang="ts">
import type { Customer } from '~/types';

defineProps<{
    loading: boolean,
    customers: Customer[],
    sort: { column: string | null; direction: 'asc' | 'desc' },
    columns: Array<{ key: string; label: string; sortable?: boolean }>,
    resultSummary: string,
    showPagination: boolean,
    currentPage: number,
    pageSize: number,
    totalCustomers: number,
}>();

const emit = defineEmits<{
    (event: 'update:sort', value: { column: string | null; direction: 'asc' | 'desc' }): void,
    (event: 'update:currentPage', value: number): void,
    (event: 'select', value: Customer): void,
}>();

const { formatDate } = useHelpers();
</script>
