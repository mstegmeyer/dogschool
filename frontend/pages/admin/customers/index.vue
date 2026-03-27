<template>
  <div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-slate-800">Kunden</h1>
      <UInput v-model="search" icon="i-heroicons-magnifying-glass" placeholder="Suchen…" class="w-full sm:w-64" />
    </div>

    <UCard>
      <AppSkeletonCollection
        v-if="loading"
        :mobile-cards="4"
        :desktop-rows="6"
        :desktop-columns="4"
        :meta-columns="1"
        :show-badge="false"
      />
      <div v-else-if="customers.length === 0" class="text-sm text-slate-400">Keine passenden Kunden gefunden.</div>
      <template v-else>
        <div class="space-y-3 md:hidden">
          <button
            v-for="customer in customers"
            :key="customer.id"
            type="button"
            class="w-full rounded-lg border border-slate-200 bg-white p-4 text-left transition hover:border-slate-300"
            @click="onSelect(customer)"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="font-medium text-slate-800">{{ customer.name }}</p>
                <p class="truncate text-sm text-slate-500">{{ customer.email }}</p>
              </div>
              <span class="text-xs text-slate-400">{{ formatDate(customer.createdAt) }}</span>
            </div>
            <p class="mt-3 text-sm text-slate-600">
              {{ customer.address.city ? `${customer.address.postalCode} ${customer.address.city}` : 'Kein Ort hinterlegt' }}
            </p>
          </button>
        </div>
        <div class="hidden md:block">
          <UTable
            v-model:sort="sort"
            :columns="columns"
            :rows="customers"
            sort-mode="manual"
            @select="onSelect"
          >
            <template #createdAt-data="{ row }">
              {{ formatDate(row.createdAt) }}
            </template>
            <template #address-data="{ row }">
              <span v-if="row.address.city">{{ row.address.postalCode }} {{ row.address.city }}</span>
              <span v-else class="text-slate-400">–</span>
            </template>
          </UTable>
        </div>
        <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm text-slate-500">{{ resultSummary }}</p>
          <UPagination
            v-if="showPagination"
            v-model="currentPage"
            :page-count="pageSize"
            :total="totalCustomers"
            :show-first="true"
            :show-last="true"
          />
        </div>
      </template>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Customer } from '~/types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const { formatDate } = useHelpers()

const customers = ref<Customer[]>([])
const search = ref('')
const loading = ref(true)
const currentPage = ref(1)
const debouncedSearch = ref('')
const totalCustomers = ref(0)
const totalPages = ref(1)
const sort = ref<{ column: string | null; direction: 'asc' | 'desc' }>({
  column: 'createdAt',
  direction: 'desc',
})

const pageSize = 20
let searchDebounceTimeout: ReturnType<typeof setTimeout> | null = null
let latestLoadId = 0

const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'E-Mail', sortable: true },
  { key: 'address', label: 'Ort' },
  { key: 'createdAt', label: 'Registriert', sortable: true },
]

const showPagination = computed(() => totalCustomers.value > pageSize)
const pageStart = computed(() => (totalCustomers.value === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1))
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, totalCustomers.value))
const resultSummary = computed(() => {
  if (totalCustomers.value === 0) return '0 Kunden'
  if (totalPages.value <= 1) return `${totalCustomers.value} Kunden`

  return `${pageStart.value}–${pageEnd.value} von ${totalCustomers.value} Kunden`
})

function onSelect(row: Customer) {
  navigateTo(`/admin/customers/${row.id}`)
}

async function loadCustomers(): Promise<void> {
  const loadId = ++latestLoadId
  loading.value = true

  const params = new URLSearchParams({
    page: `${currentPage.value}`,
    limit: `${pageSize}`,
  })
  if (debouncedSearch.value) {
    params.set('q', debouncedSearch.value)
  }
  if (sort.value.column) {
    params.set('sort', sort.value.column)
    params.set('direction', sort.value.direction)
  }

  const res = await api.get<ApiListResponse<Customer>>(`/api/admin/customers?${params.toString()}`)
  if (loadId !== latestLoadId) return

  customers.value = res.items
  totalCustomers.value = res.pagination?.total ?? res.items.length
  totalPages.value = res.pagination?.pages ?? 1
  loading.value = false
}

watch(currentPage, () => {
  void loadCustomers()
})

watch(search, (value) => {
  if (searchDebounceTimeout !== null) {
    clearTimeout(searchDebounceTimeout)
  }

  searchDebounceTimeout = setTimeout(() => {
    debouncedSearch.value = value.trim()

    if (currentPage.value !== 1) {
      currentPage.value = 1
      return
    }

    void loadCustomers()
  }, 250)
})

watch(sort, () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1
    return
  }

  void loadCustomers()
}, { deep: true })

onBeforeUnmount(() => {
  if (searchDebounceTimeout !== null) {
    clearTimeout(searchDebounceTimeout)
  }
})

onMounted(() => {
  void loadCustomers()
})
</script>
