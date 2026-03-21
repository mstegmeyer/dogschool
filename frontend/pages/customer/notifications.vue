<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Mitteilungen</h1>

    <div v-if="notifications.length === 0 && !loading" class="text-center py-12">
      <UIcon name="i-heroicons-bell" class="w-12 h-12 text-slate-300 mx-auto mb-3" />
      <p class="text-slate-500">Keine Mitteilungen vorhanden.</p>
      <p class="text-xs text-slate-400 mt-1">Abonniere Kurse, um Mitteilungen zu erhalten.</p>
    </div>

    <div v-else class="space-y-3">
      <UCard v-for="n in notifications" :key="n.id">
          <div>
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <h3 class="font-semibold text-slate-800">{{ n.title }}</h3>
                <div
                  v-if="n.isGlobal"
                  class="mt-2 flex items-center gap-2 rounded-md bg-amber-50 border border-amber-100 px-3 py-2"
                >
                  <UIcon name="i-heroicons-globe-alt" class="w-4 h-4 text-amber-600 shrink-0" />
                  <span class="text-sm font-medium text-amber-800">Alle Kurse</span>
                </div>
                <div
                  v-else-if="n.courses.length > 0"
                  class="mt-2 flex flex-wrap items-center gap-2 rounded-md bg-green-50 border border-green-100 px-3 py-2"
                >
                  <span class="text-xs font-semibold uppercase tracking-wide text-green-800 shrink-0">Kurs</span>
                  <span
                    v-for="(c, idx) in visibleCourses(n)"
                    :key="c.id"
                    class="text-sm font-medium text-green-900"
                  >
                    {{ formatNotificationCourse(c) }}<span v-if="idx < visibleCourses(n).length - 1">,</span>
                  </span>
                  <span
                    v-if="n.courses.length > maxVisibleCourses"
                    class="text-xs text-green-700 font-medium"
                  >
                    (+&nbsp;{{ n.courses.length - maxVisibleCourses }} weitere)
                  </span>
                </div>
              </div>
              <span class="text-xs text-slate-400 shrink-0 tabular-nums">{{ formatDateTime(n.createdAt) }}</span>
            </div>
            <p class="text-sm text-slate-600 mt-3 whitespace-pre-line">{{ n.message }}</p>
            <p class="text-xs text-slate-400 mt-2">von {{ n.authorName || 'Team' }}</p>
          </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Notification as AppNotification, NotificationCourseRef } from '~/types'

definePageMeta({ layout: 'customer' })

const api = useApi()
const { formatDateTime, formatNotificationCourse } = useHelpers()

const notifications = ref<AppNotification[]>([])
const loading = ref(true)

const maxVisibleCourses = 3

function visibleCourses(n: AppNotification): NotificationCourseRef[] {
  return n.courses.slice(0, maxVisibleCourses)
}

onMounted(async () => {
  const notifRes = await api.get<ApiListResponse<AppNotification>>('/api/customer/notifications')
  notifications.value = notifRes.items
  loading.value = false
})
</script>
