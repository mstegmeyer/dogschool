<template>
  <div class="space-y-3 md:hidden">
    <div
      v-for="course in courses"
      :key="course.id"
      class="rounded-lg border border-slate-200 bg-white p-4"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="font-medium text-slate-800">{{ course.type?.name || '–' }}</p>
          <p class="mt-1 text-sm text-slate-500">{{ dayName(course.dayOfWeek) }} · {{ course.startTime }} – {{ course.endTime }}</p>
        </div>
        <div class="flex flex-col items-end gap-2">
          <UBadge size="xs" variant="soft" color="gray">{{ course.type?.code }}</UBadge>
          <UBadge :color="course.archived ? 'gray' : 'primary'" variant="soft" size="xs">
            {{ course.archived ? 'Archiviert' : 'Aktiv' }}
          </UBadge>
        </div>
      </div>
      <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
        <div>
          <p class="text-slate-400">Stufe</p>
          <p class="font-medium text-slate-700">{{ levelLabel(course.level) }}</p>
        </div>
        <div>
          <p class="text-slate-400">Abonnenten</p>
          <p class="font-medium text-slate-700">{{ course.subscriberCount }}</p>
        </div>
      </div>
      <div class="mt-3 text-xs">
        <p class="text-slate-400">Trainer</p>
        <p class="font-medium text-slate-700">{{ course.trainer?.fullName || 'Nicht zugewiesen' }}</p>
      </div>
      <p v-if="course.comment" class="mt-3 text-sm text-slate-600">{{ course.comment }}</p>
      <div class="mt-4 grid grid-cols-2 gap-2">
        <UButton size="sm" variant="soft" label="Bearbeiten" @click="emit('edit', course)" />
        <UButton
          size="sm"
          :color="course.archived ? 'primary' : 'gray'"
          variant="ghost"
          :label="course.archived ? 'Reaktivieren' : 'Archivieren'"
          @click="emit('toggle-archive', course)"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Course } from '~/types'

defineProps<{
  courses: Course[]
}>()

const emit = defineEmits<{
  (event: 'edit', course: Course): void
  (event: 'toggle-archive', course: Course): void
}>()

const { dayName, levelLabel } = useHelpers()
</script>
