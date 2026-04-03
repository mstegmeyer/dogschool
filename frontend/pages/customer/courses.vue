<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-4">Kurse</h1>

    <div v-if="loading" class="space-y-4">
      <div class="flex gap-2">
        <USkeleton class="h-9 w-36 rounded-md" />
        <USkeleton class="h-9 w-28 rounded-md" />
      </div>
      <div
        v-for="index in 3"
        :key="index"
        class="overflow-hidden rounded-lg border border-slate-200 bg-white"
      >
        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-3 py-2">
          <USkeleton class="h-4 w-24 rounded-md" />
          <USkeleton class="h-4 w-14 rounded-md" />
        </div>
        <div class="space-y-3 p-3">
          <div v-for="row in 3" :key="row" class="rounded-lg border border-slate-100 p-3">
            <USkeleton class="h-4 w-32 rounded-md" />
            <USkeleton class="mt-2 h-3 w-24 rounded-md" />
            <div class="mt-3 grid grid-cols-2 gap-3">
              <USkeleton class="h-3 w-full rounded-md" />
              <USkeleton class="h-3 w-full rounded-md" />
            </div>
            <USkeleton class="mt-4 h-9 w-full rounded-md" />
          </div>
        </div>
      </div>
    </div>
    <UTabs v-else :items="tabs">
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
                  class="space-y-3 p-3 cursor-pointer transition hover:bg-slate-50/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-komm-300"
                  role="button"
                  tabindex="0"
                  @click="openCourseDetail(course)"
                  @keydown.enter.prevent="openCourseDetail(course)"
                  @keydown.space.prevent="openCourseDetail(course)"
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
                    @click.stop="unsubscribe(course)"
                  />
                  <UButton
                    v-else
                    block
                    label="Abonnieren"
                    @click.stop="subscribe(course)"
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
                      class="border-b border-slate-50 last:border-0 cursor-pointer hover:bg-slate-50/80 focus-within:bg-slate-50/80"
                      tabindex="0"
                      @click="openCourseDetail(course)"
                      @keydown.enter.prevent="openCourseDetail(course)"
                      @keydown.space.prevent="openCourseDetail(course)"
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
                          @click.stop="unsubscribe(course)"
                        />
                        <UButton
                          v-else
                          size="xs"
                          label="Abonnieren"
                          @click.stop="subscribe(course)"
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
                  class="space-y-3 p-3 cursor-pointer transition hover:bg-slate-50/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-komm-300"
                  role="button"
                  tabindex="0"
                  @click="openCourseDetail(course)"
                  @keydown.enter.prevent="openCourseDetail(course)"
                  @keydown.space.prevent="openCourseDetail(course)"
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
                    @click.stop="unsubscribe(course)"
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
                        class="border-b border-slate-50 last:border-0 cursor-pointer hover:bg-slate-50/80 focus-within:bg-slate-50/80"
                        tabindex="0"
                        @click="openCourseDetail(course)"
                        @keydown.enter.prevent="openCourseDetail(course)"
                        @keydown.space.prevent="openCourseDetail(course)"
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
                            @click.stop="unsubscribe(course)"
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

    <UModal
      v-model="showCourseDetailModal"
      :ui="{ width: 'w-full sm:max-w-6xl' }"
    >
      <UCard class="w-full">
        <template #header>
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <p class="text-lg font-semibold text-slate-800">
                {{ selectedCourse?.type?.name || 'Kursdetails' }}
              </p>
              <div v-if="selectedCourse" class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <span>{{ dayName(selectedCourse.dayOfWeek) }}</span>
                <span>{{ selectedCourse.startTime }}–{{ selectedCourse.endTime }}</span>
                <span>{{ levelLabel(selectedCourse.level) }}</span>
                <UBadge v-if="selectedCourse.type?.code" variant="soft" color="gray" size="xs">
                  {{ selectedCourse.type.code }}
                </UBadge>
              </div>
              <p v-if="selectedCourse?.comment" class="mt-2 text-sm text-slate-500">
                {{ selectedCourse.comment }}
              </p>
            </div>
            <UButton
              icon="i-heroicons-x-mark"
              color="gray"
              variant="ghost"
              size="sm"
              aria-label="Schließen"
              @click="closeCourseDetail"
            />
          </div>
        </template>

        <div v-if="courseDetailLoading" class="space-y-6">
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

        <div v-else-if="selectedCourseDetail" class="space-y-6">
          <section>
            <div class="flex items-center gap-3">
              <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                Nächste Termine
              </h2>
            </div>
            <div v-if="selectedCourseDetail.upcomingDates.length === 0" class="mt-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
              Für diesen Kurs sind aktuell keine Termine im nächsten Monat geplant.
            </div>
            <div v-else class="mt-3 space-y-2">
              <div
                v-for="courseDate in selectedCourseDetail.upcomingDates"
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
            <div v-if="selectedCourseDetail.notifications.length === 0" class="mt-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
              Für diesen Kurs gibt es in den letzten sechs Monaten keine Mitteilungen.
            </div>
            <div v-else class="mt-3 space-y-3">
              <div
                v-for="notification in selectedCourseDetail.notifications"
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
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Course, CustomerCourseDetailResponse } from '~/types'

definePageMeta({ layout: 'customer' })

const api = useApi()
const toast = useToast()
const { dayName, formatDate, levelLabel } = useHelpers()

const availableCourses = ref<Course[]>([])
const subscribedCourses = ref<Course[]>([])
const loading = ref(true)
const showCourseDetailModal = ref(false)
const selectedCourse = ref<Course | null>(null)
const selectedCourseDetail = ref<CustomerCourseDetailResponse | null>(null)
const courseDetailLoading = ref(false)
let detailRequestId = 0

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

async function openCourseDetail(course: Course) {
  selectedCourse.value = course
  showCourseDetailModal.value = true
  selectedCourseDetail.value = null
  courseDetailLoading.value = true

  const requestId = ++detailRequestId

  try {
    const detail = await api.get<CustomerCourseDetailResponse>(`/api/customer/courses/${course.id}/detail`)
    if (requestId !== detailRequestId) return
    selectedCourseDetail.value = detail
  } catch {
    if (requestId !== detailRequestId) return
    toast.add({
      title: 'Kursdetails konnten nicht geladen werden',
      description: 'Bitte versuche es gleich noch einmal.',
      color: 'red',
    })
    closeCourseDetail()
  } finally {
    if (requestId === detailRequestId) {
      courseDetailLoading.value = false
    }
  }
}

function closeCourseDetail() {
  showCourseDetailModal.value = false
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
  loading.value = true
  try {
    const [allRes, subRes] = await Promise.all([
      api.get<ApiListResponse<Course>>('/api/customer/courses'),
      api.get<ApiListResponse<Course>>('/api/customer/courses/subscribed'),
    ])
    availableCourses.value = allRes.items
    subscribedCourses.value = subRes.items
  } finally {
    loading.value = false
  }
}

onMounted(() => loadCourses())

watch(showCourseDetailModal, (isOpen) => {
  if (isOpen) return

  detailRequestId += 1
  courseDetailLoading.value = false
  selectedCourse.value = null
  selectedCourseDetail.value = null
})
</script>
