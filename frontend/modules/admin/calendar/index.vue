<template>
  <div>
    <CalendarToolbar
      title="Kalender"
      :view-mode="viewMode"
      :range-label="calendarRangeLabel"
      @update:view-mode="viewMode = $event"
      @prev="prev"
      @next="next"
      @today="goToday"
    />

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
      <template #event="{ courseDate, condensed }">
        <AdminCalendarEventCard :course-date="courseDate" :condensed="condensed" />
      </template>
    </AppCalendarTimeline>

    <AdminCalendarDetailModal
      v-model="showDetail"
      :selected-date="selectedDate"
      :trainer-options="trainerOptions"
      :selected-trainer-id="selectedTrainerId"
      :saving-trainer="savingTrainer"
      :cancelling="cancelling"
      :cancel-notify="cancelNotify"
      :cancel-notify-title="cancelNotifyTitle"
      :cancel-notify-message="cancelNotifyMessage"
      @update:selected-trainer-id="selectedTrainerId = $event"
      @update:cancel-notify="cancelNotify = $event"
      @update:cancel-notify-title="cancelNotifyTitle = $event"
      @update:cancel-notify-message="cancelNotifyMessage = $event"
      @save-trainer="saveTrainerOverride"
      @cancel-date="selectedDate && cancelDate(selectedDate)"
      @uncancel-date="selectedDate && uncancelDate(selectedDate)"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { ApiListResponse, CourseDate, TrainerInfo } from '~/types'
import AdminCalendarDetailModal from './components/DetailModal.vue'
import AdminCalendarEventCard from './components/EventCard.vue'
import CalendarToolbar from '~/components/calendar/CalendarToolbar.vue'
import { useCalendarView } from '~/composables/useCalendarView'

const api = useApi()
const toast = useToast()
const { formatCourseTitleWithLevel, formatDate } = useHelpers()

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

const {
  viewMode,
  currentMonday,
  weekStart,
  weekEnd,
  visibleDays,
  prev,
  next,
  goToday,
} = useCalendarView(courseDates)

const calendarRangeLabel = computed(() => (
  viewMode.value === 'day'
    ? formatDate(visibleDays.value[0]?.date ?? weekStart.value)
    : `${formatDate(weekStart.value)} – ${formatDate(weekEnd.value)}`
))

const trainerOptions = computed(() => [
  { label: 'Standard vom Kurs verwenden', value: '' },
  ...trainers.value.map(trainer => ({
    label: trainer.fullName,
    value: trainer.id,
  })),
])

function calendarCardClass(courseDate: CourseDate): string {
  return courseDate.cancelled
    ? 'border-red-200 bg-red-50/90 opacity-70'
    : 'border-slate-200 bg-white/95 hover:border-komm-300 hover:bg-komm-50/30'
}

function openDetail(courseDate: CourseDate): void {
  selectedDate.value = courseDate
  selectedTrainerId.value = courseDate.trainer?.id || ''
  cancelNotify.value = false
  cancelNotifyTitle.value = `Kursausfall: ${formatCourseTitleWithLevel(courseDate.courseType?.name, courseDate.level)} am ${formatDate(courseDate.date)}`
  cancelNotifyMessage.value = ''
  showDetail.value = true
}

async function saveTrainerOverride(): Promise<void> {
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

async function cancelDate(courseDate: CourseDate): Promise<void> {
  cancelling.value = true
  try {
    const body: Record<string, string> = {}
    if (cancelNotify.value && cancelNotifyTitle.value && cancelNotifyMessage.value) {
      body.notificationTitle = cancelNotifyTitle.value
      body.notificationMessage = cancelNotifyMessage.value
    }

    await api.post(`/api/admin/calendar/course-dates/${courseDate.id}/cancel`, body)
    const title = cancelNotify.value && body.notificationTitle
      ? 'Termin abgesagt & Mitteilung erstellt'
      : 'Termin abgesagt'
    toast.add({ title, color: 'green' })
    showDetail.value = false
    await loadCalendar()
  } finally {
    cancelling.value = false
  }
}

async function uncancelDate(courseDate: CourseDate): Promise<void> {
  await api.post(`/api/admin/calendar/course-dates/${courseDate.id}/uncancel`)
  toast.add({ title: 'Termin reaktiviert', color: 'green' })
  showDetail.value = false
  await loadCalendar()
}

async function loadCalendar(): Promise<void> {
  loading.value = true
  try {
    const response = await api.get<ApiListResponse<CourseDate>>(`/api/admin/calendar?week=${currentMonday.value}`)
    courseDates.value = response.items
  } finally {
    loading.value = false
  }
}

async function loadTrainers(): Promise<void> {
  const response = await api.get<ApiListResponse<TrainerInfo>>('/api/admin/trainers')
  trainers.value = response.items
}

watch(currentMonday, () => {
  void loadCalendar()
})

onMounted(() => {
  void loadCalendar()
  void loadTrainers()
})
</script>
