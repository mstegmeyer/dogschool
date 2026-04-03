<template>
  <div v-if="entries.length === 0" class="text-sm text-slate-400">
    {{ emptyLabel }}
  </div>
  <template v-else>
    <div class="space-y-3 md:hidden">
      <div
        v-for="entry in entries"
        :key="entry.id"
        class="rounded-lg border border-slate-200 bg-white p-3"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <p :class="amountClass(entry.amount)">
              {{ entry.amount > 0 ? '+' : '' }}{{ entry.amount }}
            </p>
            <UBadge
              :color="badgeColor(entry.type)"
              variant="soft"
              size="xs"
              class="mt-2"
            >
              {{ creditTypeLabel(entry.type) }}
            </UBadge>
          </div>
          <p class="text-right text-xs text-slate-500">
            {{ formatDateTime(entry.createdAt) }}
          </p>
        </div>
        <p class="mt-3 text-sm text-slate-600">
          {{ entry.description }}
        </p>
      </div>
    </div>
    <div class="hidden md:block">
      <UTable :columns="columns" :rows="entries">
        <template #amount-data="{ row }">
          <span :class="amountClass(row.amount)">
            {{ row.amount > 0 ? '+' : '' }}{{ row.amount }}
          </span>
        </template>
        <template #type-data="{ row }">
          <UBadge :color="badgeColor(row.type)" variant="soft" size="xs">
            {{ creditTypeLabel(row.type) }}
          </UBadge>
        </template>
        <template #createdAt-data="{ row }">
          {{ formatDateTime(row.createdAt) }}
        </template>
      </UTable>
    </div>
  </template>
</template>

<script setup lang="ts">
import type { CreditTransaction, CreditTransactionType } from '~/types'

withDefaults(defineProps<{
  entries: CreditTransaction[]
  columns: Array<{ key: string; label: string }>
  emptyLabel?: string
}>(), {
  emptyLabel: 'Noch keine Guthaben-Buchungen vorhanden.',
})

const { formatDateTime, creditTypeLabel } = useHelpers()

function badgeColor(type: CreditTransactionType): string {
  switch (type) {
    case 'WEEKLY_GRANT':
      return 'primary'
    case 'BOOKING':
      return 'blue'
    case 'CANCELLATION':
      return 'amber'
    default:
      return 'gray'
  }
}

function amountClass(amount: number): string {
  return amount > 0 ? 'text-komm-600 font-semibold' : 'text-red-500 font-semibold'
}
</script>
