<template>
  <UModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid="add-dog-modal">
      <template #header>
        <h3 class="font-semibold text-slate-800">Hund hinzufügen</h3>
      </template>
      <form class="space-y-4" @submit.prevent="emit('submit')">
        <UFormGroup label="Name" :error="fieldErrors.name">
          <UInput v-model="form.name" placeholder="z.B. Bella" required @update:model-value="emit('clear-field-error', 'name')" />
        </UFormGroup>
        <UFormGroup label="Rasse" :error="fieldErrors.race">
          <UInput v-model="form.race" placeholder="z.B. Golden Retriever" />
        </UFormGroup>
        <div class="grid grid-cols-2 gap-4">
          <UFormGroup label="Geschlecht" :error="fieldErrors.gender">
            <USelectMenu
              v-model="form.gender"
              :options="genderOptions"
              value-attribute="value"
              placeholder="Auswählen"
              @update:model-value="emit('clear-field-error', 'gender')"
            />
          </UFormGroup>
          <UFormGroup label="Farbe" :error="fieldErrors.color">
            <UInput v-model="form.color" placeholder="z.B. Golden" />
          </UFormGroup>
        </div>
        <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" label="Abbrechen" @click="emit('cancel')" />
          <UButton type="submit" :loading="saving" label="Hinzufügen" />
        </div>
      </form>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: boolean
  form: { name: string; race: string; gender: string; color: string }
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

const genderOptions = [
  { label: 'Rüde', value: 'male' },
  { label: 'Hündin', value: 'female' },
]
</script>
