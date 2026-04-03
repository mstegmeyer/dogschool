<template>
  <UCard :ui="{ body: { padding: 'p-0 sm:p-0' } }">
    <div v-if="loading" class="p-4">
      <AppSkeletonCollection
        :mobile-cards="4"
        :desktop-rows="6"
        :desktop-columns="4"
        :meta-columns="0"
        :content-lines="2"
        :show-actions="true"
      />
    </div>
    <div v-else-if="courseTypes.length === 0" class="py-8 text-center text-sm text-slate-400">
      Noch keine Kursarten erstellt
    </div>
    <template v-else>
      <div class="space-y-3 p-4 md:hidden">
        <div
          v-for="courseType in courseTypes"
          :key="courseType.id"
          class="rounded-lg border border-slate-200 bg-white p-4"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="font-medium text-slate-800">{{ courseType.name }}</p>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-slate-700">
                  {{ courseType.code }}
                </span>
                <span
                  class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium"
                  :class="recurrenceBadgeClass(courseType.recurrenceKind)"
                >
                  {{ recurrenceLabel(courseType.recurrenceKind) }}
                </span>
              </div>
            </div>
            <div class="flex gap-1">
              <UButton icon="i-heroicons-pencil" size="xs" variant="ghost" @click="emit('edit', courseType)" />
              <UButton icon="i-heroicons-trash" size="xs" variant="ghost" color="red" @click="emit('delete', courseType)" />
            </div>
          </div>
        </div>
      </div>
      <div class="hidden overflow-x-auto md:block">
        <UTable
          :columns="columns"
          :rows="courseTypes"
          :loading="loading"
          :ui="{
            th: { base: 'text-left text-xs font-semibold text-slate-500 py-2 px-3' },
            td: { base: 'py-1.5 px-3 align-top text-sm' },
          }"
        >
          <template #code-data="{ row }">
            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-slate-700">
              {{ row.code }}
            </span>
          </template>
          <template #name-data="{ row }">
            <span class="font-medium text-slate-800">{{ row.name }}</span>
          </template>
          <template #recurrenceKind-data="{ row }">
            <span
              class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium"
              :class="recurrenceBadgeClass(row.recurrenceKind)"
            >
              {{ recurrenceLabel(row.recurrenceKind) }}
            </span>
          </template>
          <template #actions-data="{ row }">
            <div class="flex shrink-0 justify-end gap-0.5">
              <UButton icon="i-heroicons-pencil" size="xs" variant="ghost" @click="emit('edit', row)" />
              <UButton icon="i-heroicons-trash" size="xs" variant="ghost" color="red" @click="emit('delete', row)" />
            </div>
          </template>
        </UTable>
      </div>
    </template>
  </UCard>
</template>

<script setup lang="ts">
import type { CourseType } from '~/types'

defineProps<{
  loading: boolean
  courseTypes: CourseType[]
  columns: Array<{ key: string; label: string }>
}>()

const emit = defineEmits<{
  (event: 'edit', value: CourseType): void
  (event: 'delete', value: CourseType): void
}>()

const recurrenceOptions = [
  { label: 'Wiederkehrend', value: 'RECURRING' },
  { label: 'Einmalig', value: 'ONE_TIME' },
  { label: 'Drop-In', value: 'DROP_IN' },
]

function recurrenceLabel(kind: string): string {
  return recurrenceOptions.find(option => option.value === kind)?.label ?? kind
}

function recurrenceBadgeClass(kind: string): string {
  switch (kind) {
    case 'RECURRING':
      return 'text-emerald-700 bg-emerald-50'
    case 'ONE_TIME':
      return 'text-blue-700 bg-blue-50'
    case 'DROP_IN':
      return 'text-amber-700 bg-amber-50'
    default:
      return 'text-slate-600 bg-slate-100'
  }
}
</script>
