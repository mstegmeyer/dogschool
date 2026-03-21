<template>
  <div v-if="customer">
    <div class="flex items-center gap-3 mb-6">
      <UButton icon="i-heroicons-arrow-left" variant="ghost" size="sm" to="/admin/customers" />
      <h1 class="text-2xl font-bold text-slate-800">{{ customer.name }}</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <UCard class="lg:col-span-2">
        <template #header>
          <h3 class="font-semibold text-slate-800">Kundendaten</h3>
        </template>
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-slate-400 text-xs">Name</p>
            <p class="font-medium text-slate-700">{{ customer.name }}</p>
          </div>
          <div>
            <p class="text-slate-400 text-xs">E-Mail</p>
            <p class="font-medium text-slate-700">{{ customer.email }}</p>
          </div>
          <div>
            <p class="text-slate-400 text-xs">Straße</p>
            <p class="font-medium text-slate-700">{{ customer.address.street || '–' }}</p>
          </div>
          <div>
            <p class="text-slate-400 text-xs">Ort</p>
            <p class="font-medium text-slate-700">
              {{ customer.address.postalCode }} {{ customer.address.city || '–' }}
            </p>
          </div>
          <div>
            <p class="text-slate-400 text-xs">IBAN</p>
            <p class="font-medium text-slate-700">{{ customer.bankAccount.iban || '–' }}</p>
          </div>
          <div>
            <p class="text-slate-400 text-xs">Kontoinhaber</p>
            <p class="font-medium text-slate-700">{{ customer.bankAccount.accountHolder || '–' }}</p>
          </div>
          <div>
            <p class="text-slate-400 text-xs">Registriert</p>
            <p class="font-medium text-slate-700">{{ formatDate(customer.createdAt) }}</p>
          </div>
        </div>
      </UCard>

      <UCard>
        <template #header>
          <h3 class="font-semibold text-slate-800">Guthaben</h3>
        </template>
        <div class="text-center py-2">
          <p class="text-4xl font-bold" :class="creditBalance >= 0 ? 'text-green-600' : 'text-red-500'">
            {{ creditBalance }}
          </p>
          <p class="text-xs text-slate-400 mt-1">Credits</p>
        </div>
        <UDivider class="my-3" />
        <form class="space-y-3" @submit.prevent="adjustCredits">
          <UFormGroup label="Korrektur">
            <UInput v-model.number="adjustAmount" type="number" placeholder="z.B. 5 oder -2" />
          </UFormGroup>
          <UFormGroup label="Beschreibung">
            <UInput v-model="adjustDescription" placeholder="Grund der Korrektur" />
          </UFormGroup>
          <UButton type="submit" size="sm" block :disabled="!adjustAmount || !adjustDescription">
            Guthaben anpassen
          </UButton>
        </form>
      </UCard>
    </div>

    <UCard class="mt-6">
      <template #header>
        <h3 class="font-semibold text-slate-800">Guthaben-Verlauf</h3>
      </template>
      <UTable :columns="creditColumns" :rows="creditHistory">
        <template #amount-data="{ row }">
          <span :class="row.amount > 0 ? 'text-green-600 font-semibold' : 'text-red-500 font-semibold'">
            {{ row.amount > 0 ? '+' : '' }}{{ row.amount }}
          </span>
        </template>
        <template #type-data="{ row }">
          <UBadge
            :color="row.type === 'WEEKLY_GRANT' ? 'green' : row.type === 'BOOKING' ? 'blue' : row.type === 'CANCELLATION' ? 'amber' : 'gray'"
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
    </UCard>
  </div>
</template>

<script setup lang="ts">
import type { Customer, CreditTransaction } from '~/types'

definePageMeta({ layout: 'admin' })

const route = useRoute()
const api = useApi()
const toast = useToast()
const { formatDate, formatDateTime, creditTypeLabel } = useHelpers()

const customer = ref<Customer | null>(null)
const creditBalance = ref(0)
const creditHistory = ref<CreditTransaction[]>([])
const adjustAmount = ref<number | null>(null)
const adjustDescription = ref('')

const creditColumns = [
  { key: 'amount', label: 'Betrag' },
  { key: 'type', label: 'Typ' },
  { key: 'description', label: 'Beschreibung' },
  { key: 'createdAt', label: 'Datum' },
]

interface AdminCreditsResponse {
  balance: number
  items: CreditTransaction[]
}

async function loadCredits(): Promise<void> {
  const res = await api.get<AdminCreditsResponse>(
    `/api/admin/credits?customerId=${route.params.id}`,
  )
  creditBalance.value = res.balance
  creditHistory.value = res.items
}

async function adjustCredits(): Promise<void> {
  if (!adjustAmount.value || !adjustDescription.value) return
  await api.post('/api/admin/credits/adjust', {
    customerId: route.params.id,
    amount: adjustAmount.value,
    description: adjustDescription.value,
  })
  adjustAmount.value = null
  adjustDescription.value = ''
  toast.add({ title: 'Guthaben angepasst', color: 'green' })
  await loadCredits()
}

onMounted(async () => {
  const [cust] = await Promise.all([
    api.get<Customer>(`/api/admin/customers/${route.params.id}`),
    loadCredits(),
  ])
  customer.value = cust
})
</script>
