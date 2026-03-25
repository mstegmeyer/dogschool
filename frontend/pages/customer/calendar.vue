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
import type { ApiListResponse, CourseDate, Dog } from '~/types'

definePageMeta({ layout: 'customer' })

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

function cardClass(cd: CourseDate) {
  if (cd.cancelled) return 'bg-red-50 border-red-200 opacity-60'
  if (cd.booked) return 'bg-komm-50 border-komm-200'
  return 'bg-white border-slate-200'
}

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
