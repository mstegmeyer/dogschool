<template>
  <div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-slate-800">Kunden</h1>
      <UInput v-model="search" icon="i-heroicons-magnifying-glass" placeholder="Suchen…" class="w-full sm:w-64" />
    </div>

    <UCard>
      <div v-if="loading" class="text-sm text-slate-400">Kunden werden geladen…</div>
      <div v-else-if="filteredCustomers.length === 0" class="text-sm text-slate-400">Keine passenden Kunden gefunden.</div>
      <template v-else>
        <div class="space-y-3 md:hidden">
          <button
            v-for="customer in filteredCustomers"
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
          <UTable :columns="columns" :rows="filteredCustomers" @select="onSelect">
            <template #createdAt-data="{ row }">
              {{ formatDate(row.createdAt) }}
            </template>
            <template #address-data="{ row }">
              <span v-if="row.address.city">{{ row.address.postalCode }} {{ row.address.city }}</span>
              <span v-else class="text-slate-400">–</span>
            </template>
          </UTable>
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

const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'E-Mail', sortable: true },
  { key: 'address', label: 'Ort' },
  { key: 'createdAt', label: 'Registriert', sortable: true },
]

const filteredCustomers = computed(() => {
  if (!search.value) return customers.value
  const q = search.value.toLowerCase()
  return customers.value.filter(c =>
    c.name.toLowerCase().includes(q)
    || c.email.toLowerCase().includes(q)
    || c.address.city?.toLowerCase().includes(q),
  )
})

function onSelect(row: Customer) {
  navigateTo(`/admin/customers/${row.id}`)
}

onMounted(async () => {
  const res = await api.get<ApiListResponse<Customer>>('/api/admin/customers')
  customers.value = res.items
  loading.value = false
})
</script>
