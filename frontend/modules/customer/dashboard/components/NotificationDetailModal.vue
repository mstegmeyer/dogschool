<template>
  <UModal :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <UCard v-if="notification">
      <template #header>
        <div class="flex items-center justify-between gap-3">
          <h3 class="font-semibold text-slate-800">Mitteilung</h3>
          <UButton
            color="gray"
            variant="ghost"
            icon="i-heroicons-x-mark"
            aria-label="Mitteilung schliessen"
            @click="emit('update:modelValue', false)"
          />
        </div>
      </template>
      <AppNotificationDetail :notification="notification" />
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
import type { Notification } from '~/types'

defineProps<{
  modelValue: boolean
  notification: Notification | null
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
}>()
</script>
