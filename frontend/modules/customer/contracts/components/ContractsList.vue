<template>
<AppSkeletonCollection
    v-if='loading'
    :show-desktop-table='false'
    :mobile-cards='3'
    :meta-columns='4'
    :content-lines='0'
    :show-badge='true'
/>
<div v-else-if='contracts.length === 0' class='py-12 text-center'>
    <UIcon name='i-heroicons-document-text' class='mx-auto mb-3 h-12 w-12 text-slate-300' />
    <p class='text-slate-500'>
        Du hast noch keine Verträge.
    </p>
</div>
<div v-else class='space-y-4'>
    <UCard v-for='contract in contracts' :key='contract.id'>
        <div class='flex items-start justify-between gap-4'>
            <div class='min-w-0 flex-1'>
                <div class='flex items-center gap-2'>
                    <h3 class='font-semibold text-slate-800'>
                        Vertrag
                    </h3>
                    <UBadge :color='contractStateColor(contract.state)' variant='soft' size='xs'>
                        {{ contractStateLabel(contract.state) }}
                    </UBadge>
                </div>
                <div
                    class='mt-3 grid grid-cols-2 gap-4 text-sm'
                    :class="contract.endDate ? 'sm:grid-cols-4' : 'sm:grid-cols-3'"
                >
                    <div>
                        <p class='text-xs text-slate-400'>
                            Kurse / Woche
                        </p>
                        <p class='font-medium text-slate-700'>
                            {{ contract.coursesPerWeek }}x
                        </p>
                    </div>
                    <div>
                        <p class='text-xs text-slate-400'>
                            Preis
                        </p>
                        <p class='font-medium text-slate-700'>
                            {{ formatContractMonthlyPrice(contract.price, contract.type) }}
                        </p>
                        <p
                            v-if='contract.price !== contract.quotedMonthlyPrice'
                            class='text-xs text-slate-500'
                        >
                            Automatisch berechnet: {{ formatContractMonthlyPrice(contract.quotedMonthlyPrice, contract.type) }}
                        </p>
                    </div>
                    <div>
                        <p class='text-xs text-slate-400'>
                            Beginn
                        </p>
                        <p class='font-medium text-slate-700'>
                            {{ contract.startDate ? formatDate(contract.startDate) : '–' }}
                        </p>
                    </div>
                    <div v-if='contract.endDate'>
                        <p class='text-xs text-slate-400'>
                            Ende
                        </p>
                        <p class='font-medium text-slate-700'>
                            {{ formatDate(contract.endDate) }}
                        </p>
                    </div>
                    <div>
                        <p class='text-xs text-slate-400'>
                            Anmeldegebühr
                        </p>
                        <p class='font-medium text-slate-700'>
                            {{ formatMoney(contract.registrationFee) }}
                        </p>
                    </div>
                </div>

                <div class='mt-4 space-y-3'>
                    <PricingBreakdown
                        :snapshot='contract.pricingSnapshot'
                        title='Preisaufschlüsselung'
                        :total-label="contract.registrationFee !== '0.00' ? 'Erste Rechnung' : 'Monatspreis'"
                        :total-value='contract.firstInvoiceTotal'
                    />

                    <div v-if='contract.customerComment' class='rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600'>
                        <span class='font-medium text-slate-700'>Dein Kommentar:</span>
                        {{ contract.customerComment }}
                    </div>
                    <div v-if='contract.adminComment' class='rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-800'>
                        <span class='font-medium'>Team-Hinweis:</span>
                        {{ contract.adminComment }}
                    </div>
                </div>

                <div v-if="contract.state === 'PENDING_CUSTOMER_APPROVAL'" class='mt-4 flex flex-wrap gap-2'>
                    <UButton
                        color='green'
                        :loading='busyId === contract.id'
                        label='Preis akzeptieren'
                        @click="emit('accept', contract)"
                    />
                    <UButton
                        color='red'
                        variant='soft'
                        :loading='busyId === contract.id'
                        label='Ablehnen'
                        @click="emit('decline', contract)"
                    />
                    <UButton
                        variant='soft'
                        :loading='busyId === contract.id'
                        label='Kommentar anpassen'
                        @click="emit('resubmit', contract)"
                    />
                </div>
            </div>
        </div>
    </UCard>
</div>
</template>

<script setup lang="ts">
import type { Contract } from '~/types';

defineProps<{
    loading: boolean,
    contracts: Contract[],
    busyId?: string | null,
}>();

const emit = defineEmits<{
    (event: 'accept', value: Contract): void,
    (event: 'decline', value: Contract): void,
    (event: 'resubmit', value: Contract): void,
}>();

const { formatDate, contractStateLabel, contractStateColor, formatContractMonthlyPrice, formatMoney } = useHelpers();
</script>
