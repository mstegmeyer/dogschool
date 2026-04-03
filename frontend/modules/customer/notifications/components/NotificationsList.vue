<template>
  <AppSkeletonCollection
    v-if="loading"
    :show-desktop-table="false"
    :mobile-cards="4"
    :meta-columns="0"
    :content-lines="4"
    :show-badge="true"
  />
  <div v-else-if="notifications.length === 0" class="py-12 text-center">
    <UIcon name="i-heroicons-bell" class="mx-auto mb-3 h-12 w-12 text-slate-300" />
    <p class="text-slate-500">Keine Mitteilungen vorhanden.</p>
    <p class="mt-1 text-xs text-slate-400">Abonniere Kurse, um Mitteilungen zu erhalten.</p>
  </div>
  <div v-else class="min-w-0 space-y-3">
    <UCard
      v-for="notification in notifications"
      :key="notification.id"
      :data-testid="`notification-card-${notification.id}`"
      :ui="cardUi(notification)"
    >
      <AppNotificationDetail :notification="notification" />
    </UCard>
  </div>
</template>

<script setup lang="ts">
import type { Notification as AppNotification } from '~/types'

defineProps<{
  loading: boolean
  notifications: AppNotification[]
}>()

function cardUi(notification: AppNotification): { ring?: string; base: string } {
  return notification.isPinned
    ? {
        ring: 'ring-2 ring-inset ring-indigo-300',
        base: 'relative w-full max-w-full min-w-0 overflow-hidden',
      }
    : {
        base: 'w-full max-w-full min-w-0 overflow-hidden',
      }
}
</script>
