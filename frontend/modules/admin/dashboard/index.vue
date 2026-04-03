<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Dashboard</h1>

    <StatsGrid :loading="loading" :stats="stats" />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <PendingContractsCard
        :loading="loading"
        :count="pendingContractRequests.length"
        :contracts="pendingContracts"
      />
      <TodayScheduleCard :loading="loading" :course-dates="todaySchedule" />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Course, CourseDate, Contract } from '~/types'
import PendingContractsCard from './components/PendingContractsCard.vue'
import StatsGrid from './components/StatsGrid.vue'
import TodayScheduleCard from './components/TodayScheduleCard.vue'
import type { DashboardStat } from './types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const { todayIso, getWeekMonday } = useHelpers()

const contracts = ref<Contract[]>([])
const courses = ref<Course[]>([])
const calendarItems = ref<CourseDate[]>([])
const loading = ref(true)

const activeContracts = computed(() => contracts.value.filter(contract => contract.state === 'ACTIVE'))
const pendingContractRequests = computed(() => contracts.value.filter(contract => contract.state === 'REQUESTED'))
const pendingContracts = computed(() => pendingContractRequests.value.slice(0, 5))
const todaySchedule = computed(() => calendarItems.value
  .filter(courseDate => courseDate.date === todayIso())
  .sort((a, b) => a.startTime.localeCompare(b.startTime)))
const monthlyContractValue = computed(() => activeContracts.value.reduce((sum, contract) => {
  const rawValue = contract.priceMonthly ?? contract.price
  const parsedValue = Number.parseFloat(rawValue ?? '0')
  return sum + (Number.isFinite(parsedValue) ? parsedValue : 0)
}, 0))

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value)
}

const stats = computed<DashboardStat[]>(() => [
  {
    label: 'Aktive Kurse / Woche',
    value: courses.value.filter(course => !course.archived).length,
    icon: 'i-heroicons-academic-cap',
    bgClass: 'bg-komm-100',
    iconClass: 'text-komm-600',
  },
  {
    label: 'Aktive Verträge',
    value: activeContracts.value.length,
    icon: 'i-heroicons-document-check',
    bgClass: 'bg-blue-50',
    iconClass: 'text-blue-500',
  },
  {
    label: 'Monatlicher Vertragswert',
    value: formatCurrency(monthlyContractValue.value),
    icon: 'i-heroicons-banknotes',
    bgClass: 'bg-emerald-50',
    iconClass: 'text-emerald-500',
  },
])

onMounted(async () => {
  try {
    const [contRes, courseRes, calRes] = await Promise.all([
      api.get<ApiListResponse<Contract>>('/api/admin/contracts'),
      api.get<ApiListResponse<Course>>('/api/admin/courses'),
      api.get<ApiListResponse<CourseDate>>(`/api/admin/calendar?week=${getWeekMonday(todayIso())}`),
    ])
    contracts.value = contRes.items
    courses.value = courseRes.items
    calendarItems.value = calRes.items
  } finally {
    loading.value = false
  }
})
</script>
