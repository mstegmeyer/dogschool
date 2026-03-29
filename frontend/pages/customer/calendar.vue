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

    <AppSkeletonCalendar
      v-if="loading"
      :days="viewMode === 'week' ? 7 : 1"
      :cards-per-day="viewMode === 'week' ? 2 : 4"
      :columns-class="viewMode === 'week' ? 'grid grid-cols-1 gap-3 lg:grid-cols-7' : 'grid grid-cols-1 gap-3'"
      :day-class="viewMode === 'week' ? 'min-h-[180px]' : ''"
    />
    <div v-else :class="viewMode === 'week' ? 'grid grid-cols-7 gap-3' : ''">
      <div v-for="day in visibleDays" :key="day.date" :class="viewMode === 'week' ? 'min-h-[180px]' : ''">
        <div class="text-center mb-2">
          <p class="text-xs font-semibold text-slate-500 uppercase">{{ day.label }}</p>
          <p class="text-sm font-medium" :class="day.isToday ? 'text-komm-600' : 'text-slate-700'">
            {{ day.dateShort }}
          </p>
        </div>
        <div class="space-y-2">
          <div
            v-for="cd in day.courseDates"
            :key="cd.id"
            class="rounded-lg border p-2 text-xs transition-colors"
            :class="cardClass(cd)"
          >
            <p class="font-semibold truncate" :class="cd.cancelled ? 'text-red-600 line-through' : 'text-slate-700'">
              {{ cd.courseType?.name || 'Kurs' }}
            </p>
            <p class="text-slate-400 mt-0.5">{{ cd.startTime }} – {{ cd.endTime }}</p>
            <p class="mt-1 text-slate-500">
              Trainer: {{ cd.trainer?.fullName || 'Wird noch zugewiesen' }}
            </p>

            <template v-if="cd.cancelled">
              <UBadge color="red" variant="soft" size="xs" class="mt-1">Abgesagt</UBadge>
            </template>
            <template v-else-if="cd.booked">
              <div class="mt-2 space-y-1">
                <UBadge color="primary" variant="soft" size="xs">Gebucht</UBadge>
                <p class="text-xs font-medium text-slate-800">
                  <span class="text-slate-500 font-normal">für </span>{{ bookedDogLabel(cd) }}
                </p>
                <UButton
                  v-if="!cd.bookingWindowClosed"
                  size="xs"
                  color="red"
                  variant="ghost"
                  label="Stornieren"
                  block
                  @click="cancelBooking(cd)"
                />
              </div>
            </template>
            <template v-else-if="!cd.bookingWindowClosed">
              <div class="mt-2">
                <USelectMenu
                  v-if="dogs.length > 1"
                  v-model="dogIdByCourseDate[cd.id]"
                  :options="dogOptions"
                  value-attribute="value"
                  placeholder="Hund wählen"
                  size="xs"
                  class="mb-1"
                />
                <p v-else-if="dogs.length === 1" class="text-xs text-slate-600 mb-1">
                  {{ dogs[0].name }}
                </p>
                <UButton
                  size="xs"
                  label="Buchen"
                  block
                  :disabled="!dogIdForBooking(cd)"
                  @click="bookDate(cd)"
                />
              </div>
            </template>
          </div>
          <div v-if="day.courseDates.length === 0" class="text-center py-6">
            <p class="text-xs text-slate-300">–</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, CalendarSubscriptionResponse, CourseDate, Dog } from '~/types'

definePageMeta({ layout: 'customer' })

const api = useApi()
const toast = useToast()
const runtimeConfig = useRuntimeConfig()
const { addDaysToIso, dayNameShort, formatDate, formatDateShort, getIsoDayOfWeek, getWeekMonday, todayIso } = useHelpers()

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
const loading = ref(true)

/** Pro Kurstermin gewählter Hund (nur relevant bei mehreren Hunden). */
const dogIdByCourseDate = reactive<Record<string, string>>({})

const dogOptions = computed(() => dogs.value.map(d => ({ label: d.name, value: d.id })))
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

function cardClass(cd: CourseDate) {
  if (cd.cancelled) return 'bg-red-50 border-red-200 opacity-60'
  if (cd.booked) return 'bg-komm-50 border-komm-200'
  return 'bg-white border-slate-200'
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

async function bookDate(cd: CourseDate) {
  const dogId = dogIdForBooking(cd)
  if (!dogId) {
    toast.add({ title: 'Bitte zuerst einen Hund auswählen.', color: 'red' })
    return
  }
  try {
    await api.post(`/api/customer/calendar/course-dates/${cd.id}/book`, { dogId })
    toast.add({ title: 'Termin gebucht', color: 'green' })
    delete dogIdByCourseDate[cd.id]
    await loadCalendar()
  } catch (cause) {
    toast.add({ title: extractApiErrorMessage(cause, 'Die Buchung konnte nicht gespeichert werden.', { preferFieldSummary: false }), color: 'red' })
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
