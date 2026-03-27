<template>
  <div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-slate-800">Verträge</h1>
      <USelectMenu v-model="stateFilter" :options="stateOptions" value-attribute="value" class="w-full sm:w-44" />
    </div>

    <UCard>
      <AppSkeletonCollection
        v-if="loading"
        :mobile-cards="4"
        :desktop-rows="6"
        :desktop-columns="7"
        :meta-columns="4"
        :show-actions="true"
      />
      <div v-else-if="contracts.length === 0" class="text-sm text-slate-400">Keine Verträge für diesen Filter gefunden.</div>
      <template v-else>
        <div class="space-y-3 md:hidden">
          <div
            v-for="contract in contracts"
            :key="contract.id"
            class="rounded-lg border border-slate-200 bg-white p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="font-medium text-slate-800">{{ contract.dogName || '–' }}</p>
                <p class="text-sm text-slate-500">{{ contract.customerName || '–' }}</p>
              </div>
              <UBadge :color="contractStateColor(contract.state)" variant="soft" size="xs">
                {{ contractStateLabel(contract.state) }}
              </UBadge>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
              <div>
                <p class="text-slate-400">Kurse</p>
                <p class="font-medium text-slate-700">{{ contract.coursesPerWeek }}× / Wo.</p>
              </div>
              <div>
                <p class="text-slate-400">Preis</p>
                <p class="font-medium text-slate-700">{{ formatContractMonthlyPrice(contract.price, contract.type) }}</p>
              </div>
              <div>
                <p class="text-slate-400">Zeitraum</p>
                <p class="font-medium text-slate-700">
                  {{ contract.startDate ? `${formatDate(contract.startDate)} – ${contract.endDate ? formatDate(contract.endDate) : '∞'}` : '–' }}
                </p>
              </div>
              <div>
                <p class="text-slate-400">Erstellt</p>
                <p class="font-medium text-slate-700">{{ formatDate(contract.createdAt) }}</p>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <template v-if="contract.state === 'REQUESTED'">
                <UButton size="sm" color="primary" variant="soft" label="Genehmigen" @click="approve(contract)" />
                <UButton size="sm" color="red" variant="soft" label="Ablehnen" @click="decline(contract)" />
              </template>
              <UButton
                v-else-if="contract.state === 'ACTIVE'"
                size="sm"
                color="red"
                variant="soft"
                label="Kündigen"
                @click="openCancelConfirm(contract)"
              />
            </div>
          </div>
        </div>
        <div class="hidden overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 md:block">
          <UTable
            v-model:sort="sort"
            :columns="columns"
            :rows="contracts"
            class="min-w-[720px]"
            sort-mode="manual"
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
        <div class="mx-4 mt-6 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm text-slate-500 sm:mx-0 sm:flex-row sm:items-center sm:justify-between">
          <p>{{ resultSummary }}</p>
          <UPagination
            v-if="showPagination"
            v-model="currentPage"
            :page-count="pageSize"
            :total="totalContracts"
            :show-first="true"
            :show-last="true"
          />
        </div>
      </template>
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
const currentPage = ref(1)
const totalContracts = ref(0)
const totalPages = ref(1)
const sort = ref<{ column: string | null; direction: 'asc' | 'desc' }>({
  column: 'createdAt',
  direction: 'desc',
})

const showCancelModal = ref(false)
const contractToCancel = ref<Contract | null>(null)
const cancelling = ref(false)
const pageSize = 20

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

const showPagination = computed(() => totalContracts.value > pageSize)
const pageStart = computed(() => (totalContracts.value === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1))
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, totalContracts.value))
const resultSummary = computed(() => {
  if (totalContracts.value === 0) return '0 Verträge'
  if (totalPages.value <= 1) return `${totalContracts.value} Verträge`

  return `${pageStart.value}–${pageEnd.value} von ${totalContracts.value} Verträgen`
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
  const params = new URLSearchParams({
    page: `${currentPage.value}`,
    limit: `${pageSize}`,
    state: stateFilter.value,
  })
  if (sort.value.column) {
    params.set('sort', sort.value.column)
    params.set('direction', sort.value.direction)
  }
  const res = await api.get<ApiListResponse<Contract>>(`/api/admin/contracts?${params.toString()}`)
  contracts.value = res.items
  totalContracts.value = res.pagination?.total ?? res.items.length
  totalPages.value = res.pagination?.pages ?? 1
  loading.value = false
}

watch(currentPage, () => {
  void loadContracts()
})

watch(stateFilter, () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1
    return
  }

  void loadContracts()
})

watch(sort, () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1
    return
  }

  void loadContracts()
}, { deep: true })

onMounted(() => {
  void loadContracts()
})
</script>
