<template>
  <div class="flex h-full min-w-0 flex-col gap-1">
    <div class="flex items-start justify-between gap-2">
      <p class="min-w-0 font-semibold leading-4" :class="courseDate.cancelled ? 'text-red-600 line-through' : 'text-slate-700'">
        <span
          class="block truncate"
          :class="condensed ? 'text-[11px]' : 'text-[13px] sm:text-sm'"
          :title="formatCourseTitleWithLevel(courseDate.courseType?.name, courseDate.level)"
        >
          {{ formatCourseTitleWithLevel(courseDate.courseType?.name, courseDate.level) }}
        </span>
      </p>
      <UBadge v-if="courseDate.cancelled" color="red" variant="soft" size="xs">Abgesagt</UBadge>
    </div>

    <template v-if="condensed">
      <div class="space-y-0.5 text-[10px] leading-4 text-slate-500">
        <p>{{ courseDate.startTime }} – {{ courseDate.endTime }}</p>
        <p class="break-words">{{ courseDate.trainer?.fullName || 'Kein Trainer' }}</p>
      </div>
    </template>
    <template v-else>
      <p class="text-[11px] font-medium text-slate-500">{{ courseDate.startTime }} – {{ courseDate.endTime }}</p>
      <p class="text-[11px] leading-4 text-slate-500">
        Trainer: {{ courseDate.trainer?.fullName || 'Nicht zugewiesen' }}
      </p>
    </template>

    <div class="mt-auto flex items-center gap-1" :class="condensed ? 'text-[10px] leading-4' : 'text-[11px]'">
      <UTooltip
        v-if="!condensed && courseDate.bookingCount"
        :text="courseDate.bookings?.map(booking => `${booking.dogName} (${booking.customerName})`).join(', ') || ''"
      >
        <div class="flex items-center gap-1 text-slate-500">
          <UIcon name="i-heroicons-user-group" class="text-slate-400" :class="condensed ? 'h-2.5 w-2.5' : 'h-3 w-3'" />
          <span>{{ courseDate.bookingCount }}</span>
        </div>
      </UTooltip>
      <div v-else class="flex items-center gap-1 text-slate-500">
        <UIcon name="i-heroicons-user-group" class="text-slate-400" :class="condensed ? 'h-2.5 w-2.5' : 'h-3 w-3'" />
        <span>{{ courseDate.bookingCount }}</span>
      </div>

      <div class="ml-auto flex items-center gap-1" :class="courseDate.subscriberCount ? 'text-komm-600' : 'text-slate-400'">
        <UIcon
          name="i-heroicons-heart"
          :class="[condensed ? 'h-2.5 w-2.5' : 'h-3 w-3', courseDate.subscriberCount ? 'text-komm-500' : 'text-slate-300']"
        />
        <span class="font-medium">{{ courseDate.subscriberCount ?? 0 }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CourseDate } from '~/types'

defineProps<{
  courseDate: CourseDate
  condensed: boolean
}>()

const { formatCourseTitleWithLevel } = useHelpers()
</script>
