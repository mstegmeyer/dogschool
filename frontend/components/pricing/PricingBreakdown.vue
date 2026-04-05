<template>
<div v-if='snapshot?.lineItems?.length' class='rounded-xl border border-slate-200 bg-slate-50/70 p-4'>
    <div class='mb-3 flex items-center justify-between gap-3'>
        <p class='text-sm font-semibold text-slate-800'>
            {{ title }}
        </p>
        <p v-if='totalValue' class='text-sm font-semibold text-slate-800'>
            {{ totalLabel }}: {{ formatMoney(totalValue) }}
        </p>
    </div>

    <div class='space-y-2'>
        <div
            v-for='item in snapshot.lineItems'
            :key='item.key'
            class='flex items-start justify-between gap-4 rounded-lg bg-white px-3 py-2'
        >
            <div class='min-w-0'>
                <p class='text-sm font-medium text-slate-700'>
                    {{ item.label }}
                </p>
                <p class='text-xs text-slate-500'>
                    {{ item.quantity }} × {{ formatMoney(item.unitPrice) }}
                </p>
            </div>
            <p class='whitespace-nowrap text-sm font-semibold text-slate-800'>
                {{ formatMoney(item.amount) }}
            </p>
        </div>
    </div>
</div>
</template>

<script setup lang="ts">
import type { PricingSnapshot } from '~/types';

withDefaults(defineProps<{
    snapshot: PricingSnapshot | null | undefined,
    title?: string,
    totalLabel?: string,
    totalValue?: string | null,
}>(), {
    title: 'Preisübersicht',
    totalLabel: 'Gesamt',
    totalValue: null,
});

const { formatMoney } = useHelpers();
</script>
