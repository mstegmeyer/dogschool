<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Mitteilungen</h1>

    <NotificationsList :loading="loading" :notifications="notifications" />
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Notification as AppNotification } from '~/types'
import NotificationsList from './components/NotificationsList.vue'

definePageMeta({ layout: 'customer' })

const api = useApi()

const notifications = ref<AppNotification[]>([])
const loading = ref(true)

onMounted(async () => {
  const notifRes = await api.get<ApiListResponse<AppNotification>>('/api/customer/notifications')
  notifications.value = notifRes.items
  loading.value = false
})
</script>
