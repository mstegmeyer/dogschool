<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Guthaben</h1>

    <UCard class="mb-6">
        <div class="text-center py-4">
          <p class="text-5xl font-bold" :class="balance >= 0 ? 'text-komm-600' : 'text-red-500'">
            {{ balance }}
          </p>
          <p class="text-sm text-slate-400 mt-2">Verfügbare Credits</p>
        </div>
      </UCard>

    <UCard v-if="nextWeeklyGrants.length > 0" class="mb-6">
      <template #header>
        <h3 class="font-semibold text-slate-800">Nächste Gutschriften</h3>
      </template>
      <p class="text-xs text-slate-500 mb-3">
        Bei aktiven Dauer-Verträgen werden Credits einmal pro Kalenderwoche gutgeschrieben (siehe Verlauf). Unten siehst du den nächsten voraussichtlichen Zuschlagstermin.
      </p>
      <ul class="space-y-3">
        <li
          v-for="g in nextWeeklyGrants"
          :key="g.contractId"
          class="text-sm border border-slate-100 rounded-lg p-3 bg-slate-50/80"
        >
          <p class="font-medium text-slate-800">
            +{{ g.amount }} {{ g.amount === 1 ? 'Credit' : 'Credits' }}
            <span v-if="g.dogName" class="text-slate-500 font-normal"> · {{ g.dogName }}</span>
          </p>
          <p class="text-xs text-slate-600 mt-1">
            Nächster Zuschlag: <span class="font-medium">{{ formatDateTime(g.nextGrantAt) }}</span>
            <span v-if="g.pendingGrantThisWeek" class="text-amber-700"> · diese Woche noch ausstehend</span>
          </p>
        </li>
      </ul>
    </UCard>

    <UCard>
      <template #header>
        <h3 class="font-semibold text-slate-800">Transaktionsverlauf</h3>
      </template>

      <div v-if="loading" class="text-sm text-slate-400">Lade Verlauf…</div>
      <div v-else-if="transactions.length === 0" class="text-sm text-slate-400">Noch keine Transaktionen vorhanden.</div>
      <template v-else>
        <div class="space-y-3 md:hidden">
          <div
            v-for="transaction in transactions"
            :key="transaction.id"
            class="rounded-lg border border-slate-200 bg-white p-3"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="font-semibold" :class="transaction.amount > 0 ? 'text-komm-600' : 'text-red-500'">
                  {{ transaction.amount > 0 ? '+' : '' }}{{ transaction.amount }}
                </p>
                <UBadge
                  :color="transaction.type === 'WEEKLY_GRANT' ? 'primary' : transaction.type === 'BOOKING' ? 'blue' : transaction.type === 'CANCELLATION' ? 'amber' : 'gray'"
                  variant="soft"
                  size="xs"
                  class="mt-2"
                >
                  {{ creditTypeLabel(transaction.type) }}
                </UBadge>
              </div>
              <p class="text-right text-xs text-slate-500">
                {{ formatDateTime(transaction.createdAt) }}
              </p>
            </div>
            <p class="mt-3 text-sm text-slate-600">
              {{ transaction.description }}
            </p>
          </div>
        </div>
        <div class="hidden md:block">
          <UTable :columns="columns" :rows="transactions">
            <template #amount-data="{ row }">
              <span class="font-semibold" :class="row.amount > 0 ? 'text-komm-600' : 'text-red-500'">
                {{ row.amount > 0 ? '+' : '' }}{{ row.amount }}
              </span>
            </template>
            <template #type-data="{ row }">
              <UBadge
                :color="row.type === 'WEEKLY_GRANT' ? 'primary' : row.type === 'BOOKING' ? 'blue' : row.type === 'CANCELLATION' ? 'amber' : 'gray'"
                variant="soft"
                size="xs"
              >
                {{ creditTypeLabel(row.type) }}
              </UBadge>
            </template>
            <template #createdAt-data="{ row }">
              {{ formatDateTime(row.createdAt) }}
            </template>
          </UTable>
        </div>
      </template>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import type { CreditTransaction, CustomerCreditsResponse, NextWeeklyGrantHint } from '~/types'

definePageMeta({ layout: 'customer' })

const api = useApi()
const { formatDateTime, creditTypeLabel } = useHelpers()

const balance = ref(0)
const nextWeeklyGrants = ref<NextWeeklyGrantHint[]>([])
const transactions = ref<CreditTransaction[]>([])
const loading = ref(true)

const columns = [
  { key: 'amount', label: 'Betrag' },
  { key: 'type', label: 'Typ' },
  { key: 'description', label: 'Beschreibung' },
  { key: 'createdAt', label: 'Datum' },
]

onMounted(async () => {
  const res = await api.get<CustomerCreditsResponse>('/api/customer/credits')
  balance.value = res.balance
  nextWeeklyGrants.value = res.nextWeeklyGrants ?? []
  transactions.value = res.items
  loading.value = false
})
</script>
