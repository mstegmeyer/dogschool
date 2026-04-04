<template>
<UCard>
    <template #header>
        <h3 class='font-semibold text-slate-800'>
            Guthaben
        </h3>
    </template>
    <CreditBalanceSummary
        :loading='false'
        :balance='balance'
        label='Credits'
        compact
    />
    <UDivider class='my-3' />
    <form class='space-y-3' @submit.prevent="emit('submit')">
        <UFormGroup label='Korrektur' :error='amountError'>
            <UInput
                :model-value="adjustAmount ?? ''"
                type='number'
                placeholder='z.B. 5 oder -2'
                @update:model-value="emit('update:adjustAmount', normalizeAmount($event)); emit('clear-field-error', 'amount')"
            />
        </UFormGroup>
        <UFormGroup label='Beschreibung' :error='descriptionError'>
            <UInput
                :model-value='adjustDescription'
                placeholder='Grund der Korrektur'
                @update:model-value="emit('update:adjustDescription', `${$event ?? ''}`); emit('clear-field-error', 'description')"
            />
        </UFormGroup>
        <UAlert
            v-if='formError'
            color='red'
            variant='soft'
            :title='formError'
            icon='i-heroicons-exclamation-triangle'
        />
        <UButton
            type='submit'
            size='sm'
            block
            :loading='saving'
        >
            Guthaben anpassen
        </UButton>
    </form>
</UCard>
</template>

<script setup lang="ts">
defineProps<{
    balance: number,
    adjustAmount: number | null,
    adjustDescription: string,
    amountError?: string,
    descriptionError?: string,
    formError: string,
    saving: boolean,
}>();

const emit = defineEmits<{
    (event: 'update:adjustAmount', value: number | null): void,
    (event: 'update:adjustDescription', value: string): void,
    (event: 'clear-field-error', value: string): void,
    (event: 'submit'): void,
}>();

function normalizeAmount(value: string | number | null | undefined): number | null {
    if (value === '' || value === null || value === undefined) {
        return null;
    }

    const amount = Number(value);
    return Number.isFinite(amount) ? amount : null;
}
</script>
