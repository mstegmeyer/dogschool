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
      <div v-for="day in weekDays" :key="day.date" class="min-h-[180px]">
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
            class="rounded-lg border p-2 text-xs transition-colors"
            :class="cardClass(cd)"
          >
            <p class="font-semibold truncate" :class="cd.cancelled ? 'text-red-600 line-through' : 'text-slate-700'">
              {{ cd.courseType?.name || 'Kurs' }}
            </p>
            <p class="text-slate-400 mt-0.5">{{ cd.startTime }} – {{ cd.endTime }}</p>

            <template v-if="cd.cancelled">
              <UBadge color="red" variant="soft" size="xs" class="mt-1">Abgesagt</UBadge>
            </template>
            <template v-else-if="cd.booked">
              <div class="mt-2 space-y-1">
                <UBadge color="green" variant="soft" size="xs">Gebucht</UBadge>
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
import type { ApiListResponse, CourseDate, Dog } from '~/types'

definePageMeta({ layout: 'customer' })

const api = useApi()
const toast = useToast()
const { formatDate, dayNameShort, getWeekMonday } = useHelpers()

const currentMonday = ref(getWeekMonday())
const courseDates = ref<CourseDate[]>([])
const dogs = ref<Dog[]>([])

/** Pro Kurstermin gewählter Hund (nur relevant bei mehreren Hunden). */
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

function cardClass(cd: CourseDate) {
  if (cd.cancelled) return 'bg-red-50 border-red-200 opacity-60'
  if (cd.booked) return 'bg-green-50 border-green-200'
  return 'bg-white border-slate-200'
}

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

async function bookDate(cd: CourseDate) {
  const dogId = dogIdForBooking(cd)
  if (!dogId) return
  try {
    await api.post(`/api/customer/calendar/course-dates/${cd.id}/book`, { dogId })
    toast.add({ title: 'Termin gebucht', color: 'green' })
    delete dogIdByCourseDate[cd.id]
    await loadCalendar()
  } catch {
    toast.add({ title: 'Buchung fehlgeschlagen – eventuell nicht genug Credits', color: 'red' })
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
  } catch {
    toast.add({ title: 'Stornierung fehlgeschlagen', color: 'red' })
  }
}

async function loadCalendar(): Promise<void> {
  const res = await api.get<ApiListResponse<CourseDate>>(`/api/customer/calendar?week=${currentMonday.value}`)
  courseDates.value = res.items
}

watch(currentMonday, () => loadCalendar())

onMounted(async () => {
  const [, dogRes] = await Promise.all([
    loadCalendar(),
    api.get<ApiListResponse<Dog>>('/api/customer/dogs'),
  ])
  dogs.value = dogRes.items
})
</script>
