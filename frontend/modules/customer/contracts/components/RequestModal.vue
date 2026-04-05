<template>
<UModal :model-value='modelValue' @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid='request-contract-modal'>
        <template #header>
            <h3 class='font-semibold text-slate-800'>
                Vertrag anfragen
            </h3>
        </template>
        <form class='space-y-4' @submit.prevent="emit('submit')">
            <UFormGroup label='Hund' :error='fieldErrors.dogId'>
                <USelectMenu
                    v-model='form.dogId'
                    data-testid='request-contract-dog'
                    :options='dogOptions'
                    value-attribute='value'
                    placeholder='Hund auswählen'
                    @update:model-value="emit('clear-field-error', 'dogId')"
                />
            </UFormGroup>
            <UFormGroup label='Kurse pro Woche' :error='fieldErrors.coursesPerWeek'>
                <UInput
                    v-model.number='form.coursesPerWeek'
                    type='number'
                    min='1'
                    max='7'
                    @update:model-value="emit('clear-field-error', 'coursesPerWeek')"
                />
            </UFormGroup>
            <UFormGroup label='Beginn' help='Nur der erste Tag eines Monats ist möglich.' :error='fieldErrors.startDate'>
                <UInput
                    v-model='form.startDate'
                    data-testid='request-contract-start-date'
                    type='date'
                    @change="emit('normalize-start-date')"
                    @update:model-value="emit('clear-field-error', 'startDate')"
                />
            </UFormGroup>
            <UFormGroup label='Kommentar für Zusatzwünsche'>
                <UTextarea
                    v-model='form.customerComment'
                    :rows='4'
                    placeholder='Zum Beispiel Wunschzeiten, besondere Betreuung oder organisatorische Hinweise.'
                />
            </UFormGroup>
            <div v-if='previewLoading' class='rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500'>
                Preisvorschau wird berechnet…
            </div>
            <PricingBreakdown
                v-else-if='preview'
                :snapshot='preview.snapshot'
                title='Voraussichtlicher Preis'
                total-label='Erste Rechnung'
                :total-value='preview.firstInvoiceTotal'
            />
            <UAlert
                v-if='formError'
                color='red'
                variant='soft'
                :title='formError'
                icon='i-heroicons-exclamation-triangle'
            />
            <div class='flex justify-end gap-2'>
                <UButton variant='ghost' label='Abbrechen' @click="emit('cancel')" />
                <UButton type='submit' :loading='saving' label='Anfragen' />
            </div>
        </form>
    </UCard>
</UModal>
</template>

<script setup lang="ts">
import type { ContractQuotePreview } from '~/types';

defineProps<{
    modelValue: boolean,
    dogOptions: Array<{ label: string; value: string }>,
    form: { dogId: string; coursesPerWeek: number; startDate: string; customerComment: string },
    fieldErrors: Record<string, string>,
    formError: string,
    saving: boolean,
    previewLoading: boolean,
    preview: ContractQuotePreview | null,
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
    (event: 'submit'): void,
    (event: 'cancel'): void,
    (event: 'normalize-start-date'): void,
    (event: 'clear-field-error', value: string): void,
}>();
</script>
