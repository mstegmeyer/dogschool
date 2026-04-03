<template>
  <UModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid="notification-form-modal">
      <template #header>
        <h3 class="font-semibold text-slate-800">
          {{ editing ? 'Mitteilung bearbeiten' : 'Neue Mitteilung' }}
        </h3>
      </template>
      <form class="space-y-4" @submit.prevent="emit('submit')">
        <UFormGroup label="Reichweite" :error="fieldErrors.courseIds">
          <div class="mb-2 flex items-center gap-3">
            <UToggle data-testid="notification-global-toggle" v-model="form.isGlobal" />
            <span class="text-sm text-slate-600">
              {{ form.isGlobal ? 'Globale Mitteilung (alle Kunden)' : 'Bestimmte Kurse auswählen' }}
            </span>
          </div>
          <USelectMenu
            data-testid="notification-course-select"
            v-if="!form.isGlobal"
            v-model="form.courseIds"
            :options="courseOptions"
            value-attribute="value"
            placeholder="Kurse auswählen…"
            multiple
            @update:model-value="emit('clear-field-error', 'courseIds')"
          />
        </UFormGroup>
        <UFormGroup label="Titel" :error="fieldErrors.title">
          <UInput data-testid="notification-title" v-model="form.title" placeholder="Betreff" required @update:model-value="emit('clear-field-error', 'title')" />
        </UFormGroup>
        <UFormGroup label="Nachricht" :error="fieldErrors.message">
          <UTextarea data-testid="notification-message" v-model="form.message" placeholder="Nachricht an die Kursteilnehmer…" :rows="4" required @update:model-value="emit('clear-field-error', 'message')" />
        </UFormGroup>
        <UFormGroup label="Angepinnt bis" hint="Optional – Mitteilung wird bis zu diesem Datum oben angezeigt" :error="fieldErrors.pinnedUntil">
          <UInput data-testid="notification-pinned-until" v-model="form.pinnedUntil" type="date" @update:model-value="emit('clear-field-error', 'pinnedUntil')" />
        </UFormGroup>
        <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" label="Abbrechen" @click="emit('cancel')" />
          <UButton data-testid="save-notification" type="submit" :loading="saving" label="Speichern" />
        </div>
      </form>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: boolean
  editing: boolean
  form: { courseIds: string[]; title: string; message: string; isGlobal: boolean; pinnedUntil: string }
  courseOptions: Array<{ label: string; value: string }>
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
