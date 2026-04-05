<template>
<UModal :model-value='modelValue' @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid='request-hotel-booking-modal'>
        <template #header>
            <h3 class='font-semibold text-slate-800'>
                Hotelbuchung anfragen
            </h3>
        </template>
        <form class='space-y-4' @submit.prevent="emit('submit')">
            <UFormGroup label='Hund' :error='fieldErrors.dogId'>
                <USelectMenu
                    v-model='form.dogId'
                    data-testid='request-hotel-booking-dog'
                    :options='dogOptions'
                    value-attribute='value'
                    placeholder='Hund auswählen'
                    @update:model-value="emit('clear-field-error', 'dogId')"
                />
            </UFormGroup>

            <UAlert
                v-if='selectedDogName'
                color='amber'
                variant='soft'
                icon='i-heroicons-information-circle'
                :title='`Bitte die aktuelle Schulterhöhe von ${selectedDogName} prüfen.`'
                :description='`Gespeichert sind derzeit ${storedShoulderHeightCm} cm. Gerade bei jungen Hunden sollte der Wert vor jeder Buchung aktualisiert werden.`'
            />

            <UFormGroup
                v-if='selectedDogName'
                label='Aktuelle Schulterhöhe / Widerristhöhe (cm)'
                :error='fieldErrors.currentShoulderHeightCm'
            >
                <UInput
                    v-model.number='form.currentShoulderHeightCm'
                    data-testid='request-hotel-booking-height'
                    type='number'
                    min='1'
                    placeholder='z.B. 48'
                    @update:model-value="emit('clear-field-error', 'currentShoulderHeightCm')"
                />
            </UFormGroup>

            <div class='grid gap-4 sm:grid-cols-2'>
                <UFormGroup label='Beginn' :error='fieldErrors.startAt' help='Startzeit nur zwischen 06:00 und 22:00 Uhr.'>
                    <UInput
                        v-model='form.startAt'
                        data-testid='request-hotel-booking-start-at'
                        type='datetime-local'
                        @update:model-value="emit('clear-field-error', 'startAt')"
                    />
                </UFormGroup>
                <UFormGroup label='Ende' :error='fieldErrors.endAt' help='Endzeit nur zwischen 06:00 und 22:00 Uhr.'>
                    <UInput
                        v-model='form.endAt'
                        data-testid='request-hotel-booking-end-at'
                        type='datetime-local'
                        @update:model-value="emit('clear-field-error', 'endAt')"
                    />
                </UFormGroup>
            </div>

            <UFormGroup label='Zusatzoptionen'>
                <UCheckbox
                    v-model='form.includesTravelProtection'
                    label='Reiseschutz hinzufügen'
                />
            </UFormGroup>

            <UFormGroup label='Kommentar für Zusatzwünsche'>
                <UTextarea
                    v-model='form.customerComment'
                    :rows='4'
                    placeholder='Zum Beispiel Einzelzimmer, Läufigkeit oder besondere Hinweise für das Team.'
                />
            </UFormGroup>

            <div v-if='previewLoading' class='rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500'>
                Preisvorschau wird berechnet…
            </div>
            <div v-else-if='preview' class='space-y-3'>
                <UAlert
                    color='blue'
                    variant='soft'
                    icon='i-heroicons-banknotes'
                    :title='`${hotelPricingKindLabel(preview.pricingKind)} · ${preview.billableDays} Kalendertag(e)`'
                    :description='preview.includesTravelProtection ? "Reiseschutz ist in der Vorschau enthalten." : "Reiseschutz ist nicht enthalten."'
                />
                <PricingBreakdown
                    :snapshot='preview.snapshot'
                    title='Voraussichtlicher Preis'
                    total-label='Gesamt'
                    :total-value='preview.quotedTotalPrice'
                />
            </div>

            <UAlert
                v-if='formError'
                color='red'
                variant='soft'
                :title='formError'
                icon='i-heroicons-exclamation-triangle'
            />

            <div class='flex justify-end gap-2'>
                <UButton variant='ghost' label='Abbrechen' @click="emit('cancel')" />
                <UButton
                    data-testid='submit-hotel-booking-request'
                    type='submit'
                    :loading='saving'
                    label='Anfragen'
                />
            </div>
        </form>
    </UCard>
</UModal>
</template>

<script setup lang="ts">
import type { HotelBookingQuotePreview } from '~/types';

defineProps<{
    modelValue: boolean,
    dogOptions: Array<{ label: string; value: string }>,
    selectedDogName: string,
    storedShoulderHeightCm: number,
    form: {
        dogId: string,
        startAt: string,
        endAt: string,
        currentShoulderHeightCm: number,
        includesTravelProtection: boolean,
        customerComment: string,
    },
    fieldErrors: Record<string, string>,
    formError: string,
    saving: boolean,
    previewLoading: boolean,
    preview: HotelBookingQuotePreview | null,
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
    (event: 'submit'): void,
    (event: 'cancel'): void,
    (event: 'clear-field-error', value: string): void,
}>();

const { hotelPricingKindLabel } = useHelpers();
</script>
