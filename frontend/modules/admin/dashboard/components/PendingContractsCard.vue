<template>
<UCard>
    <template #header>
        <div class='flex items-center justify-between'>
            <div class='flex items-center gap-2'>
                <h3 class='font-semibold text-slate-800'>
                    Offene Vertragsanfragen
                </h3>
                <UBadge color='amber' variant='soft'>
                    {{ count }}
                </UBadge>
            </div>
            <UButton variant='ghost' size='xs' to='/admin/contracts'>
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
        <div v-for='contract in contracts' :key='contract.id' class='flex items-center justify-between py-3'>
            <div class='min-w-0 pr-2'>
                <p class='truncate text-sm font-medium text-slate-700'>
                    {{ contract.dogName || 'Hund' }} · {{ contract.customerName || 'Kunde' }}
                </p>
                <p class='text-xs text-slate-400'>
                    {{ contract.coursesPerWeek }}× / Woche · {{ formatContractMonthlyPrice(contract.price, contract.type) }}
                </p>
            </div>
            <UBadge color='amber' variant='soft'>
                Angefragt
            </UBadge>
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

const { formatContractMonthlyPrice } = useHelpers();
</script>
