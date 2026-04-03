<template>
  <div>
    <USkeleton v-if="loading" class="mb-2 h-8 w-56 rounded-md" />
    <h1 v-else class="text-2xl font-bold text-slate-800 mb-2">
      Hallo, {{ user?.name || 'Willkommen' }}!
    </h1>
    <USkeleton v-if="loading" class="mb-6 h-4 w-40 rounded-md" />
    <p v-else class="text-slate-500 mb-6">Schön, dass du da bist.</p>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(22rem,0.95fr)]">
      <UpcomingDatesCard
        :loading="loading"
        :upcoming-dates="upcomingDates"
        :dogs="dogs"
        :dog-options="dogOptions"
        :dog-id-by-course-date="dogIdByCourseDate"
        :booking-in-progress="bookingInProgress"
        @update:dog-id="setDogId"
        @book="quickBook"
      />
      <NotificationsCard :loading="loading" :notifications="notifications" @select="openNotificationModal" />
    </div>

    <OverviewStats
      :loading="loading"
      :credit-balance="creditBalance"
      :subscribed-course-count="subscribedCourses.length"
      :dog-count="dogs.length"
    />

    <NotificationDetailModal v-model="showNotificationModal" :notification="selectedNotification" />
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, BookingResponse, Dog, Course, CourseDate, Notification as AppNotification } from '~/types'
import NotificationDetailModal from './components/NotificationDetailModal.vue'
import NotificationsCard from './components/NotificationsCard.vue'
import OverviewStats from './components/OverviewStats.vue'
import UpcomingDatesCard from './components/UpcomingDatesCard.vue'

definePageMeta({ layout: 'customer' })

const { user, fetchProfile } = useAuth()
const api = useApi()
const { todayIso } = useHelpers()

const toast = useToast()

const creditBalance = ref(0)
const dogs = ref<Dog[]>([])
const subscribedCourses = ref<Course[]>([])
const calendarItems = ref<CourseDate[]>([])
const notifications = ref<AppNotification[]>([])
const bookingInProgress = ref<string | null>(null)
const selectedNotification = ref<AppNotification | null>(null)
const loading = ref(true)
const showNotificationModal = ref(false)

const dogIdByCourseDate = reactive<Record<string, string>>({})
const dogOptions = computed(() => dogs.value.map(d => ({ label: d.name, value: d.id })))

function dogIdForBooking(cd: CourseDate): string {
  if (dogs.value.length === 0) return ''
  if (dogs.value.length === 1) return dogs.value[0]?.id ?? ''
  return dogIdByCourseDate[cd.id] || ''
}

const upcomingDates = computed(() =>
  calendarItems.value
    .filter(cd => cd.subscribed || cd.booked)
    .slice(0, 6),
)

function setDogId(payload: { courseDateId: string; dogId: string }): void {
  dogIdByCourseDate[payload.courseDateId] = payload.dogId
}

function openNotificationModal(notification: AppNotification): void {
  selectedNotification.value = notification
  showNotificationModal.value = true
}

watch(showNotificationModal, (isOpen) => {
  if (!isOpen) {
    selectedNotification.value = null
  }
})

async function quickBook(cd: CourseDate) {
  const dogId = dogIdForBooking(cd)
  if (!dogId) {
    toast.add({ title: 'Bitte zuerst einen Hund auswählen.', color: 'red' })
    return
  }
  bookingInProgress.value = cd.id
  try {
    const res = await api.post<BookingResponse>(`/api/customer/calendar/course-dates/${cd.id}/book`, { dogId })
    toast.add({ title: 'Termin gebucht', color: 'green' })
    creditBalance.value = res.creditBalance
    delete dogIdByCourseDate[cd.id]
    await reloadCalendar()
  } catch (cause) {
    toast.add({ title: extractApiErrorMessage(cause, 'Die Buchung konnte nicht gespeichert werden.', { preferFieldSummary: false }), color: 'red' })
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
  try {
    await fetchProfile()

    const today = todayIso()
    const [creditRes, dogRes, courseRes, calRes, notifRes] = await Promise.all([
      safeGet<{ balance: number }>('/api/customer/credits', { balance: 0 }),
      safeGet<ApiListResponse<Dog>>('/api/customer/dogs', { items: [] }),
      safeGet<ApiListResponse<Course>>('/api/customer/courses/subscribed', { items: [] }),
      safeGet<ApiListResponse<CourseDate>>(`/api/customer/calendar?from=${today}&days=14`, { items: [] }),
      safeGet<ApiListResponse<AppNotification>>('/api/customer/notifications', { items: [] }),
    ])

    creditBalance.value = creditRes.balance
    dogs.value = dogRes.items
    subscribedCourses.value = courseRes.items
    calendarItems.value = calRes.items
    notifications.value = notifRes.items
  } finally {
    loading.value = false
  }
})
</script>
