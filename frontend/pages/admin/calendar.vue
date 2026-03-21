<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Kalender</h1>
      <div class="flex items-center gap-2">
        <UButton icon="i-heroicons-chevron-left" variant="ghost" size="sm" @click="prevWeek" />
        <span class="text-sm font-medium text-slate-600 min-w-[160px] text-center">
          {{ formatDate(weekStart) }} – {{ formatDate(weekEnd) }}
        </span>
        <UButton icon="i-heroicons-chevron-right" variant="ghost" size="sm" @click="nextWeek" />
        <UButton variant="outline" size="sm" label="Heute" class="ml-2" @click="goToday" />
      </div>
    </div>

    <div class="grid grid-cols-7 gap-3">
      <div v-for="day in weekDays" :key="day.date" class="min-h-[200px]">
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
              <UIcon name="i-heroicons-user-group" class="w-3 h-3 text-slate-400" />
              <span class="text-slate-500">{{ cd.bookingCount }}</span>
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
        <template #footer>
          <div class="flex gap-2 justify-end">
            <UButton
              v-if="!selectedDate.cancelled"
              color="red"
              variant="soft"
              label="Absagen"
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

const currentMonday = ref(getWeekMonday())
const courseDates = ref<CourseDate[]>([])
const showDetail = ref(false)
const selectedDate = ref<CourseDate | null>(null)

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

function prevWeek() {
  const d = new Date(currentMonday.value)
  d.setDate(d.getDate() - 7)
  currentMonday.value = d.toISOString().split('T')[0]
}

function nextWeek() {
  const d = new Date(currentMonday.value)
  d.setDate(d.getDate() + 7)
  currentMonday.value = d.toISOString().split('T')[0]
}

function goToday() {
  currentMonday.value = getWeekMonday()
}

function openDetail(cd: CourseDate) {
  selectedDate.value = cd
  showDetail.value = true
}

async function cancelDate(cd: CourseDate) {
  await api.post(`/api/admin/calendar/course-dates/${cd.id}/cancel`)
  toast.add({ title: 'Termin abgesagt', color: 'green' })
  showDetail.value = false
  await loadCalendar()
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
