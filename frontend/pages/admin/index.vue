<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Dashboard</h1>

    <AppSkeletonStatGrid
      v-if="loading"
      class="mb-8"
      :count="3"
      grid-classes="grid grid-cols-1 gap-4 mb-8 sm:grid-cols-2 lg:grid-cols-3"
    />
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
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
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-slate-800">Offene Vertragsanfragen</h3>
              <UBadge color="amber" variant="soft">{{ pendingContractRequests.length }}</UBadge>
            </div>
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
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-slate-800">Heutige Termine</h3>
              <UBadge color="primary" variant="soft">{{ todaySchedule.length }}</UBadge>
            </div>
            <UButton variant="ghost" size="xs" to="/admin/calendar">Zum Kalender</UButton>
          </div>
        </template>
        <AppSkeletonCollection
          v-if="loading"
          :show-desktop-table="false"
          :mobile-cards="4"
          :meta-columns="0"
          :content-lines="2"
        />
        <div v-else-if="todaySchedule.length === 0" class="text-sm text-slate-400 py-4 text-center">
          Keine Termine heute
        </div>
        <div v-else class="divide-y divide-slate-100">
          <div v-for="courseDate in todaySchedule" :key="courseDate.id" class="py-3 flex items-center justify-between gap-3">
            <div class="min-w-0">
              <p
                class="text-sm font-medium truncate"
                :class="courseDate.cancelled ? 'text-red-600 line-through' : 'text-slate-700'"
              >
                {{ courseDate.courseType?.name || 'Kurs' }}
              </p>
              <p class="text-xs text-slate-400">{{ courseDate.startTime }} – {{ courseDate.endTime }}</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
              <UTooltip
                v-if="courseDate.bookingCount"
                :text="courseDate.bookings?.map(b => `${b.dogName} (${b.customerName})`).join(', ') || ''"
              >
                <div class="flex items-center gap-1 text-slate-500">
                  <UIcon name="i-heroicons-user-group" class="w-4 h-4 text-slate-400" />
                  <span class="text-sm font-medium">{{ courseDate.bookingCount }}</span>
                </div>
              </UTooltip>
              <div v-else class="flex items-center gap-1 text-slate-500">
                <UIcon name="i-heroicons-user-group" class="w-4 h-4 text-slate-400" />
                <span class="text-sm font-medium">0</span>
              </div>
              <UBadge v-if="courseDate.cancelled" color="red" variant="soft" size="xs">Abgesagt</UBadge>
            </div>
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Course, CourseDate, Contract } from '~/types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const { formatContractMonthlyPrice, todayIso, getWeekMonday } = useHelpers()

interface DashboardStat {
  label: string
  value: number | string
  icon: string
  bgClass: string
  iconClass: string
}

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
