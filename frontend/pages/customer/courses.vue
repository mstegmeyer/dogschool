<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-4">Kurse</h1>

    <UTabs :items="tabs">
      <template #item="{ item }">
        <div class="pt-3">
          <!-- Available: grouped by weekday, compact tables -->
          <div v-if="item.key === 'available'" class="space-y-4">
            <div
              v-for="group in groupedAvailable"
              :key="group.dayOfWeek"
              class="border border-slate-200 rounded-lg overflow-hidden bg-white"
            >
              <div class="px-3 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-700">{{ dayName(group.dayOfWeek) }}</span>
                <span class="text-xs text-slate-400">{{ group.courses.length }} Kurse</span>
              </div>
              <div class="divide-y divide-slate-100 md:hidden">
                <div
                  v-for="course in group.courses"
                  :key="course.id"
                  class="space-y-3 p-3"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <p class="font-medium text-slate-800 leading-tight">
                        {{ course.type?.name || 'Kurs' }}
                      </p>
                      <p v-if="course.comment" class="mt-1 text-xs text-slate-500">
                        {{ course.comment }}
                      </p>
                    </div>
                    <UBadge v-if="course.type?.code" variant="soft" color="gray" size="xs">
                      {{ course.type.code }}
                    </UBadge>
                  </div>
                  <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                      <p class="text-slate-400">Zeit</p>
                      <p class="font-medium text-slate-700">{{ course.startTime }}–{{ course.endTime }}</p>
                    </div>
                    <div>
                      <p class="text-slate-400">Stufe</p>
                      <p class="font-medium text-slate-700">{{ levelLabel(course.level) }}</p>
                    </div>
                  </div>
                  <UButton
                    v-if="isSubscribed(course.id)"
                    color="red"
                    variant="soft"
                    block
                    label="Abbestellen"
                    @click="unsubscribe(course)"
                  />
                  <UButton
                    v-else
                    block
                    label="Abonnieren"
                    @click="subscribe(course)"
                  />
                </div>
              </div>
              <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-xs text-slate-500 border-b border-slate-100">
                      <th class="px-3 py-1.5 font-medium">Kurs</th>
                      <th class="px-2 py-1.5 font-medium w-[88px]">Zeit</th>
                      <th class="px-2 py-1.5 font-medium w-[72px]">Stufe</th>
                      <th class="px-2 py-1.5 font-medium w-[100px] text-right">Aktion</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="course in group.courses"
                      :key="course.id"
                      class="border-b border-slate-50 last:border-0 hover:bg-slate-50/80"
                    >
                      <td class="px-3 py-1.5">
                        <div class="font-medium text-slate-800 leading-tight">
                          {{ course.type?.name || 'Kurs' }}
                          <UBadge v-if="course.type?.code" variant="soft" color="gray" size="xs" class="ml-1 align-middle">
                            {{ course.type.code }}
                          </UBadge>
                        </div>
                        <p v-if="course.comment" class="text-xs text-slate-400 truncate max-w-[280px] mt-0.5" :title="course.comment">
                          {{ course.comment }}
                        </p>
                      </td>
                      <td class="px-2 py-1.5 text-slate-600 whitespace-nowrap tabular-nums">
                        {{ course.startTime }}–{{ course.endTime }}
                      </td>
                      <td class="px-2 py-1.5 text-slate-500 text-xs">
                        {{ levelLabel(course.level) }}
                      </td>
                      <td class="px-2 py-1 text-right whitespace-nowrap">
                        <UButton
                          v-if="isSubscribed(course.id)"
                          color="red"
                          variant="soft"
                          size="xs"
                          label="Abbestellen"
                          @click="unsubscribe(course)"
                        />
                        <UButton
                          v-else
                          size="xs"
                          label="Abonnieren"
                          @click="subscribe(course)"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <p v-if="availableCourses.length === 0" class="text-center text-slate-400 text-sm py-8">
              Keine Kurse verfügbar.
            </p>
          </div>

          <!-- Subscribed: same compact grouping -->
          <div v-if="item.key === 'subscribed'">
            <div v-if="subscribedCourses.length === 0" class="text-center py-8 text-slate-400 text-sm">
              Du hast noch keine Kurse abonniert.
            </div>
            <div v-else class="space-y-4">
              <div
                v-for="group in groupedSubscribed"
                :key="group.dayOfWeek"
                class="border border-slate-200 rounded-lg overflow-hidden bg-white"
              >
              <div class="px-3 py-2 bg-komm-50 border-b border-komm-100 flex items-center justify-between">
                <span class="text-sm font-semibold text-komm-800">{{ dayName(group.dayOfWeek) }}</span>
                <UBadge color="primary" variant="soft" size="xs">{{ group.courses.length }}</UBadge>
              </div>
              <div class="divide-y divide-slate-100 md:hidden">
                <div
                  v-for="course in group.courses"
                  :key="course.id"
                  class="space-y-3 p-3"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <p class="font-medium text-slate-800 leading-tight">
                        {{ course.type?.name || 'Kurs' }}
                      </p>
                      <p v-if="course.comment" class="mt-1 text-xs text-slate-500">
                        {{ course.comment }}
                      </p>
                    </div>
                    <UBadge v-if="course.type?.code" variant="soft" color="gray" size="xs">
                      {{ course.type.code }}
                    </UBadge>
                  </div>
                  <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                      <p class="text-slate-400">Zeit</p>
                      <p class="font-medium text-slate-700">{{ course.startTime }}–{{ course.endTime }}</p>
                    </div>
                    <div>
                      <p class="text-slate-400">Stufe</p>
                      <p class="font-medium text-slate-700">{{ levelLabel(course.level) }}</p>
                    </div>
                  </div>
                  <UButton
                    color="red"
                    variant="soft"
                    block
                    label="Abbestellen"
                    @click="unsubscribe(course)"
                  />
                </div>
              </div>
              <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="text-left text-xs text-slate-500 border-b border-slate-100">
                      <th class="px-3 py-1.5 font-medium">Kurs</th>
                        <th class="px-2 py-1.5 font-medium w-[88px]">Zeit</th>
                        <th class="px-2 py-1.5 font-medium w-[72px]">Stufe</th>
                        <th class="px-2 py-1.5 font-medium w-[100px] text-right">Aktion</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="course in group.courses"
                        :key="course.id"
                        class="border-b border-slate-50 last:border-0 hover:bg-slate-50/80"
                      >
                        <td class="px-3 py-1.5 font-medium text-slate-800">
                          {{ course.type?.name }}
                          <UBadge v-if="course.type?.code" variant="soft" color="gray" size="xs" class="ml-1">
                            {{ course.type.code }}
                          </UBadge>
                        </td>
                        <td class="px-2 py-1.5 text-slate-600 whitespace-nowrap tabular-nums">
                          {{ course.startTime }}–{{ course.endTime }}
                        </td>
                        <td class="px-2 py-1.5 text-slate-500 text-xs">
                          {{ levelLabel(course.level) }}
                        </td>
                        <td class="px-2 py-1 text-right">
                          <UButton
                            color="red"
                            variant="soft"
                            size="xs"
                            label="Abbestellen"
                            @click="unsubscribe(course)"
                          />
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </UTabs>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Course } from '~/types'

definePageMeta({ layout: 'customer' })

const api = useApi()
const toast = useToast()
const { dayName, levelLabel } = useHelpers()

const availableCourses = ref<Course[]>([])
const subscribedCourses = ref<Course[]>([])

const tabs = [
  { key: 'available', label: 'Verfügbare Kurse' },
  { key: 'subscribed', label: 'Meine Kurse' },
]

function sortByDayThenTime(courses: Course[]): Course[] {
  return [...courses].sort((a, b) => {
    if (a.dayOfWeek !== b.dayOfWeek) return a.dayOfWeek - b.dayOfWeek
    return a.startTime.localeCompare(b.startTime)
  })
}

function groupByWeekday(courses: Course[]): { dayOfWeek: number; courses: Course[] }[] {
  const sorted = sortByDayThenTime(courses)
  const map = new Map<number, Course[]>()
  for (const c of sorted) {
    const list = map.get(c.dayOfWeek) ?? []
    list.push(c)
    map.set(c.dayOfWeek, list)
  }
  return [...map.entries()]
    .sort((a, b) => a[0] - b[0])
    .map(([dayOfWeek, groupCourses]) => ({ dayOfWeek, courses: groupCourses }))
}

const groupedAvailable = computed(() => groupByWeekday(availableCourses.value))
const groupedSubscribed = computed(() => groupByWeekday(subscribedCourses.value))

const subscribedIds = computed(() => new Set(subscribedCourses.value.map(c => c.id)))
function isSubscribed(courseId: string) {
  return subscribedIds.value.has(courseId)
}

async function subscribe(course: Course) {
  await api.post(`/api/customer/courses/${course.id}/subscribe`)
  toast.add({ title: `${course.type?.name} abonniert`, color: 'green' })
  await loadCourses()
}

async function unsubscribe(course: Course) {
  await api.del(`/api/customer/courses/${course.id}/subscribe`)
  toast.add({ title: `${course.type?.name} abbestellt`, color: 'amber' })
  await loadCourses()
}

async function loadCourses(): Promise<void> {
  const [allRes, subRes] = await Promise.all([
    api.get<ApiListResponse<Course>>('/api/customer/courses'),
    api.get<ApiListResponse<Course>>('/api/customer/courses/subscribed'),
  ])
  availableCourses.value = allRes.items
  subscribedCourses.value = subRes.items
}

onMounted(() => loadCourses())
</script>
