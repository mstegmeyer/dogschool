<template>
  <UModal
    :model-value="modelValue"
    :ui="{ width: 'w-full sm:max-w-6xl' }"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <UCard class="w-full">
      <template #header>
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <p class="text-lg font-semibold text-slate-800">
              {{ course?.type?.name || 'Kursdetails' }}
            </p>
            <div v-if="course" class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
              <span>{{ dayName(course.dayOfWeek) }}</span>
              <span>{{ course.startTime }}–{{ course.endTime }}</span>
              <span>{{ levelLabel(course.level) }}</span>
              <UBadge v-if="course.type?.code" variant="soft" color="gray" size="xs">
                {{ course.type.code }}
              </UBadge>
            </div>
            <p v-if="course?.comment" class="mt-2 text-sm text-slate-500">
              {{ course.comment }}
            </p>
          </div>
          <UButton
            icon="i-heroicons-x-mark"
            color="gray"
            variant="ghost"
            size="sm"
            aria-label="Schließen"
            @click="emit('update:modelValue', false)"
          />
        </div>
      </template>

      <div v-if="loading" class="space-y-6">
        <div>
          <USkeleton class="h-4 w-40 rounded-md" />
          <div class="mt-3 space-y-2">
            <USkeleton class="h-14 w-full rounded-lg" />
            <USkeleton class="h-14 w-full rounded-lg" />
            <USkeleton class="h-14 w-full rounded-lg" />
          </div>
        </div>
        <div>
          <USkeleton class="h-4 w-44 rounded-md" />
          <div class="mt-3 space-y-3">
            <USkeleton class="h-24 w-full rounded-lg" />
            <USkeleton class="h-24 w-full rounded-lg" />
          </div>
        </div>
      </div>

      <div v-else-if="courseDetail" class="space-y-6">
        <section>
          <div class="flex items-center gap-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Nächste Termine
            </h2>
          </div>
          <div v-if="courseDetail.upcomingDates.length === 0" class="mt-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
            Für diesen Kurs sind aktuell keine Termine im nächsten Monat geplant.
          </div>
          <div v-else class="mt-3 space-y-2">
            <div
              v-for="courseDate in courseDetail.upcomingDates"
              :key="courseDate.id"
              class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3"
            >
              <div class="min-w-0">
                <p class="font-medium text-slate-800">
                  {{ dayName(courseDate.dayOfWeek) }}, {{ formatDate(courseDate.date) }}
                </p>
                <p class="mt-1 text-sm text-slate-500">
                  {{ courseDate.startTime }}–{{ courseDate.endTime }}
                  <span v-if="courseDate.trainer?.fullName">· {{ courseDate.trainer.fullName }}</span>
                </p>
              </div>
              <UBadge v-if="courseDate.cancelled" color="red" variant="soft" size="sm">
                Abgesagt
              </UBadge>
            </div>
          </div>
        </section>

        <section>
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Mitteilungsverlauf
            </h2>
          </div>
          <div v-if="courseDetail.notifications.length === 0" class="mt-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
            Für diesen Kurs gibt es in den letzten sechs Monaten keine Mitteilungen.
          </div>
          <div v-else class="mt-3 space-y-3">
            <div
              v-for="notification in courseDetail.notifications"
              :key="notification.id"
              class="rounded-lg border border-slate-200 bg-white p-4"
            >
              <AppNotificationDetail :notification="notification" :max-visible-courses="1" />
            </div>
          </div>
        </section>
      </div>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
import type { Course, CustomerCourseDetailResponse } from '~/types'

defineProps<{
  modelValue: boolean
  course: Course | null
  courseDetail: CustomerCourseDetailResponse | null
  loading: boolean
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
}>()

const { dayName, formatDate, levelLabel } = useHelpers()
</script>
