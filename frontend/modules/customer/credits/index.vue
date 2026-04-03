<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Guthaben</h1>

    <UCard class="mb-6">
      <CreditBalanceSummary :loading="loading" :balance="balance" />
    </UCard>

    <NextWeeklyGrantsCard :loading="loading" :items="nextWeeklyGrants" />

    <UCard>
      <template #header>
        <h3 class="font-semibold text-slate-800">Transaktionsverlauf</h3>
      </template>

      <AppSkeletonCollection
        v-if="loading"
        :mobile-cards="4"
        :desktop-rows="6"
        :desktop-columns="4"
        :meta-columns="0"
        :content-lines="2"
        :show-badge="true"
      />
      <CreditHistoryList v-else :entries="transactions" :columns="columns" empty-label="Noch keine Transaktionen vorhanden." />
    </UCard>
  </div>
</template>

<script setup lang="ts">
import type { CreditTransaction, CustomerCreditsResponse, NextWeeklyGrantHint } from '~/types'
import CreditBalanceSummary from '~/components/credits/CreditBalanceSummary.vue'
import CreditHistoryList from '~/components/credits/CreditHistoryList.vue'
import NextWeeklyGrantsCard from './components/NextWeeklyGrantsCard.vue'

definePageMeta({ layout: 'customer' })

const api = useApi()

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
