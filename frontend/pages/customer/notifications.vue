<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Mitteilungen</h1>

    <AppSkeletonCollection
      v-if="loading"
      :show-desktop-table="false"
      :mobile-cards="4"
      :meta-columns="0"
      :content-lines="4"
      :show-badge="true"
    />
    <div v-else-if="notifications.length === 0" class="text-center py-12">
      <UIcon name="i-heroicons-bell" class="w-12 h-12 text-slate-300 mx-auto mb-3" />
      <p class="text-slate-500">Keine Mitteilungen vorhanden.</p>
      <p class="text-xs text-slate-400 mt-1">Abonniere Kurse, um Mitteilungen zu erhalten.</p>
    </div>

    <div v-else class="min-w-0 space-y-3">
      <UCard
        v-for="n in notifications"
        :key="n.id"
        :ui="cardUi(n)"
      >
        <AppNotificationDetail :notification="n" />
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Notification as AppNotification } from '~/types'

definePageMeta({ layout: 'customer' })

const api = useApi()

const notifications = ref<AppNotification[]>([])
const loading = ref(true)

function cardUi(n: AppNotification): { ring?: string, base: string } {
  return n.isPinned
    ? {
        ring: 'ring-2 ring-inset ring-indigo-300',
        base: 'relative w-full max-w-full min-w-0 overflow-hidden',
      }
    : {
        base: 'w-full max-w-full min-w-0 overflow-hidden',
      }
}

onMounted(async () => {
  const notifRes = await api.get<ApiListResponse<AppNotification>>('/api/customer/notifications')
  notifications.value = notifRes.items
  loading.value = false
})
</script>
