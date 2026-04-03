import { computed, onBeforeUnmount, onMounted, ref, watchEffect, type ComputedRef, type Ref } from 'vue'
import type { CalendarTimelineDay } from '~/composables/useCalendarTimeline'
import type { CourseDate } from '~/types'

export interface UseCalendarViewResult {
  viewMode: Ref<'day' | 'week'>
  currentDay: Ref<string>
  currentMonday: Ref<string>
  weekStart: ComputedRef<string>
  weekEnd: ComputedRef<string>
  visibleDays: ComputedRef<CalendarTimelineDay[]>
  prev: () => void
  next: () => void
  goToday: () => void
}

export function useCalendarView(courseDates: Ref<CourseDate[]>): UseCalendarViewResult {
  const {
    addDaysToIso,
    dayNameShort,
    formatDateShort,
    getIsoDayOfWeek,
    getWeekMonday,
    todayIso,
  } = useHelpers()

  const isMobile = ref(false)
  const viewMode = ref<'day' | 'week'>('week')
  const currentDay = ref(todayIso())
  const currentMonday = ref(getWeekMonday(currentDay.value))

  function updateViewport(): void {
    isMobile.value = window.innerWidth < 1024
  }

  function syncMondayFromDay(): void {
    const newMonday = getWeekMonday(currentDay.value)
    if (newMonday !== currentMonday.value) {
      currentMonday.value = newMonday
    }
  }

  watchEffect(() => {
    viewMode.value = isMobile.value ? 'day' : 'week'
  })

  const weekStart = computed(() => currentMonday.value)
  const weekEnd = computed(() => addDaysToIso(currentMonday.value, 6))

  const weekDays = computed<CalendarTimelineDay[]>(() => {
    const days: CalendarTimelineDay[] = []
    const today = todayIso()

    for (let index = 0; index < 7; index += 1) {
      const date = addDaysToIso(currentMonday.value, index)
      days.push({
        date,
        label: dayNameShort(index + 1),
        dateShort: formatDateShort(date),
        isToday: date === today,
        courseDates: courseDates.value
          .filter(courseDate => courseDate.date === date)
          .sort((left, right) => left.startTime.localeCompare(right.startTime)),
      })
    }

    return days
  })

  const singleDay = computed<CalendarTimelineDay>(() => {
    const today = todayIso()
    const dayOfWeek = getIsoDayOfWeek(currentDay.value)

    return {
      date: currentDay.value,
      label: dayNameShort(dayOfWeek),
      dateShort: formatDateShort(currentDay.value),
      isToday: currentDay.value === today,
      courseDates: courseDates.value
        .filter(courseDate => courseDate.date === currentDay.value)
        .sort((left, right) => left.startTime.localeCompare(right.startTime)),
    }
  })

  const visibleDays = computed(() => (viewMode.value === 'week' ? weekDays.value : [singleDay.value]))

  function prev(): void {
    if (viewMode.value === 'week') {
      currentMonday.value = addDaysToIso(currentMonday.value, -7)
      return
    }

    currentDay.value = addDaysToIso(currentDay.value, -1)
    syncMondayFromDay()
  }

  function next(): void {
    if (viewMode.value === 'week') {
      currentMonday.value = addDaysToIso(currentMonday.value, 7)
      return
    }

    currentDay.value = addDaysToIso(currentDay.value, 1)
    syncMondayFromDay()
  }

  function goToday(): void {
    currentDay.value = todayIso()
    currentMonday.value = getWeekMonday(currentDay.value)
  }

  onMounted(() => {
    updateViewport()
    window.addEventListener('resize', updateViewport)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('resize', updateViewport)
  })

  return {
    viewMode,
    currentDay,
    currentMonday,
    weekStart,
    weekEnd,
    visibleDays,
    prev,
    next,
    goToday,
  }
}
