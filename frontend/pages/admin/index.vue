<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Dashboard</h1>

    <AppSkeletonStatGrid
      v-if="loading"
      class="mb-8"
      :count="4"
      grid-classes="grid grid-cols-1 gap-4 mb-8 sm:grid-cols-2 lg:grid-cols-4"
    />
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <UCard v-for="stat in stats" :key="stat.label">
        <div class="flex items-center gap-3">
          <div class="p-2 rounded-lg" :class="stat.bgClass">
            <UIcon :name="stat.icon" class="w-6 h-6" :class="stat.iconClass" />
          </div>
          <div>
            <p class="text-2xl font-bold text-slate-800">{{ stat.value }}</p>
            <p class="text-xs text-slate-500">{{ stat.label }}</p>
          </div>
        </div>
      </UCard>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <UCard>
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Offene Vertragsanfragen</h3>
            <UButton variant="ghost" size="xs" to="/admin/contracts">Alle anzeigen</UButton>
          </div>
        </template>
        <AppSkeletonCollection
          v-if="loading"
          :show-desktop-table="false"
          :mobile-cards="4"
          :meta-columns="0"
          :content-lines="2"
        />
        <div v-else-if="pendingContracts.length === 0" class="text-sm text-slate-400 py-4 text-center">
          Keine offenen Anfragen
        </div>
        <div v-else class="divide-y divide-slate-100">
          <div v-for="c in pendingContracts" :key="c.id" class="py-3 flex items-center justify-between">
            <div class="min-w-0 pr-2">
              <p class="text-sm font-medium text-slate-700 truncate">
                {{ c.dogName || 'Hund' }} · {{ c.customerName || 'Kunde' }}
              </p>
              <p class="text-xs text-slate-400">
                {{ c.coursesPerWeek }}× / Woche · {{ formatContractMonthlyPrice(c.price, c.type) }}
              </p>
            </div>
            <UBadge color="amber" variant="soft">Angefragt</UBadge>
          </div>
        </div>
      </UCard>

      <UCard>
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Neueste Kunden</h3>
            <UButton variant="ghost" size="xs" to="/admin/customers">Alle anzeigen</UButton>
          </div>
        </template>
        <AppSkeletonCollection
          v-if="loading"
          :show-desktop-table="false"
          :mobile-cards="4"
          :meta-columns="0"
          :content-lines="2"
          :show-actions="true"
        />
        <div v-else-if="recentCustomers.length === 0" class="text-sm text-slate-400 py-4 text-center">
          Noch keine Kunden
        </div>
        <div v-else class="divide-y divide-slate-100">
          <div v-for="c in recentCustomers" :key="c.id" class="py-3 flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-700">{{ c.name }}</p>
              <p class="text-xs text-slate-400">{{ c.email }}</p>
            </div>
            <UButton variant="ghost" size="xs" :to="`/admin/customers/${c.id}`">Details</UButton>
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Course, CourseDate, Customer, Contract } from '~/types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const { formatContractMonthlyPrice, todayIso } = useHelpers()

interface DashboardStat {
  label: string
  value: number
  icon: string
  bgClass: string
  iconClass: string
}

const customers = ref<Customer[]>([])
const contracts = ref<Contract[]>([])
const courses = ref<Course[]>([])
const calendarItems = ref<CourseDate[]>([])
const loading = ref(true)

const recentCustomers = computed(() => customers.value.slice(0, 5))
const pendingContracts = computed(() => contracts.value.filter(c => c.state === 'REQUESTED').slice(0, 5))

const stats = computed<DashboardStat[]>(() => [
  {
    label: 'Kunden',
    value: customers.value.length,
    icon: 'i-heroicons-users',
    bgClass: 'bg-blue-50',
    iconClass: 'text-blue-500',
  },
  {
    label: 'Aktive Kurse',
    value: courses.value.filter(c => !c.archived).length,
    icon: 'i-heroicons-academic-cap',
    bgClass: 'bg-komm-100',
    iconClass: 'text-komm-600',
  },
  {
    label: 'Heutige Termine',
    value: calendarItems.value.filter(d => d.date === todayIso()).length,
    icon: 'i-heroicons-calendar-days',
    bgClass: 'bg-purple-50',
    iconClass: 'text-purple-500',
  },
  {
    label: 'Offene Anfragen',
    value: pendingContracts.value.length,
    icon: 'i-heroicons-document-text',
    bgClass: 'bg-amber-50',
    iconClass: 'text-amber-500',
  },
])

onMounted(async () => {
  try {
    const [custRes, contRes, courseRes, calRes] = await Promise.all([
      api.get<ApiListResponse<Customer>>('/api/admin/customers'),
      api.get<ApiListResponse<Contract>>('/api/admin/contracts'),
      api.get<ApiListResponse<Course>>('/api/admin/courses'),
      api.get<ApiListResponse<CourseDate>>('/api/admin/calendar'),
    ])
    customers.value = custRes.items
    contracts.value = contRes.items
    courses.value = courseRes.items
    calendarItems.value = calRes.items
  } finally {
    loading.value = false
  }
})
</script>
