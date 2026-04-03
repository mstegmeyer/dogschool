<template>
  <div class="hidden md:block">
    <UTable
      v-model:sort="sortModel"
      :columns="columns"
      :rows="courses"
      sort-mode="manual"
    >
      <template #type-data="{ row }">
        <div>
          <span class="font-medium">{{ row.type?.name || '–' }}</span>
          <UBadge class="ml-2" size="xs" variant="soft" color="gray">{{ row.type?.code }}</UBadge>
        </div>
      </template>
      <template #dayOfWeek-data="{ row }">
        {{ dayName(row.dayOfWeek) }}
      </template>
      <template #time-data="{ row }">
        {{ row.startTime }} – {{ row.endTime }}
      </template>
      <template #level-data="{ row }">
        {{ levelLabel(row.level) }}
      </template>
      <template #trainer-data="{ row }">
        <span :class="row.trainer ? 'font-medium text-slate-700' : 'text-slate-400'">
          {{ row.trainer?.fullName || 'Nicht zugewiesen' }}
        </span>
      </template>
      <template #subscribers-data="{ row }">
        <UTooltip
          v-if="row.subscriberCount > 0"
          :text="row.subscribers.map((subscriber: { name: string }) => subscriber.name).join(', ')"
        >
          <span class="cursor-default font-medium text-komm-600">{{ row.subscriberCount }}</span>
        </UTooltip>
        <span v-else class="text-slate-400">0</span>
      </template>
      <template #archived-data="{ row }">
        <UBadge :color="row.archived ? 'gray' : 'primary'" variant="soft" size="xs">
          {{ row.archived ? 'Archiviert' : 'Aktiv' }}
        </UBadge>
      </template>
      <template #actions-data="{ row }">
        <UDropdown :items="getRowActions(row)">
          <UButton variant="ghost" icon="i-heroicons-ellipsis-vertical" size="xs" />
        </UDropdown>
      </template>
    </UTable>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Course } from '~/types'
import type { CourseTableSort } from '../types'

const props = defineProps<{
  courses: Course[]
  sort: CourseTableSort
}>()

const emit = defineEmits<{
  (event: 'update:sort', value: CourseTableSort): void
  (event: 'edit', course: Course): void
  (event: 'toggle-archive', course: Course): void
}>()

const { dayName, levelLabel } = useHelpers()

const columns = [
  { key: 'type', label: 'Kurstyp' },
  { key: 'dayOfWeek', label: 'Tag', sortable: true },
  { key: 'time', label: 'Uhrzeit' },
  { key: 'level', label: 'Stufe' },
  { key: 'trainer', label: 'Trainer' },
  { key: 'subscribers', label: 'Abonnenten' },
  { key: 'archived', label: 'Status', sortable: true },
  { key: 'actions', label: '' },
]

const sortModel = computed({
  get: () => props.sort,
  set: (value: CourseTableSort) => emit('update:sort', value),
})

function getRowActions(course: Course) {
  return [[
    {
      label: 'Bearbeiten',
      icon: 'i-heroicons-pencil',
      click: () => emit('edit', course),
    },
    {
      label: course.archived ? 'Reaktivieren' : 'Archivieren',
      icon: course.archived ? 'i-heroicons-arrow-path' : 'i-heroicons-archive-box',
      click: () => emit('toggle-archive', course),
    },
  ]]
}
</script>
