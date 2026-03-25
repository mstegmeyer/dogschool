<template>
  <div>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Kalender</h1>
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

    <div :class="viewMode === 'week' ? 'grid grid-cols-7 gap-3' : ''">
      <div v-for="day in visibleDays" :key="day.date" :class="viewMode === 'week' ? 'min-h-[200px]' : ''">
        <div class="text-center mb-2">
          <p class="text-xs font-semibold text-slate-500 uppercase">{{ day.label }}</p>
          <p class="text-sm font-medium" :class="day.isToday ? 'text-green-600' : 'text-slate-700'">
            {{ day.dateShort }}
          </p>
        </div>
        <div class="space-y-2">
          <div
            v-for="cd in day.courseDates"
            :key="cd.id"
            class="rounded-lg border p-2 text-xs cursor-pointer transition-colors"
            :class="cd.cancelled
              ? 'bg-red-50 border-red-200 opacity-60'
              : 'bg-white border-slate-200 hover:border-green-300'"
            @click="openDetail(cd)"
          >
            <p class="font-semibold truncate" :class="cd.cancelled ? 'text-red-600 line-through' : 'text-slate-700'">
              {{ cd.courseType?.name || 'Kurs' }}
            </p>
            <p class="text-slate-400 mt-0.5">{{ cd.startTime }} – {{ cd.endTime }}</p>
            <div class="flex items-center gap-1 mt-1">
              <UTooltip
                v-if="cd.bookingCount"
                :text="cd.bookings?.map(b => `${b.dogName} (${b.customerName})`).join(', ') || ''"
              >
                <div class="flex items-center gap-1">
                  <UIcon name="i-heroicons-user-group" class="w-3 h-3 text-slate-400" />
                  <span class="text-slate-500">{{ cd.bookingCount }}</span>
                </div>
              </UTooltip>
              <template v-else>
                <UIcon name="i-heroicons-user-group" class="w-3 h-3 text-slate-400" />
                <span class="text-slate-500">0</span>
              </template>
              <div v-if="cd.subscriberCount" class="flex items-center gap-0.5 ml-auto">
                <UIcon name="i-heroicons-heart" class="w-3 h-3 text-green-500" />
                <span class="text-green-600 font-medium">{{ cd.subscriberCount }}</span>
              </div>
              <UBadge v-if="cd.cancelled" color="red" variant="soft" size="xs" class="ml-auto">Abgesagt</UBadge>
            </div>
          </div>
          <div v-if="day.courseDates.length === 0" class="text-center py-6">
            <p class="text-xs text-slate-300">Keine Termine</p>
          </div>
        </div>
      </div>
    </div>

    <UModal v-model="showDetail">
      <UCard v-if="selectedDate">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">{{ selectedDate.courseType?.name }}</h3>
            <UBadge v-if="selectedDate.cancelled" color="red" variant="soft">Abgesagt</UBadge>
          </div>
        </template>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between">
            <span class="text-slate-500">Datum</span>
            <span class="font-medium">{{ formatDate(selectedDate.date) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Uhrzeit</span>
            <span class="font-medium">{{ selectedDate.startTime }} – {{ selectedDate.endTime }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Buchungen</span>
            <span class="font-medium">{{ selectedDate.bookingCount }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Abonnenten</span>
            <span class="font-medium">{{ selectedDate.subscriberCount ?? 0 }}</span>
          </div>

          <div v-if="selectedDate.subscribers?.length" class="border-t border-slate-100 pt-3">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Kurs-Abonnenten</p>
            <ul class="divide-y divide-slate-100 rounded-md border border-slate-100 max-h-32 overflow-y-auto">
              <li
                v-for="sub in selectedDate.subscribers"
                :key="sub.id"
                class="px-3 py-2 text-sm font-medium text-slate-800"
              >
                {{ sub.name }}
              </li>
            </ul>
          </div>

          <div class="border-t border-slate-100 pt-3">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Angemeldete Hunde</p>
            <div v-if="!selectedDate.bookings?.length" class="text-sm text-slate-400 py-2">
              Noch keine Buchungen für diesen Termin.
            </div>
            <ul v-else class="divide-y divide-slate-100 rounded-md border border-slate-100 max-h-48 overflow-y-auto">
              <li
                v-for="b in selectedDate.bookings"
                :key="b.id"
                class="px-3 py-2 flex flex-col gap-0.5"
              >
                <span class="font-medium text-slate-800">{{ b.dogName || 'Hund' }}</span>
                <span class="text-xs text-slate-500">Halter: {{ b.customerName || '–' }}</span>
              </li>
            </ul>
          </div>
        </div>

        <div v-if="!selectedDate.cancelled" class="border-t border-slate-100 pt-4 mt-4 space-y-3">
          <div class="flex items-center gap-3">
            <UToggle v-model="cancelNotify" />
            <span class="text-sm text-slate-600">Mitteilung an Kursteilnehmer senden</span>
          </div>
          <template v-if="cancelNotify">
            <UFormGroup label="Titel">
              <UInput v-model="cancelNotifyTitle" placeholder="Betreff" />
            </UFormGroup>
            <UFormGroup label="Nachricht">
              <UTextarea v-model="cancelNotifyMessage" placeholder="Grund für die Absage…" :rows="3" />
            </UFormGroup>
          </template>
        </div>

        <template #footer>
          <div class="flex gap-2 justify-end">
            <UButton
              v-if="!selectedDate.cancelled"
              color="red"
              variant="soft"
              label="Absagen"
              :loading="cancelling"
              @click="cancelDate(selectedDate)"
            />
            <UButton
              v-else
              color="green"
              variant="soft"
              label="Reaktivieren"
              @click="uncancelDate(selectedDate)"
            />
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, CourseDate } from '~/types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const toast = useToast()
const { formatDate, dayNameShort, getWeekMonday } = useHelpers()

const isMobile = ref(false)
onMounted(() => {
  isMobile.value = window.innerWidth < 1024
})
const viewMode = ref<'day' | 'week'>('week')
watchEffect(() => { viewMode.value = isMobile.value ? 'day' : 'week' })

const currentMonday = ref(getWeekMonday())
const currentDay = ref(new Date().toISOString().split('T')[0])
const courseDates = ref<CourseDate[]>([])
const showDetail = ref(false)
const selectedDate = ref<CourseDate | null>(null)
const cancelling = ref(false)
const cancelNotify = ref(false)
const cancelNotifyTitle = ref('')
const cancelNotifyMessage = ref('')

const weekStart = computed(() => currentMonday.value)
const weekEnd = computed(() => {
  const d = new Date(currentMonday.value)
  d.setDate(d.getDate() + 6)
  return d.toISOString().split('T')[0]
})

const weekDays = computed(() => {
  const days = []
  const today = new Date().toISOString().split('T')[0]
  for (let i = 0; i < 7; i++) {
    const d = new Date(currentMonday.value)
    d.setDate(d.getDate() + i)
    const dateStr = d.toISOString().split('T')[0]
    days.push({
      date: dateStr,
      label: dayNameShort(i + 1),
      dateShort: d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' }),
      isToday: dateStr === today,
      courseDates: courseDates.value
        .filter(cd => cd.date === dateStr)
        .sort((a, b) => a.startTime.localeCompare(b.startTime)),
    })
  }
  return days
})

const singleDay = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  const d = new Date(currentDay.value)
  const dow = d.getDay() === 0 ? 7 : d.getDay()
  return {
    date: currentDay.value,
    label: dayNameShort(dow),
    dateShort: d.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' }),
    isToday: currentDay.value === today,
    courseDates: courseDates.value
      .filter(cd => cd.date === currentDay.value)
      .sort((a, b) => a.startTime.localeCompare(b.startTime)),
  }
})

const visibleDays = computed(() => viewMode.value === 'week' ? weekDays.value : [singleDay.value])

function prev() {
  if (viewMode.value === 'week') {
    const d = new Date(currentMonday.value)
    d.setDate(d.getDate() - 7)
    currentMonday.value = d.toISOString().split('T')[0]
  } else {
    const d = new Date(currentDay.value)
    d.setDate(d.getDate() - 1)
    currentDay.value = d.toISOString().split('T')[0]
    syncMondayFromDay()
  }
}

function next() {
  if (viewMode.value === 'week') {
    const d = new Date(currentMonday.value)
    d.setDate(d.getDate() + 7)
    currentMonday.value = d.toISOString().split('T')[0]
  } else {
    const d = new Date(currentDay.value)
    d.setDate(d.getDate() + 1)
    currentDay.value = d.toISOString().split('T')[0]
    syncMondayFromDay()
  }
}

function goToday() {
  currentMonday.value = getWeekMonday()
  currentDay.value = new Date().toISOString().split('T')[0]
}

function syncMondayFromDay() {
  const d = new Date(currentDay.value)
  const dow = d.getDay() === 0 ? 7 : d.getDay()
  d.setDate(d.getDate() - (dow - 1))
  const newMonday = d.toISOString().split('T')[0]
  if (newMonday !== currentMonday.value) {
    currentMonday.value = newMonday
  }
}

function openDetail(cd: CourseDate) {
  selectedDate.value = cd
  cancelNotify.value = false
  cancelNotifyTitle.value = `Kursausfall: ${cd.courseType?.name || 'Kurs'} am ${formatDate(cd.date)}`
  cancelNotifyMessage.value = ''
  showDetail.value = true
}

async function cancelDate(cd: CourseDate) {
  cancelling.value = true
  try {
    const body: Record<string, string> = {}
    if (cancelNotify.value && cancelNotifyTitle.value && cancelNotifyMessage.value) {
      body.notificationTitle = cancelNotifyTitle.value
      body.notificationMessage = cancelNotifyMessage.value
    }
    await api.post(`/api/admin/calendar/course-dates/${cd.id}/cancel`, body)
    const msg = cancelNotify.value && body.notificationTitle
      ? 'Termin abgesagt & Mitteilung erstellt'
      : 'Termin abgesagt'
    toast.add({ title: msg, color: 'green' })
    showDetail.value = false
    await loadCalendar()
  } finally {
    cancelling.value = false
  }
}

async function uncancelDate(cd: CourseDate) {
  await api.post(`/api/admin/calendar/course-dates/${cd.id}/uncancel`)
  toast.add({ title: 'Termin reaktiviert', color: 'green' })
  showDetail.value = false
  await loadCalendar()
}

async function loadCalendar(): Promise<void> {
  const res = await api.get<ApiListResponse<CourseDate>>(`/api/admin/calendar?week=${currentMonday.value}`)
  courseDates.value = res.items
}

watch(currentMonday, () => loadCalendar())
onMounted(() => loadCalendar())
</script>
