<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Verträge</h1>
      <USelectMenu v-model="stateFilter" :options="stateOptions" value-attribute="value" class="w-44" />
    </div>

    <UCard>
      <div class="overflow-x-auto -mx-4 sm:-mx-6 px-4 sm:px-6">
        <UTable
          :columns="columns"
          :rows="filteredContracts"
          :loading="loading"
          class="min-w-[720px]"
        >
        <template #participant-data="{ row }">
          <div class="min-w-[8rem] max-w-[14rem] py-0.5">
            <p class="font-medium text-slate-800 truncate" :title="row.dogName || ''">
              {{ row.dogName || '–' }}
            </p>
            <p class="text-xs text-slate-500 truncate" :title="row.customerName || ''">
              {{ row.customerName || '–' }}
            </p>
          </div>
        </template>
        <template #state-data="{ row }">
          <UBadge :color="contractStateColor(row.state)" variant="soft" size="xs">
            {{ contractStateLabel(row.state) }}
          </UBadge>
        </template>
        <template #coursesPerWeek-data="{ row }">
          {{ row.coursesPerWeek }}× / Wo.
        </template>
        <template #price-data="{ row }">
          <span class="text-sm whitespace-nowrap">{{ formatContractMonthlyPrice(row.price, row.type) }}</span>
        </template>
        <template #dates-data="{ row }">
          <span v-if="row.startDate" class="text-sm whitespace-nowrap">
            {{ formatDate(row.startDate) }} – {{ row.endDate ? formatDate(row.endDate) : '∞' }}
          </span>
          <span v-else class="text-slate-400">–</span>
        </template>
        <template #createdAt-data="{ row }">
          <span class="text-sm whitespace-nowrap">{{ formatDate(row.createdAt) }}</span>
        </template>
        <template #actions-data="{ row }">
          <div class="flex flex-wrap justify-end gap-1 whitespace-nowrap min-w-[9rem]">
            <template v-if="row.state === 'REQUESTED'">
              <UButton size="xs" color="primary" variant="soft" label="Genehmigen" @click="approve(row)" />
              <UButton size="xs" color="red" variant="soft" label="Ablehnen" @click="decline(row)" />
            </template>
            <UButton
              v-else-if="row.state === 'ACTIVE'"
              size="xs"
              color="red"
              variant="soft"
              label="Kündigen"
              @click="openCancelConfirm(row)"
            />
          </div>
        </template>
        </UTable>
      </div>
    </UCard>

    <UModal v-model="showCancelModal">
      <UCard>
        <template #header>
          <h3 class="font-semibold text-slate-800">Vertrag kündigen?</h3>
        </template>
        <p class="text-sm text-slate-600">
          Möchtest du den Vertrag für
          <strong>{{ contractToCancel?.dogName }}</strong>
          ({{ contractToCancel?.customerName }}) wirklich kündigen? Diese Aktion kann nicht rückgängig gemacht werden.
        </p>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" label="Abbrechen" @click="showCancelModal = false" />
            <UButton color="red" label="Kündigen" :loading="cancelling" @click="confirmCancel" />
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Contract } from '~/types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const toast = useToast()
const { formatDate, contractStateLabel, contractStateColor, formatContractMonthlyPrice } = useHelpers()

const contracts = ref<Contract[]>([])
const loading = ref(true)
const stateFilter = ref('ACTIVE')

const showCancelModal = ref(false)
const contractToCancel = ref<Contract | null>(null)
const cancelling = ref(false)

const stateOptions = [
  { label: 'Aktiv', value: 'ACTIVE' },
  { label: 'Alle', value: 'all' },
  { label: 'Angefragt', value: 'REQUESTED' },
  { label: 'Abgelehnt', value: 'DECLINED' },
  { label: 'Gekündigt', value: 'CANCELLED' },
]

const columns = [
  { key: 'participant', label: 'Hund · Halter' },
  { key: 'state', label: 'Status', sortable: true },
  { key: 'coursesPerWeek', label: 'Kurse' },
  { key: 'price', label: 'Preis' },
  { key: 'dates', label: 'Zeitraum' },
  { key: 'createdAt', label: 'Erstellt', sortable: true },
  { key: 'actions', label: 'Aktion' },
]

const filteredContracts = computed(() => {
  if (stateFilter.value === 'all') return contracts.value
  return contracts.value.filter(c => c.state === stateFilter.value)
})

function openCancelConfirm(contract: Contract) {
  contractToCancel.value = contract
  showCancelModal.value = true
}

async function confirmCancel() {
  if (!contractToCancel.value) return
  cancelling.value = true
  try {
    await api.post(`/api/admin/contracts/${contractToCancel.value.id}/cancel`)
    toast.add({ title: 'Vertrag gekündigt', color: 'red' })
    showCancelModal.value = false
    contractToCancel.value = null
    await loadContracts()
  } finally {
    cancelling.value = false
  }
}

async function approve(contract: Contract) {
  await api.post(`/api/admin/contracts/${contract.id}/approve`)
  toast.add({ title: 'Vertrag genehmigt', color: 'green' })
  await loadContracts()
}

async function decline(contract: Contract) {
  await api.post(`/api/admin/contracts/${contract.id}/decline`)
  toast.add({ title: 'Vertrag abgelehnt', color: 'amber' })
  await loadContracts()
}

async function loadContracts(): Promise<void> {
  loading.value = true
  const res = await api.get<ApiListResponse<Contract>>('/api/admin/contracts')
  contracts.value = res.items
  loading.value = false
}

onMounted(() => loadContracts())
</script>
