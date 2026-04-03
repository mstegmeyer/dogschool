<template>
  <UModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <UCard>
      <template #header>
        <h3 class="font-semibold text-slate-800">
          {{ editing ? 'Kursart bearbeiten' : 'Neue Kursart' }}
        </h3>
      </template>
      <form class="space-y-4" @submit.prevent="emit('submit')">
        <UFormGroup label="Kürzel" :error="fieldErrors.code">
          <UInput v-model="form.code" placeholder="z.B. AGI" maxlength="20" required @update:model-value="emit('clear-field-error', 'code')" />
        </UFormGroup>
        <UFormGroup label="Name" :error="fieldErrors.name">
          <UInput v-model="form.name" placeholder="z.B. Agility" required @update:model-value="emit('clear-field-error', 'name')" />
        </UFormGroup>
        <UFormGroup label="Wiederholungsart" :error="fieldErrors.recurrenceKind">
          <USelectMenu
            v-model="form.recurrenceKind"
            :options="recurrenceOptions"
            value-attribute="value"
            @update:model-value="emit('clear-field-error', 'recurrenceKind')"
          />
        </UFormGroup>
        <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" label="Abbrechen" @click="emit('cancel')" />
          <UButton type="submit" :loading="saving" label="Speichern" />
        </div>
      </form>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: boolean
  editing: boolean
  form: { code: string; name: string; recurrenceKind: string }
  recurrenceOptions: Array<{ label: string; value: string }>
  fieldErrors: Record<string, string>
  formError: string
  saving: boolean
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
  (event: 'submit'): void
  (event: 'cancel'): void
  (event: 'clear-field-error', value: string): void
}>()
</script>
