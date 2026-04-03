<template>
  <div>
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <h3 class="font-semibold text-slate-800">{{ notification.title }}</h3>
          <span
            v-if="notification.isPinned"
            class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 shrink-0"
          >
            <UIcon name="i-heroicons-map-pin" class="h-3 w-3" />
            Angepinnt
          </span>
        </div>
      </div>
      <span class="shrink-0 text-xs text-slate-400 tabular-nums">{{ formatDateTime(notification.createdAt) }}</span>
    </div>
    <div
      v-if="notification.isGlobal"
      class="mt-2 flex w-full items-center gap-2 rounded-md border border-amber-100 bg-amber-50 px-3 py-2"
    >
      <UIcon name="i-heroicons-globe-alt" class="h-4 w-4 text-amber-600 shrink-0" />
      <span class="text-sm font-medium text-amber-800">Alle Kurse</span>
    </div>
    <div
      v-else-if="notification.courses.length > 0"
      class="mt-2 flex w-full flex-wrap items-center gap-2 rounded-md border border-komm-100 bg-komm-50 px-3 py-2"
    >
      <span class="shrink-0 text-xs font-semibold uppercase tracking-wide text-komm-800">Kurs</span>
      <span
        v-for="(course, index) in visibleCourses"
        :key="course.id"
        class="text-sm font-medium text-komm-900"
      >
        {{ formatNotificationCourse(course) }}<span v-if="index < visibleCourses.length - 1">,</span>
      </span>
      <span
        v-if="notification.courses.length > maxVisibleCourses"
        class="text-xs font-medium text-komm-700"
      >
        (+&nbsp;{{ notification.courses.length - maxVisibleCourses }} weitere)
      </span>
    </div>
    <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ notification.message }}</p>
    <p class="mt-2 text-xs text-slate-400">von {{ notification.authorName || 'Team' }}</p>
  </div>
</template>

<script setup lang="ts">
import type { Notification as AppNotification } from '~/types'

const props = withDefaults(defineProps<{
  notification: AppNotification
  maxVisibleCourses?: number
}>(), {
  maxVisibleCourses: 3,
})

const { formatDateTime, formatNotificationCourse } = useHelpers()

const visibleCourses = computed(() => props.notification.courses.slice(0, props.maxVisibleCourses))
</script>
