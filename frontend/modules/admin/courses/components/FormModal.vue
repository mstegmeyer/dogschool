<template>
  <UModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid="course-form-modal">
      <template #header>
        <h3 class="font-semibold text-slate-800">{{ editingCourse ? 'Kurs bearbeiten' : 'Neuer Kurs' }}</h3>
      </template>
      <form class="space-y-4" @submit.prevent="emit('submit')">
        <UFormGroup label="Kurstyp (Code)" :error="fieldErrors.typeCode">
          <UInput
            data-testid="course-form-type-code"
            v-model="form.typeCode"
            placeholder="z.B. MH, JUHU, AGI"
            required
            @update:model-value="emit('clear-field-error', 'typeCode')"
          />
        </UFormGroup>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <UFormGroup label="Wochentag" :error="fieldErrors.dayOfWeek">
            <USelectMenu
              data-testid="course-form-day-of-week"
              v-model="form.dayOfWeek"
              :options="dayOptions"
              value-attribute="value"
              @update:model-value="emit('clear-field-error', 'dayOfWeek')"
            />
          </UFormGroup>
          <UFormGroup label="Stufe (0-4)" :error="fieldErrors.level">
            <UInput
              data-testid="course-form-level"
              v-model.number="form.level"
              type="number"
              min="0"
              max="4"
              @update:model-value="emit('clear-field-error', 'level')"
            />
          </UFormGroup>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <UFormGroup label="Startzeit" :error="fieldErrors.startTime">
            <UInput
              data-testid="course-form-start-time"
              v-model="form.startTime"
              type="time"
              required
              @update:model-value="emit('clear-field-error', 'startTime')"
            />
          </UFormGroup>
          <UFormGroup label="Endzeit" :error="fieldErrors.endTime">
            <UInput
              data-testid="course-form-end-time"
              v-model="form.endTime"
              type="time"
              required
              @update:model-value="emit('clear-field-error', 'endTime')"
            />
          </UFormGroup>
        </div>
        <UFormGroup label="Trainer" :error="fieldErrors.trainerId">
          <USelectMenu
            data-testid="course-form-trainer"
            v-model="form.trainerId"
            :options="trainerOptions"
            value-attribute="value"
            placeholder="Trainer auswählen"
            @update:model-value="emit('clear-field-error', 'trainerId')"
          />
        </UFormGroup>
        <div
          v-if="showScheduleHint"
          class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
        >
          {{ scheduleHintText }}
        </div>
        <UFormGroup label="Kommentar" :error="fieldErrors.comment">
          <UTextarea
            data-testid="course-form-comment"
            v-model="form.comment"
            placeholder="Optionaler Kommentar"
            @update:model-value="emit('clear-field-error', 'comment')"
          />
        </UFormGroup>
        <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />
        <div class="flex justify-end gap-2">
          <UButton variant="ghost" label="Abbrechen" @click="emit('cancel')" />
          <UButton data-testid="save-course" type="submit" :loading="saving" label="Speichern" />
        </div>
      </form>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
import type { CourseFormState } from '../types'

defineProps<{
  modelValue: boolean
  editingCourse: boolean
  form: CourseFormState
  dayOptions: Array<{ label: string; value: number }>
  trainerOptions: Array<{ label: string; value: string }>
  showScheduleHint: boolean
  scheduleHintText: string
  formError: string
  fieldErrors: Record<string, string>
  saving: boolean
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
  (event: 'submit'): void
  (event: 'cancel'): void
  (event: 'clear-field-error', field: string): void
}>()
</script>
