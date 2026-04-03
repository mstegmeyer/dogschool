<template>
  <UModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid="request-contract-modal">
      <template #header>
        <h3 class="font-semibold text-slate-800">Vertrag anfragen</h3>
      </template>
      <form class="space-y-4" @submit.prevent="emit('submit')">
        <UFormGroup label="Hund" :error="fieldErrors.dogId">
          <USelectMenu
            data-testid="request-contract-dog"
            v-model="form.dogId"
            :options="dogOptions"
            value-attribute="value"
            placeholder="Hund auswählen"
            @update:model-value="emit('clear-field-error', 'dogId')"
          />
        </UFormGroup>
        <UFormGroup label="Kurse pro Woche" :error="fieldErrors.coursesPerWeek">
          <UInput v-model.number="form.coursesPerWeek" type="number" min="1" max="7" @update:model-value="emit('clear-field-error', 'coursesPerWeek')" />
        </UFormGroup>
        <UFormGroup label="Beginn" help="Nur der erste Tag eines Monats ist möglich." :error="fieldErrors.startDate">
          <UInput
            data-testid="request-contract-start-date"
            v-model="form.startDate"
            type="date"
            @change="emit('normalize-start-date')"
            @update:model-value="emit('clear-field-error', 'startDate')"
          />
        </UFormGroup>
        <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" label="Abbrechen" @click="emit('cancel')" />
          <UButton type="submit" :loading="saving" label="Anfragen" />
        </div>
      </form>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: boolean
  dogOptions: Array<{ label: string; value: string }>
  form: { dogId: string; coursesPerWeek: number; startDate: string }
  fieldErrors: Record<string, string>
  formError: string
  saving: boolean
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
  (event: 'submit'): void
  (event: 'cancel'): void
  (event: 'normalize-start-date'): void
  (event: 'clear-field-error', value: string): void
}>()
</script>
