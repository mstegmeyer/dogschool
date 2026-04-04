<template>
  <UCard v-if="loading">
    <div class="space-y-4">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-2">
          <USkeleton class="h-6 w-40 rounded-md" />
          <USkeleton class="h-4 w-64 rounded-md" />
        </div>
        <USkeleton class="h-6 w-20 rounded-full" />
      </div>
      <USkeleton class="h-24 w-full rounded-lg" />
      <div class="flex gap-3">
        <USkeleton class="h-10 w-52 rounded-md" />
      </div>
    </div>
  </UCard>
  <UCard v-else data-testid="notification-settings-card">
    <div class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-800">Benachrichtigungen</h2>
          <p class="text-sm text-slate-500">
            Aktiviere Push-Mitteilungen für wichtige Hinweise und neue Nachrichten.
          </p>
        </div>

        <UBadge :color="badgeColor" variant="soft" size="sm">
          {{ statusLabel }}
        </UBadge>
      </div>

      <UAlert
        :color="alertColor"
        variant="soft"
        :title="statusTitle"
        :description="statusDescription"
        icon="i-heroicons-bell-alert"
      />

      <div class="flex flex-wrap gap-3">
        <UButton
          data-testid="enable-notifications"
          v-if="canEnable"
          label="Benachrichtigungen aktivieren"
          :loading="saving"
          @click="emit('enable')"
        />
        <UButton
          data-testid="disable-notifications"
          v-if="canDisable"
          label="Benachrichtigungen deaktivieren"
          color="gray"
          variant="soft"
          :loading="saving"
          @click="emit('disable')"
        />
      </div>
    </div>
  </UCard>
</template>

<script setup lang="ts">
defineProps<{
  loading: boolean
  badgeColor: string
  statusLabel: string
  alertColor: string
  statusTitle: string
  statusDescription: string
  canEnable: boolean
  canDisable: boolean
  saving: boolean
}>()

const emit = defineEmits<{
  (event: 'enable'): void
  (event: 'disable'): void
}>()
</script>
