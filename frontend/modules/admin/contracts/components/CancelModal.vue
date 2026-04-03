<template>
  <UModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid="contract-cancel-modal">
      <template #header>
        <h3 class="font-semibold text-slate-800">Vertrag kündigen?</h3>
      </template>
      <div class="space-y-4">
        <p class="text-sm text-slate-600">
          Wähle das Enddatum, bis zu dem für
          <strong>{{ contract?.dogName }}</strong>
          ({{ contract?.customerName }}) noch Credits gutgeschrieben werden sollen.
        </p>
        <UFormGroup label="Vertragsende" help="Nur der letzte Tag eines Monats ist möglich." :error="endDateError">
          <UInput
            data-testid="contract-end-date"
            :model-value="endDate"
            type="date"
            :min="contract?.startDate ?? undefined"
            @change="emit('normalize-end-date')"
            @update:model-value="emit('update:endDate', $event); emit('clear-end-date-error')"
          />
        </UFormGroup>
        <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" label="Abbrechen" @click="emit('cancel')" />
          <UButton data-testid="confirm-contract-cancel" color="red" label="Kündigen" :loading="saving" @click="emit('submit')" />
        </div>
      </template>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
import type { Contract } from '~/types'

defineProps<{
  modelValue: boolean
  contract: Contract | null
  endDate: string
  endDateError?: string
  formError: string
  saving: boolean
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
  (event: 'update:endDate', value: string): void
  (event: 'normalize-end-date'): void
  (event: 'clear-end-date-error'): void
  (event: 'cancel'): void
  (event: 'submit'): void
}>()
</script>
