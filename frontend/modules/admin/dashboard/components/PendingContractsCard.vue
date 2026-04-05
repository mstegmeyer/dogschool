<template>
<UCard>
    <template #header>
        <div class='flex items-center justify-between'>
            <div class='flex items-center gap-2'>
                <h3 class='font-semibold text-slate-800'>
                    Offene Vertragsfälle
                </h3>
                <UBadge color='amber' variant='soft'>
                    {{ count }}
                </UBadge>
            </div>
            <UButton variant='ghost' size='xs' :to='{ path: "/admin/contracts", query: { state: "open" } }'>
                Alle anzeigen
            </UButton>
        </div>
    </template>

    <AppSkeletonCollection
        v-if='loading'
        :show-desktop-table='false'
        :mobile-cards='4'
        :meta-columns='0'
        :content-lines='2'
    />
    <div v-else-if='contracts.length === 0' class='py-4 text-center text-sm text-slate-400'>
        Keine offenen Anfragen
    </div>
    <div v-else class='divide-y divide-slate-100'>
        <div v-for='contract in contracts' :key='contract.id' class='flex items-start justify-between gap-3 py-3'>
            <div class='min-w-0 pr-2'>
                <p class='truncate text-sm font-medium text-slate-700'>
                    {{ contract.dogName || 'Hund' }} · {{ contract.customerName || 'Kunde' }}
                </p>
                <p class='text-xs text-slate-400'>
                    {{ contract.coursesPerWeek }}× / Woche · {{ formatContractMonthlyPrice(contract.price, contract.type) }}
                </p>
            </div>
            <div class='flex shrink-0 flex-col items-end gap-2'>
                <UBadge :color='contractStateColor(contract.state)' variant='soft'>
                    {{ contractStateLabel(contract.state) }}
                </UBadge>
                <UButton
                    :data-testid='`dashboard-review-contract-${contract.id}`'
                    size='xs'
                    color='primary'
                    variant='soft'
                    label='Prüfen'
                    @click="emit('review', contract)"
                />
            </div>
        </div>
    </div>
</UCard>
</template>

<script setup lang="ts">
import type { Contract } from '~/types';

defineProps<{
    loading: boolean,
    count: number,
    contracts: Contract[],
}>();

const emit = defineEmits<{
    (event: 'review', value: Contract): void,
}>();

const { formatContractMonthlyPrice, contractStateLabel, contractStateColor } = useHelpers();
</script>
