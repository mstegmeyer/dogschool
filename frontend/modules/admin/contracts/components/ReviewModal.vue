<template>
<UModal :model-value='modelValue' @update:model-value="emit('update:modelValue', $event)">
    <UCard v-if='contract' data-testid='contract-review-modal'>
        <template #header>
            <div class='flex items-start justify-between gap-3'>
                <div>
                    <h3 class='font-semibold text-slate-800'>
                        Vertrag prüfen
                    </h3>
                    <p class='text-sm text-slate-500'>
                        {{ contract.dogName || 'Hund' }} · {{ contract.customerName || 'Kunde' }}
                    </p>
                </div>
                <UBadge :color='contractStateColor(contract.state)' variant='soft'>
                    {{ contractStateLabel(contract.state) }}
                </UBadge>
            </div>
        </template>

        <div class='space-y-4'>
            <PricingBreakdown
                :snapshot='contract.pricingSnapshot'
                title='Aktuelle Preisübersicht'
                total-label='Erste Rechnung'
                :total-value='firstInvoiceTotal'
            />

            <div v-if='contract.customerComment' class='rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600'>
                <span class='font-medium text-slate-700'>Kundenkommentar:</span>
                {{ contract.customerComment }}
            </div>

            <UFormGroup label='Monatspreis'>
                <UInput
                    :model-value='price'
                    type='number'
                    step='0.01'
                    @update:model-value="emit('update:price', String($event))"
                />
                <template #hint>
                    Automatischer Vorschlag: {{ formatContractMonthlyPrice(contract.quotedMonthlyPrice, contract.type) }}
                </template>
            </UFormGroup>

            <UFormGroup label='Anmeldegebühr'>
                <UInput
                    :model-value='registrationFee'
                    type='number'
                    step='0.01'
                    @update:model-value="emit('update:registrationFee', String($event))"
                />
                <template #hint>
                    Automatischer Vorschlag: {{ formatMoney(suggestedRegistrationFee) }}
                </template>
            </UFormGroup>

            <UFormGroup label='Admin-Kommentar'>
                <UTextarea
                    :model-value='adminComment'
                    :rows='4'
                    placeholder='Begründung für Preisänderungen oder Hinweise zur Anfrage.'
                    @update:model-value="emit('update:adminComment', $event)"
                />
            </UFormGroup>

            <div class='flex justify-end gap-2'>
                <UButton variant='ghost' label='Schließen' @click="emit('update:modelValue', false)" />
                <UButton
                    data-testid='decline-contract-review'
                    color='red'
                    variant='soft'
                    :loading='declining'
                    label='Ablehnen'
                    @click="emit('decline')"
                />
                <UButton
                    data-testid='approve-contract-review'
                    color='green'
                    :loading='approving'
                    label='Bestätigen'
                    @click="emit('approve')"
                />
            </div>
        </div>
    </UCard>
</UModal>
</template>

<script setup lang="ts">
import type { Contract } from '~/types';

const props = defineProps<{
    modelValue: boolean,
    contract: Contract | null,
    price: string,
    registrationFee: string,
    adminComment: string,
    approving: boolean,
    declining: boolean,
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
    (event: 'update:price', value: string): void,
    (event: 'update:registrationFee', value: string): void,
    (event: 'update:adminComment', value: string): void,
    (event: 'approve'): void,
    (event: 'decline'): void,
}>();

const { contractStateLabel, contractStateColor, formatContractMonthlyPrice, formatMoney } = useHelpers();

const suggestedRegistrationFee = computed(() => {
    if (!props.contract) {
        return '0.00';
    }

    const snapshotRegistrationFee = props.contract.pricingSnapshot.quotedRegistrationFee;

    return typeof snapshotRegistrationFee === 'string'
        ? snapshotRegistrationFee
        : props.contract.registrationFee;
});

const firstInvoiceTotal = computed(() => formatAmount(
    amountToCents(props.price || props.contract?.price || '0.00')
    + amountToCents(props.registrationFee || props.contract?.registrationFee || '0.00'),
));

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
