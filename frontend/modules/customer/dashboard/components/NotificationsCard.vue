<template>
  <UCard class="xl:min-h-[32rem]">
    <template #header>
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 class="font-semibold text-slate-800">Mitteilungen</h3>
          <p class="mt-1 text-xs text-slate-400">Wichtige Hinweise zu deinen Kursen und allgemeine Updates.</p>
        </div>
        <UButton variant="ghost" size="xs" to="/customer/notifications">Alle</UButton>
      </div>
    </template>

    <AppSkeletonCollection
      v-if="loading"
      :show-desktop-table="false"
      :mobile-cards="5"
      :meta-columns="0"
      :content-lines="3"
      :show-badge="false"
    />
    <div v-else-if="notifications.length === 0" class="py-4 text-center text-sm text-slate-400">
      Keine neuen Mitteilungen
    </div>
    <div v-else class="divide-y divide-slate-100">
      <button
        v-for="notification in notifications.slice(0, 4)"
        :key="notification.id"
        type="button"
        class="-mx-2 block w-[calc(100%+1rem)] rounded-lg px-2 py-3 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-komm-500"
        @click="emit('select', notification)"
      >
        <div class="flex items-center gap-1.5">
          <UIcon v-if="notification.isPinned" name="i-heroicons-map-pin" class="h-3.5 w-3.5 shrink-0 text-indigo-600" />
          <h4 class="text-sm font-semibold text-slate-700">{{ notification.title }}</h4>
        </div>
        <p v-if="notification.isGlobal" class="mt-0.5 text-xs text-amber-700">Alle Kurse</p>
        <p v-else-if="notification.courses.length > 0" class="mt-0.5 text-xs text-komm-700">{{ formatNotificationCourses(notification.courses) }}</p>
        <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ notification.message }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ formatDateTime(notification.createdAt) }}</p>
      </button>
    </div>
  </UCard>
</template>

<script setup lang="ts">
import type { Notification } from '~/types'

defineProps<{
  loading: boolean
  notifications: Notification[]
}>()

const emit = defineEmits<{
  (event: 'select', value: Notification): void
}>()

const { formatDateTime, formatNotificationCourses } = useHelpers()
</script>
