<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
      <div class="flex items-center gap-3">
        <h1 class="text-2xl font-bold text-slate-800">Kalender</h1>
        <UButton
          color="blue"
          variant="soft"
          size="sm"
          icon="i-heroicons-link"
          label="Abonnieren"
          @click="showCalendarSubscription = true"
        />
      </div>
      <div class="flex items-center gap-2">
        <UButtonGroup size="xs">
          <UButton
            :variant="viewMode === 'day' ? 'solid' : 'outline'"
            label="Tag"
            @click="viewMode = 'day'"
          />
          <UButton
            :variant="viewMode === 'week' ? 'solid' : 'outline'"
            label="Woche"
            @click="viewMode = 'week'"
          />
        </UButtonGroup>
        <UButton icon="i-heroicons-chevron-left" variant="ghost" size="sm" @click="prev" />
        <span class="text-sm font-medium text-slate-600 min-w-[100px] sm:min-w-[160px] text-center">
          {{ viewMode === 'day' ? formatDate(currentDay) : `${formatDate(weekStart)} – ${formatDate(weekEnd)}` }}
        </span>
        <UButton icon="i-heroicons-chevron-right" variant="ghost" size="sm" @click="next" />
        <UButton variant="outline" size="sm" label="Heute" class="ml-1" @click="goToday" />
      </div>
    </div>

    <UModal v-model="showCalendarSubscription">
      <UCard>
        <template #header>
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-slate-800">Kalender abonnieren</h2>
              <p class="text-sm text-slate-500">
                Mit diesem Link kannst du deine gebuchten Kurse in deiner Kalender-App als Abo einbinden.
              </p>
            </div>

            <UBadge color="blue" variant="soft" size="sm">ICS Feed</UBadge>
          </div>
        </template>

        <div class="space-y-4">
          <UAlert
            color="amber"
            variant="soft"
            icon="i-heroicons-lock-closed"
            title="Privater Link"
            description="Wenn du ihn teilst, können andere deine gebuchten Kurse sehen."
          />

          <div class="flex flex-col gap-3">
            <UInput
              :model-value="calendarSubscriptionUrl"
              readonly
              class="flex-1"
              placeholder="Kalender-Link wird geladen ..."
            />
            <div class="flex flex-wrap gap-2">
              <UButton
                label="Kopieren"
                icon="i-heroicons-clipboard-document"
                :disabled="!calendarSubscriptionUrl"
                @click="copyCalendarUrl"
              />
              <UButton
                label="Öffnen"
                icon="i-heroicons-arrow-top-right-on-square"
                variant="soft"
                :disabled="!calendarSubscriptionWebcalUrl"
                @click="openCalendarUrl"
              />
            </div>
          </div>

          <p class="text-xs text-slate-500">
            Änderungen an Buchungen und Kursabsagen erscheinen, sobald deine Kalender-App das Abo aktualisiert.
          </p>
        </div>

        <template #footer>
          <div class="flex justify-end">
            <UButton label="Schließen" color="gray" variant="ghost" @click="showCalendarSubscription = false" />
          </div>
        </template>
      </UCard>
    </UModal>

    <UModal v-model="showBookingModal">
      <UCard v-if="bookingCourseDate">
        <template #header>
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h2 class="text-lg font-semibold text-slate-800">
                {{ formatCourseTitleWithLevel(bookingCourseDate.courseType?.name, bookingCourseDate.level) }}
              </h2>
              <p class="mt-1 text-sm text-slate-500">
                {{ formatDate(bookingCourseDate.date) }} · {{ bookingCourseDate.startTime }} – {{ bookingCourseDate.endTime }}
              </p>
            </div>
            <UBadge color="primary" variant="soft" size="sm">Buchung</UBadge>
          </div>
        </template>

        <div class="space-y-4" data-testid="booking-modal">
          <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
            <p>Trainer: {{ bookingCourseDate.trainer?.fullName || 'Wird noch zugewiesen' }}</p>
          </div>

          <div v-if="dogs.length === 0" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            Für die Buchung ist zuerst ein Hund erforderlich.
          </div>
          <div v-else-if="dogs.length === 1" class="rounded-lg border border-komm-200 bg-komm-50 px-3 py-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-komm-700">Hund</p>
            <p class="mt-1 text-sm font-medium text-komm-900">{{ dogs[0].name }}</p>
          </div>
          <UFormGroup v-else label="Hund auswählen">
            <select
              v-model="bookingDogId"
              data-testid="booking-dog-select"
              class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm outline-none transition focus:border-komm-400 focus:ring-2 focus:ring-komm-200"
            >
              <option value="">Hund wählen</option>
              <option v-for="dog in dogs" :key="dog.id" :value="dog.id">
                {{ dog.name }}
              </option>
            </select>
          </UFormGroup>
        </div>

        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton
              label="Abbrechen"
              color="gray"
              variant="ghost"
              :disabled="bookingInFlight"
              @click="closeBookingModal"
            />
            <UButton
              label="Buchen"
              :loading="bookingInFlight"
              :disabled="!selectedBookingDogId"
              data-testid="confirm-booking"
              @click="confirmBooking"
            />
          </div>
        </template>
      </UCard>
    </UModal>

    <AppSkeletonCalendar
      v-if="loading"
      :days="viewMode === 'week' ? 7 : 1"
      :cards-per-day="viewMode === 'week' ? 2 : 4"
      :columns-class="viewMode === 'week' ? 'grid grid-cols-1 gap-3 lg:grid-cols-7' : 'grid grid-cols-1 gap-3'"
      :day-class="viewMode === 'week' ? 'min-h-[180px]' : ''"
    />
    <AppCalendarTimeline
      v-else
      :days="visibleDays"
      :view-mode="viewMode"
      empty-label="–"
      :event-class="calendarCardClass"
    >
      <template #event="{ courseDate: cd, condensed }">
        <div class="flex h-full min-w-0 flex-col gap-1">
          <div class="flex items-start justify-between gap-2">
            <p class="min-w-0 font-semibold leading-4" :class="cd.cancelled ? 'text-red-600 line-through' : 'text-slate-700'">
              <span
                class="block truncate"
                :class="condensed ? 'text-[11px]' : 'text-[13px] sm:text-sm'"
                :title="formatCourseTitleWithLevel(cd.courseType?.name, cd.level)"
              >
                {{ formatCourseTitleWithLevel(cd.courseType?.name, cd.level) }}
              </span>
            </p>
            <UBadge v-if="cd.cancelled" color="red" variant="soft" size="xs">Abgesagt</UBadge>
            <UBadge v-else-if="cd.booked" color="primary" variant="soft" size="xs">Gebucht</UBadge>
          </div>

          <template v-if="condensed">
            <div class="space-y-0.5 text-[10px] leading-4 text-slate-500">
              <p>{{ cd.startTime }} – {{ cd.endTime }}</p>
              <p class="break-words">{{ cd.trainer?.fullName || 'Trainer offen' }}</p>
            </div>
          </template>
          <template v-else>
            <p class="text-[11px] font-medium text-slate-500">{{ cd.startTime }} – {{ cd.endTime }}</p>
            <p class="text-[11px] leading-4 text-slate-500">
              Trainer: {{ cd.trainer?.fullName || 'Wird noch zugewiesen' }}
            </p>
          </template>

          <div v-if="cd.cancelled && !condensed" class="mt-auto text-[11px] font-medium text-red-600">
            Dieser Termin findet nicht statt.
          </div>
          <div v-else-if="cd.booked" class="mt-auto space-y-1">
            <p class="truncate text-[11px] font-medium text-slate-800">
              <span class="font-normal text-slate-500">für </span>{{ bookedDogLabel(cd) }}
            </p>
            <button
              v-if="!cd.bookingWindowClosed"
              type="button"
              class="inline-flex w-full items-center justify-center rounded-md border border-red-200 bg-red-50 px-2 py-1 text-[10px] font-semibold text-red-700 shadow-sm transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-200 focus:ring-offset-1"
              @click="cancelBooking(cd)"
            >
              {{ condensed ? 'Storno' : 'Stornieren' }}
            </button>
          </div>
          <div v-else-if="cd.bookingWindowClosed" class="mt-auto">
            <p v-if="!condensed" class="text-[11px] font-medium text-slate-400">
              Buchung geschlossen
            </p>
          </div>
          <div v-else class="mt-auto space-y-1">
            <p v-if="dogs.length === 0" class="text-[11px] text-slate-400">
              Kein Hund verfügbar
            </p>
            <template v-else>
              <p v-if="!condensed" class="truncate text-[10px] font-medium text-slate-500">
                {{ bookingSummaryLabel(condensed) }}
              </p>
              <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-md bg-komm-700 px-2 py-1 text-[10px] font-semibold text-white shadow-sm transition hover:bg-komm-800 focus:outline-none focus:ring-2 focus:ring-komm-300 focus:ring-offset-1"
                :data-testid="`open-booking-${cd.id}`"
                @click="openBookingModal(cd)"
              >
                {{ bookingTriggerLabel(condensed) }}
              </button>
            </template>
          </div>
        </div>
      </template>
    </AppCalendarTimeline>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, CalendarSubscriptionResponse, CourseDate, Dog } from '~/types'

definePageMeta({ layout: 'customer' })

const api = useApi()
const toast = useToast()
const runtimeConfig = useRuntimeConfig()
const { addDaysToIso, dayNameShort, formatCourseTitleWithLevel, formatDate, formatDateShort, getIsoDayOfWeek, getWeekMonday, todayIso } = useHelpers()

const isMobile = ref(false)
onMounted(() => {
  isMobile.value = window.innerWidth < 1024
})
const viewMode = ref<'day' | 'week'>('week')
watchEffect(() => { viewMode.value = isMobile.value ? 'day' : 'week' })

const currentDay = ref(todayIso())
const currentMonday = ref(getWeekMonday(currentDay.value))
const courseDates = ref<CourseDate[]>([])
const dogs = ref<Dog[]>([])
const calendarSubscriptionPath = ref('')
const showCalendarSubscription = ref(false)
const showBookingModal = ref(false)
const bookingCourseDate = ref<CourseDate | null>(null)
const bookingDogId = ref('')
const bookingInFlight = ref(false)
const loading = ref(true)
const calendarSubscriptionUrl = computed(() => {
  if (!calendarSubscriptionPath.value) return ''

  const baseUrl = runtimeConfig.public.apiBaseUrl || (import.meta.client ? window.location.origin : '')
  if (!baseUrl) return calendarSubscriptionPath.value

  try {
    return new URL(calendarSubscriptionPath.value, baseUrl).toString()
  } catch {
    return calendarSubscriptionPath.value
  }
})
const calendarSubscriptionWebcalUrl = computed(() =>
  calendarSubscriptionUrl.value.replace(/^https?/, 'webcal'),
)

const selectedBookingDogId = computed(() => {
  if (dogs.value.length === 0) return ''
  if (dogs.value.length === 1) return dogs.value[0]?.id ?? ''
  return bookingDogId.value
})

function bookedDogLabel(cd: CourseDate): string {
  const b = cd.bookings?.[0]
  if (!b) return '–'
  if (b.dogName) return b.dogName
  const dog = dogs.value.find(d => d.id === b.dogId)
  return dog?.name ?? '–'
}

const weekStart = computed(() => currentMonday.value)
const weekEnd = computed(() => addDaysToIso(currentMonday.value, 6))

const weekDays = computed(() => {
  const days = []
  const today = todayIso()
  for (let i = 0; i < 7; i++) {
    const dateStr = addDaysToIso(currentMonday.value, i)
    days.push({
      date: dateStr,
      label: dayNameShort(i + 1),
      dateShort: formatDateShort(dateStr),
      isToday: dateStr === today,
      courseDates: courseDates.value
        .filter(cd => cd.date === dateStr)
        .sort((a, b) => a.startTime.localeCompare(b.startTime)),
    })
  }
  return days
})

const singleDay = computed(() => {
  const today = todayIso()
  const dow = getIsoDayOfWeek(currentDay.value)
  return {
    date: currentDay.value,
    label: dayNameShort(dow),
    dateShort: formatDateShort(currentDay.value),
    isToday: currentDay.value === today,
    courseDates: courseDates.value
      .filter(cd => cd.date === currentDay.value)
      .sort((a, b) => a.startTime.localeCompare(b.startTime)),
  }
})

const visibleDays = computed(() => viewMode.value === 'week' ? weekDays.value : [singleDay.value])

function calendarCardClass(cd: CourseDate) {
  if (cd.cancelled) return 'bg-red-50 border-red-200 opacity-60'
  if (cd.booked) return 'bg-komm-50/90 border-komm-200'
  return 'bg-white/95 border-slate-200'
}

function prev() {
  if (viewMode.value === 'week') {
    currentMonday.value = addDaysToIso(currentMonday.value, -7)
  } else {
    currentDay.value = addDaysToIso(currentDay.value, -1)
    syncMondayFromDay()
  }
}

function next() {
  if (viewMode.value === 'week') {
    currentMonday.value = addDaysToIso(currentMonday.value, 7)
  } else {
    currentDay.value = addDaysToIso(currentDay.value, 1)
    syncMondayFromDay()
  }
}

function goToday() {
  currentDay.value = todayIso()
  currentMonday.value = getWeekMonday(currentDay.value)
}

function syncMondayFromDay() {
  const newMonday = getWeekMonday(currentDay.value)
  if (newMonday !== currentMonday.value) {
    currentMonday.value = newMonday
  }
}

function bookingSummaryLabel(condensed: boolean): string {
  if (dogs.value.length === 1) return `für ${dogs.value[0]?.name ?? 'Hund'}`
  if (condensed) return `${dogs.value.length} Hunde`
  return `${dogs.value.length} Hunde verfügbar`
}

function bookingTriggerLabel(condensed: boolean): string {
  if (dogs.value.length > 1 && !condensed) return 'Hund wählen'
  return 'Buchen'
}

function openBookingModal(cd: CourseDate): void {
  if (dogs.value.length === 0 || cd.cancelled || cd.booked || cd.bookingWindowClosed) return

  bookingCourseDate.value = cd
  bookingDogId.value = dogs.value.length === 1 ? (dogs.value[0]?.id ?? '') : ''
  showBookingModal.value = true
}

function closeBookingModal(): void {
  if (bookingInFlight.value) return
  showBookingModal.value = false
  bookingCourseDate.value = null
  bookingDogId.value = ''
}

async function bookDate(cd: CourseDate, dogId = selectedBookingDogId.value): Promise<boolean> {
  if (!dogId) {
    toast.add({ title: 'Bitte zuerst einen Hund auswählen.', color: 'red' })
    return false
  }
  try {
    await api.post(`/api/customer/calendar/course-dates/${cd.id}/book`, { dogId })
    toast.add({ title: 'Termin gebucht', color: 'green' })
    await loadCalendar()
    return true
  } catch (cause) {
    toast.add({ title: extractApiErrorMessage(cause, 'Die Buchung konnte nicht gespeichert werden.', { preferFieldSummary: false }), color: 'red' })
    return false
  }
}

async function confirmBooking(): Promise<void> {
  if (!bookingCourseDate.value || !selectedBookingDogId.value) return

  bookingInFlight.value = true
  try {
    const didBook = await bookDate(bookingCourseDate.value, selectedBookingDogId.value)
    if (didBook) {
      bookingInFlight.value = false
      closeBookingModal()
      return
    }
  } finally {
    bookingInFlight.value = false
  }
}

async function cancelBooking(cd: CourseDate) {
  if (!cd.bookings?.length) return
  const dogId = cd.bookings[0].dogId
  if (!dogId) return
  try {
    await api.del(`/api/customer/calendar/course-dates/${cd.id}/book?dogId=${dogId}`)
    toast.add({ title: 'Buchung storniert', color: 'amber' })
    await loadCalendar()
  } catch (cause) {
    toast.add({ title: extractApiErrorMessage(cause, 'Die Stornierung konnte nicht gespeichert werden.', { preferFieldSummary: false }), color: 'red' })
  }
}

async function loadCalendar(): Promise<void> {
  loading.value = true
  try {
    const res = await api.get<ApiListResponse<CourseDate>>(`/api/customer/calendar?week=${currentMonday.value}`)
    courseDates.value = res.items
  } finally {
    loading.value = false
  }
}

async function loadCalendarSubscription(): Promise<void> {
  const res = await api.get<CalendarSubscriptionResponse>('/api/customer/calendar/subscription')
  calendarSubscriptionPath.value = res.path
}

async function copyCalendarUrl(): Promise<void> {
  if (!calendarSubscriptionUrl.value || !navigator.clipboard) return

  try {
    await navigator.clipboard.writeText(calendarSubscriptionUrl.value)
    toast.add({ title: 'Kalender-Link kopiert', color: 'green' })
  } catch {
    toast.add({ title: 'Link konnte nicht kopiert werden', color: 'red' })
  }
}

function openCalendarUrl(): void {
  if (!calendarSubscriptionWebcalUrl.value) return
  window.location.href = calendarSubscriptionWebcalUrl.value
}

watch(showBookingModal, (open) => {
  if (!open && !bookingInFlight.value) {
    bookingCourseDate.value = null
    bookingDogId.value = ''
  }
})

watch(currentMonday, () => loadCalendar())

onMounted(async () => {
  const [, dogRes] = await Promise.all([
    loadCalendar(),
    api.get<ApiListResponse<Dog>>('/api/customer/dogs'),
    loadCalendarSubscription(),
  ])
  dogs.value = dogRes.items
})
</script>
