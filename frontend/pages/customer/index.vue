<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">
      Hallo, {{ user?.name || 'Willkommen' }}!
    </h1>
    <p class="text-slate-500 mb-6">Schön, dass du da bist.</p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
      <UCard>
        <div class="text-center">
          <p class="text-3xl font-bold" :class="creditBalance >= 0 ? 'text-green-600' : 'text-red-500'">
            {{ creditBalance }}
          </p>
          <p class="text-xs text-slate-400 mt-1">Guthaben (Credits)</p>
        </div>
      </UCard>
      <UCard>
        <div class="text-center">
          <p class="text-3xl font-bold text-blue-600">{{ subscribedCourses.length }}</p>
          <p class="text-xs text-slate-400 mt-1">Abonnierte Kurse</p>
        </div>
      </UCard>
      <UCard>
        <div class="text-center">
          <p class="text-3xl font-bold text-purple-600">{{ dogs.length }}</p>
          <p class="text-xs text-slate-400 mt-1">Registrierte Hunde</p>
        </div>
      </UCard>
    </div>

    <UCard class="mb-8">
      <template #header>
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-slate-800">Verträge</h3>
          <UButton variant="ghost" size="xs" to="/customer/contracts">Alle</UButton>
        </div>
      </template>
      <div v-if="activeContracts.length === 0" class="text-sm text-slate-400 py-4 text-center">
        Keine aktiven Verträge
      </div>
      <ul v-else class="divide-y divide-slate-100">
        <li v-for="c in activeContracts.slice(0, 4)" :key="c.id" class="py-3 flex flex-wrap items-center justify-between gap-2">
          <div>
            <p class="text-sm font-medium text-slate-700">
              {{ c.dogName || 'Hund' }} · {{ c.coursesPerWeek }}× / Woche
            </p>
            <p class="text-xs text-slate-400">
              {{ formatContractMonthlyPrice(c.price, c.type) }}
              <span v-if="c.startDate"> · seit {{ formatDate(c.startDate) }}</span>
            </p>
          </div>
          <UBadge color="green" variant="soft" size="xs">{{ contractStateLabel(c.state) }}</UBadge>
        </li>
      </ul>
    </UCard>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <UCard>
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Nächste Termine</h3>
            <UButton variant="ghost" size="xs" to="/customer/calendar">Kalender</UButton>
          </div>
        </template>
        <div v-if="upcomingDates.length === 0" class="text-sm text-slate-400 py-4 text-center">
          Keine anstehenden Termine
        </div>
        <div v-else class="divide-y divide-slate-100">
          <div v-for="cd in upcomingDates" :key="cd.id" class="py-3">
            <div class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <p class="text-sm font-medium text-slate-700">
                  {{ cd.courseType?.name || 'Kurs' }}
                </p>
                <p class="text-xs text-slate-400">
                  {{ dayName(cd.dayOfWeek) }}, {{ formatDate(cd.date) }} · {{ cd.startTime }} – {{ cd.endTime }}
                </p>
              </div>
              <div v-if="cd.cancelled" class="shrink-0">
                <UBadge color="red" variant="soft" size="xs">Abgesagt</UBadge>
              </div>
              <UBadge
                v-else-if="cd.booked"
                color="green"
                variant="soft"
                size="xs"
                class="shrink-0 max-w-[11rem] whitespace-normal text-center leading-tight"
              >
                Gebucht für {{ bookedDogLabel(cd) }}
              </UBadge>
              <div v-else-if="cd.subscribed && !cd.bookingWindowClosed" class="flex items-center gap-1.5 shrink-0">
                <USelectMenu
                  v-if="dogs.length > 1"
                  v-model="dogIdByCourseDate[cd.id]"
                  :options="dogOptions"
                  value-attribute="value"
                  placeholder="Hund …"
                  size="xs"
                  class="w-24"
                />
                <UButton
                  size="xs"
                  label="Buchen"
                  :disabled="!dogIdForBooking(cd)"
                  :loading="bookingInProgress === cd.id"
                  @click="quickBook(cd)"
                />
              </div>
            </div>
          </div>
        </div>
      </UCard>

      <UCard>
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Mitteilungen</h3>
            <UButton variant="ghost" size="xs" to="/customer/notifications">Alle</UButton>
          </div>
        </template>
        <div v-if="notifications.length === 0" class="text-sm text-slate-400 py-4 text-center">
          Keine neuen Mitteilungen
        </div>
        <div v-else class="divide-y divide-slate-100">
          <div v-for="n in notifications.slice(0, 4)" :key="n.id" class="py-3">
            <h4 class="text-sm font-semibold text-slate-700">{{ n.title }}</h4>
            <p v-if="n.isGlobal" class="text-xs text-amber-700 mt-0.5">Alle Kurse</p>
            <p v-else-if="n.courses.length > 0" class="text-xs text-green-700 mt-0.5">{{ formatNotificationCourses(n.courses) }}</p>
            <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ n.message }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ formatDateTime(n.createdAt) }}</p>
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, BookingResponse, Dog, Course, CourseDate, Contract, Notification as AppNotification } from '~/types'

definePageMeta({ layout: 'customer' })

const { user, fetchProfile } = useAuth()
const api = useApi()
const {
  dayName,
  formatDate,
  formatDateTime,
  formatNotificationCourses,
  formatContractMonthlyPrice,
  contractStateLabel,
  todayIso,
} = useHelpers()

const toast = useToast()

const creditBalance = ref(0)
const dogs = ref<Dog[]>([])
const subscribedCourses = ref<Course[]>([])
const calendarItems = ref<CourseDate[]>([])
const contracts = ref<Contract[]>([])
const notifications = ref<AppNotification[]>([])
const bookingInProgress = ref<string | null>(null)

const dogIdByCourseDate = reactive<Record<string, string>>({})
const dogOptions = computed(() => dogs.value.map(d => ({ label: d.name, value: d.id })))

function dogIdForBooking(cd: CourseDate): string {
  if (dogs.value.length === 0) return ''
  if (dogs.value.length === 1) return dogs.value[0].id
  return dogIdByCourseDate[cd.id] || ''
}

function bookedDogLabel(cd: CourseDate): string {
  const b = cd.bookings?.[0]
  if (!b) return '–'
  if (b.dogName) return b.dogName
  const dog = dogs.value.find(d => d.id === b.dogId)
  return dog?.name ?? '–'
}

const activeContracts = computed(() => contracts.value.filter(c => c.state === 'ACTIVE'))

const upcomingDates = computed(() =>
  calendarItems.value
    .filter(cd => cd.subscribed || cd.booked)
    .slice(0, 5),
)

async function quickBook(cd: CourseDate) {
  const dogId = dogIdForBooking(cd)
  if (!dogId) return
  bookingInProgress.value = cd.id
  try {
    const res = await api.post<BookingResponse>(`/api/customer/calendar/course-dates/${cd.id}/book`, { dogId })
    toast.add({ title: 'Termin gebucht', color: 'green' })
    creditBalance.value = res.creditBalance
    delete dogIdByCourseDate[cd.id]
    await reloadCalendar()
  } catch {
    toast.add({ title: 'Buchung fehlgeschlagen', color: 'red' })
  } finally {
    bookingInProgress.value = null
  }
}

async function reloadCalendar(): Promise<void> {
  const today = todayIso()
  const res = await safeGet<ApiListResponse<CourseDate>>(`/api/customer/calendar?from=${today}&days=14`, { items: [] })
  calendarItems.value = res.items
}

async function safeGet<T>(url: string, fallback: T): Promise<T> {
  try { return await api.get<T>(url) }
  catch { return fallback }
}

onMounted(async () => {
  await fetchProfile()

  const today = todayIso()
  const [creditRes, dogRes, courseRes, calRes, contractRes, notifRes] = await Promise.all([
    safeGet<{ balance: number }>('/api/customer/credits', { balance: 0 }),
    safeGet<ApiListResponse<Dog>>('/api/customer/dogs', { items: [] }),
    safeGet<ApiListResponse<Course>>('/api/customer/courses/subscribed', { items: [] }),
    safeGet<ApiListResponse<CourseDate>>(`/api/customer/calendar?from=${today}&days=14`, { items: [] }),
    safeGet<ApiListResponse<Contract>>('/api/customer/contracts', { items: [] }),
    safeGet<ApiListResponse<AppNotification>>('/api/customer/notifications', { items: [] }),
  ])

  creditBalance.value = creditRes.balance
  dogs.value = dogRes.items
  subscribedCourses.value = courseRes.items
  calendarItems.value = calRes.items
  contracts.value = contractRes.items
  notifications.value = notifRes.items
})
</script>
