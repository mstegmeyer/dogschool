<template>
  <div>
    <USkeleton v-if="loading" class="mb-2 h-8 w-56 rounded-md" />
    <h1 v-else class="text-2xl font-bold text-slate-800 mb-2">
      Hallo, {{ user?.name || 'Willkommen' }}!
    </h1>
    <USkeleton v-if="loading" class="mb-6 h-4 w-40 rounded-md" />
    <p v-else class="text-slate-500 mb-6">Schön, dass du da bist.</p>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(22rem,0.95fr)]">
      <UCard class="xl:min-h-[32rem]">
        <template #header>
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h3 class="font-semibold text-slate-800">Nächste Termine</h3>
              <p class="text-xs text-slate-400 mt-1">Deine kommenden Buchungen und offenen Termine der nächsten 14 Tage.</p>
            </div>
            <UButton variant="ghost" size="xs" to="/customer/calendar">Kalender</UButton>
          </div>
        </template>
        <AppSkeletonCollection
          v-if="loading"
          :show-desktop-table="false"
          :mobile-cards="5"
          :meta-columns="0"
          :content-lines="2"
          :show-badge="true"
        />
        <div v-else-if="upcomingDates.length === 0" class="text-sm text-slate-400 py-4 text-center">
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
                color="primary"
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

      <UCard class="xl:min-h-[32rem]">
        <template #header>
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h3 class="font-semibold text-slate-800">Mitteilungen</h3>
              <p class="text-xs text-slate-400 mt-1">Wichtige Hinweise zu deinen Kursen und allgemeine Updates.</p>
            </div>
            <UButton variant="ghost" size="xs" to="/customer/notifications">Alle</UButton>
          </div>
        </template>
        <AppSkeletonCollection
          v-if="loading"
          :show-desktop-table="false"
          :mobile-cards="5"
          :meta-columns="0"
          :content-lines="3"
          :show-badge="false"
        />
        <div v-else-if="notifications.length === 0" class="text-sm text-slate-400 py-4 text-center">
          Keine neuen Mitteilungen
        </div>
        <div v-else class="divide-y divide-slate-100">
          <button
            v-for="n in notifications.slice(0, 4)"
            :key="n.id"
            type="button"
            class="-mx-2 block w-[calc(100%+1rem)] rounded-lg px-2 py-3 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-komm-500"
            @click="openNotificationModal(n)"
          >
            <div class="flex items-center gap-1.5">
              <UIcon v-if="n.isPinned" name="i-heroicons-map-pin" class="h-3.5 w-3.5 shrink-0 text-indigo-600" />
              <h4 class="text-sm font-semibold text-slate-700">{{ n.title }}</h4>
            </div>
            <p v-if="n.isGlobal" class="mt-0.5 text-xs text-amber-700">Alle Kurse</p>
            <p v-else-if="n.courses.length > 0" class="mt-0.5 text-xs text-komm-700">{{ formatNotificationCourses(n.courses) }}</p>
            <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ n.message }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ formatDateTime(n.createdAt) }}</p>
          </button>
        </div>
      </UCard>
    </div>

    <section class="mt-8">
      <div class="mb-3">
        <h3 class="font-semibold text-slate-800">Überblick</h3>
        <p class="text-sm text-slate-500">Die wichtigsten Kennzahlen findest du hier als schnelle Zusammenfassung.</p>
      </div>

      <AppSkeletonStatGrid
        v-if="loading"
        :count="3"
        centered
        grid-classes="grid grid-cols-1 gap-4 sm:grid-cols-3"
      />
      <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <UCard>
          <div class="text-center">
            <p class="text-3xl font-bold" :class="creditBalance >= 0 ? 'text-komm-600' : 'text-red-500'">
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
    </section>

    <UModal v-model="showNotificationModal">
      <UCard v-if="selectedNotification">
        <template #header>
          <div class="flex items-center justify-between gap-3">
            <h3 class="font-semibold text-slate-800">Mitteilung</h3>
            <UButton
              color="gray"
              variant="ghost"
              icon="i-heroicons-x-mark"
              aria-label="Mitteilung schliessen"
              @click="closeNotificationModal"
            />
          </div>
        </template>
        <AppNotificationDetail :notification="selectedNotification" />
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, BookingResponse, Dog, Course, CourseDate, Notification as AppNotification } from '~/types'

definePageMeta({ layout: 'customer' })

const { user, fetchProfile } = useAuth()
const api = useApi()
const {
  dayName,
  formatDate,
  formatDateTime,
  formatNotificationCourses,
  todayIso,
} = useHelpers()

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

const upcomingDates = computed(() =>
  calendarItems.value
    .filter(cd => cd.subscribed || cd.booked)
    .slice(0, 6),
)

function openNotificationModal(notification: AppNotification): void {
  selectedNotification.value = notification
  showNotificationModal.value = true
}

function closeNotificationModal(): void {
  showNotificationModal.value = false
  selectedNotification.value = null
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
