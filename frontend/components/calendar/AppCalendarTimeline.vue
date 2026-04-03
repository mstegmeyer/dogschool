<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/80 shadow-sm">
    <div class="overflow-x-auto overflow-y-clip overscroll-x-contain [touch-action:pan-x_pan-y]">
      <div class="min-w-full" :style="{ minWidth: containerMinWidth }">
        <div class="grid border-b border-slate-200 bg-white/80" :style="{ gridTemplateColumns }">
          <div class="sticky left-0 z-20 border-r border-slate-200 bg-slate-50/95 px-3 py-4" />
          <div
            v-for="day in timeline.days"
            :key="`${day.date}-header`"
            class="min-w-0 border-r border-slate-200 px-2 py-3 text-center last:border-r-0 sm:px-3"
            :class="day.isToday ? 'bg-komm-50/70' : 'bg-white/80'"
          >
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
              {{ day.label }}
            </p>
            <p class="mt-1 text-sm font-semibold" :class="day.isToday ? 'text-komm-700' : 'text-slate-700'">
              {{ day.dateShort }}
            </p>
          </div>
        </div>

        <div class="grid" :style="{ gridTemplateColumns }">
          <div class="sticky left-0 z-10 border-r border-slate-200 bg-slate-50/95">
            <div class="relative" :style="{ height: `${timelineFrameHeight}px` }">
              <div
                v-for="mark in timeline.hourMarks"
                :key="`time-${mark.minute}`"
                class="absolute inset-x-0"
                :style="markStyle(mark.top)"
              >
                <div class="relative">
                  <span class="absolute left-0 top-0 -translate-y-1/2 px-2 text-[11px] font-semibold text-slate-400">
                    {{ mark.label }}
                  </span>
                  <div class="border-t border-slate-200/90" />
                </div>
              </div>
            </div>
          </div>

          <div
            v-for="day in timeline.days"
            :key="day.date"
            class="relative min-w-0 border-r border-slate-200 last:border-r-0"
            :class="day.isToday ? 'bg-komm-50/25' : 'bg-white/90'"
            :data-day-date="day.date"
          >
            <div class="relative" :style="{ height: `${timelineFrameHeight}px` }">
              <div
                v-for="mark in timeline.hourMarks"
                :key="`${day.date}-${mark.minute}`"
                class="pointer-events-none absolute inset-x-0 border-t border-slate-200/80"
                :style="markStyle(mark.top)"
              />

              <div
                v-if="day.items.length === 0"
                class="absolute inset-0 flex items-center justify-center p-3"
              >
                <span class="rounded-full border border-slate-200 bg-white/90 px-3 py-1 text-[11px] font-medium text-slate-400 shadow-sm">
                  {{ emptyLabel }}
                </span>
              </div>

              <component
                :is="selectable ? 'button' : 'div'"
                v-for="item in day.items"
                :key="item.courseDate.id"
                :type="selectable ? 'button' : undefined"
                class="absolute px-1 text-left"
                :class="selectable ? 'cursor-pointer focus:outline-none focus:ring-2 focus:ring-komm-300 focus:ring-offset-1 focus:ring-offset-white' : ''"
                :style="eventStyle(item)"
                data-testid="calendar-event"
                :data-course-date-id="item.courseDate.id"
                @click="handleSelect(item.courseDate)"
              >
                <div
                  class="h-full overflow-hidden rounded-xl border shadow-sm ring-1 ring-black/5 transition-colors"
                  :class="[eventCardClass(item), eventClass(item.courseDate, item)]"
                >
                  <slot
                    name="event"
                    :course-date="item.courseDate"
                    :layout="item"
                    :condensed="isCondensed(item)"
                    :view-mode="viewMode"
                  />
                </div>
              </component>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, type PropType } from 'vue'
import type { CourseDate } from '~/types'
import type { CalendarTimelineDay, CalendarTimelineItem } from '~/composables/useCalendarTimeline'
import { buildCalendarTimeline } from '~/composables/useCalendarTimeline'

const props = defineProps({
  days: {
    type: Array as PropType<CalendarTimelineDay[]>,
    required: true,
  },
  viewMode: {
    type: String as PropType<'day' | 'week'>,
    default: 'week',
  },
  emptyLabel: {
    type: String,
    default: 'Keine Termine',
  },
  selectable: {
    type: Boolean,
    default: false,
  },
  eventClass: {
    type: Function as PropType<(courseDate: CourseDate, item: CalendarTimelineItem) => string>,
    default: () => '',
  },
})

const emit = defineEmits<{
  (event: 'select', value: CourseDate): void
}>()

const timeline = computed(() => buildCalendarTimeline(props.days, { viewMode: props.viewMode }))

const gridTemplateColumns = computed(() => {
  const dayColumns = timeline.value.days.map((day) => {
    if (props.viewMode === 'day') return 'minmax(0, 1fr)'

    const minWidthRem = 12 + (Math.max(1, day.maxColumns) - 1) * 4
    return `minmax(${minWidthRem}rem, 1fr)`
  })

  return `4.5rem ${dayColumns.join(' ')}`
})

const containerMinWidth = computed(() => {
  if (props.viewMode === 'day') return '100%'

  const dayWidths = timeline.value.days.map(day => 12 + (Math.max(1, day.maxColumns) - 1) * 4)
  const totalWidthRem = 4.5 + dayWidths.reduce((sum, width) => sum + width, 0)
  return `${totalWidthRem}rem`
})

const timelineVerticalPadding = computed(() => props.viewMode === 'day' ? 14 : 12)
const timelineFrameHeight = computed(() => timeline.value.timelineHeight + (timelineVerticalPadding.value * 2))

function markStyle(top: number) {
  return {
    top: `${top + timelineVerticalPadding.value}px`,
  }
}

function eventCardClass(item: CalendarTimelineItem): string {
  return isCondensed(item) ? 'p-1' : 'p-2'
}

function eventStyle(item: CalendarTimelineItem) {
  const verticalInset = Math.min(props.viewMode === 'day' ? 3 : 2, Math.max(1, item.height / 6))

  return {
    top: `${timelineVerticalPadding.value + item.top + verticalInset}px`,
    height: `${Math.max(0, item.height - (verticalInset * 2))}px`,
    left: `${item.left}%`,
    width: `${item.width}%`,
  }
}

function isCondensed(item: CalendarTimelineItem): boolean {
  return props.viewMode === 'week' || item.columns > 1 || item.height < 96
}

function handleSelect(courseDate: CourseDate): void {
  if (!props.selectable) return
  emit('select', courseDate)
}
</script>
