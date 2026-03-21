<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Kunden</h1>
      <UInput v-model="search" icon="i-heroicons-magnifying-glass" placeholder="Suchen…" class="w-64" />
    </div>

    <UCard>
      <UTable :columns="columns" :rows="filteredCustomers" :loading="loading" @select="onSelect">
        <template #createdAt-data="{ row }">
          {{ formatDate(row.createdAt) }}
        </template>
        <template #address-data="{ row }">
          <span v-if="row.address.city">{{ row.address.postalCode }} {{ row.address.city }}</span>
          <span v-else class="text-slate-400">–</span>
        </template>
      </UTable>
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
