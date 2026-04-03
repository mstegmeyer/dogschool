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

    <AppSkeletonCalendar
      v-if="loading"
      :days="viewMode === 'week' ? 7 : 1"
      :cards-per-day="viewMode === 'week' ? 2 : 4"
      :columns-class="viewMode === 'week' ? 'grid grid-cols-1 gap-3 lg:grid-cols-7' : 'grid grid-cols-1 gap-3'"
      :day-class="viewMode === 'week' ? 'min-h-[200px]' : ''"
    />
    <AppCalendarTimeline
      v-else
      :days="visibleDays"
      :view-mode="viewMode"
      empty-label="Keine Termine"
      selectable
      :event-class="calendarCardClass"
      @select="openDetail"
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
          </div>

          <template v-if="condensed">
            <div class="space-y-0.5 text-[10px] leading-4 text-slate-500">
              <p>{{ cd.startTime }} – {{ cd.endTime }}</p>
              <p class="break-words">{{ cd.trainer?.fullName || 'Kein Trainer' }}</p>
            </div>
          </template>
          <template v-else>
            <p class="text-[11px] font-medium text-slate-500">{{ cd.startTime }} – {{ cd.endTime }}</p>
            <p class="text-[11px] leading-4 text-slate-500">
              Trainer: {{ cd.trainer?.fullName || 'Nicht zugewiesen' }}
            </p>
          </template>

          <div class="mt-auto flex items-center gap-1" :class="condensed ? 'text-[10px] leading-4' : 'text-[11px]'">
            <UTooltip
              v-if="!condensed && cd.bookingCount"
              :text="cd.bookings?.map(b => `${b.dogName} (${b.customerName})`).join(', ') || ''"
            >
              <div class="flex items-center gap-1 text-slate-500">
                <UIcon name="i-heroicons-user-group" class="text-slate-400" :class="condensed ? 'h-2.5 w-2.5' : 'h-3 w-3'" />
                <span>{{ cd.bookingCount }}</span>
              </div>
            </UTooltip>
            <div v-else class="flex items-center gap-1 text-slate-500">
              <UIcon name="i-heroicons-user-group" class="text-slate-400" :class="condensed ? 'h-2.5 w-2.5' : 'h-3 w-3'" />
              <span>{{ cd.bookingCount }}</span>
            </div>

            <div class="ml-auto flex items-center gap-1" :class="cd.subscriberCount ? 'text-komm-600' : 'text-slate-400'">
              <UIcon
                name="i-heroicons-heart"
                :class="[condensed ? 'h-2.5 w-2.5' : 'h-3 w-3', cd.subscriberCount ? 'text-komm-500' : 'text-slate-300']"
              />
              <span class="font-medium">{{ cd.subscriberCount ?? 0 }}</span>
            </div>
          </div>
        </div>
      </template>
    </AppCalendarTimeline>

    <UModal v-model="showDetail">
      <UCard v-if="selectedDate">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">
              {{ formatCourseTitleWithLevel(selectedDate.courseType?.name, selectedDate.level) }}
            </h3>
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
          <div class="flex justify-between gap-4">
            <span class="text-slate-500">Trainer</span>
            <span class="text-right font-medium">
              {{ selectedDate.trainer?.fullName || 'Nicht zugewiesen' }}
            </span>
          </div>
          <div
            v-if="selectedDate.trainerOverridden && selectedDate.courseTrainer"
            class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900"
          >
            Standard für den Kurs: {{ selectedDate.courseTrainer.fullName }}
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
            <UFormGroup label="Trainer für diesen Termin">
              <div class="flex flex-col gap-2 sm:flex-row">
                <USelectMenu
                  v-model="selectedTrainerId"
                  :options="trainerOptions"
                  value-attribute="value"
                  class="flex-1"
                />
                <UButton
                  label="Trainer speichern"
                  :loading="savingTrainer"
                  :disabled="selectedTrainerId === (selectedDate.trainer?.id || '')"
                  @click="saveTrainerOverride"
                />
              </div>
            </UFormGroup>
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
              color="primary"
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
import type { ApiListResponse, CourseDate, TrainerInfo } from '~/types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const toast = useToast()
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
const trainers = ref<TrainerInfo[]>([])
const showDetail = ref(false)
const selectedDate = ref<CourseDate | null>(null)
const selectedTrainerId = ref('')
const cancelling = ref(false)
const savingTrainer = ref(false)
const cancelNotify = ref(false)
const cancelNotifyTitle = ref('')
const cancelNotifyMessage = ref('')
const loading = ref(true)

const weekStart = computed(() => currentMonday.value)
const weekEnd = computed(() => addDaysToIso(currentMonday.value, 6))
const trainerOptions = computed(() => [
  { label: 'Standard vom Kurs verwenden', value: '' },
  ...trainers.value.map(trainer => ({
    label: trainer.fullName,
    value: trainer.id,
  })),
])

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
  return cd.cancelled
    ? 'border-red-200 bg-red-50/90 opacity-70'
    : 'border-slate-200 bg-white/95 hover:border-komm-300 hover:bg-komm-50/30'
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

function openDetail(cd: CourseDate) {
  selectedDate.value = cd
  selectedTrainerId.value = cd.trainer?.id || ''
  cancelNotify.value = false
  cancelNotifyTitle.value = `Kursausfall: ${formatCourseTitleWithLevel(cd.courseType?.name, cd.level)} am ${formatDate(cd.date)}`
  cancelNotifyMessage.value = ''
  showDetail.value = true
}

async function saveTrainerOverride() {
  if (!selectedDate.value) return

  savingTrainer.value = true
  try {
    await api.put(`/api/admin/calendar/course-dates/${selectedDate.value.id}/trainer`, {
      trainerId: selectedTrainerId.value || null,
    })
    toast.add({ title: 'Trainer aktualisiert', color: 'green' })
    await loadCalendar()
    const refreshed = courseDates.value.find(courseDate => courseDate.id === selectedDate.value?.id) || null
    selectedDate.value = refreshed
    selectedTrainerId.value = refreshed?.trainer?.id || ''
  } catch (cause) {
    toast.add({ title: extractApiErrorMessage(cause, 'Der Trainer konnte nicht gespeichert werden.', { preferFieldSummary: false }), color: 'red' })
  } finally {
    savingTrainer.value = false
  }
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
  loading.value = true
  try {
    const res = await api.get<ApiListResponse<CourseDate>>(`/api/admin/calendar?week=${currentMonday.value}`)
    courseDates.value = res.items
  } finally {
    loading.value = false
  }
}

async function loadTrainers(): Promise<void> {
  const res = await api.get<ApiListResponse<TrainerInfo>>('/api/admin/trainers')
  trainers.value = res.items
}

watch(currentMonday, () => loadCalendar())
onMounted(() => {
  void loadCalendar()
  void loadTrainers()
})
</script>
