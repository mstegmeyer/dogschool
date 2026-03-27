<template>
  <div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-slate-800">Kurse</h1>
      <div class="flex flex-col gap-2 sm:flex-row">
        <USelectMenu v-model="archiveFilter" :options="filterOptions" value-attribute="value" class="w-full sm:w-40" />
        <UButton icon="i-heroicons-plus" label="Neuer Kurs" class="justify-center" @click="openCreateModal" />
      </div>
    </div>

    <UCard>
      <AppSkeletonCollection
        v-if="loading"
        :mobile-cards="4"
        :desktop-rows="6"
        :desktop-columns="7"
        :meta-columns="2"
        :show-actions="true"
      />
      <div v-else-if="courses.length === 0" class="text-sm text-slate-400">Keine Kurse gefunden.</div>
      <template v-else>
        <div class="space-y-3 md:hidden">
          <div
            v-for="course in courses"
            :key="course.id"
            class="rounded-lg border border-slate-200 bg-white p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="font-medium text-slate-800">{{ course.type?.name || '–' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ dayName(course.dayOfWeek) }} · {{ course.startTime }} – {{ course.endTime }}</p>
              </div>
              <div class="flex flex-col items-end gap-2">
                <UBadge size="xs" variant="soft" color="gray">{{ course.type?.code }}</UBadge>
                <UBadge :color="course.archived ? 'gray' : 'primary'" variant="soft" size="xs">
                  {{ course.archived ? 'Archiviert' : 'Aktiv' }}
                </UBadge>
              </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
              <div>
                <p class="text-slate-400">Stufe</p>
                <p class="font-medium text-slate-700">{{ levelLabel(course.level) }}</p>
              </div>
              <div>
                <p class="text-slate-400">Abonnenten</p>
                <p class="font-medium text-slate-700">{{ course.subscriberCount }}</p>
              </div>
            </div>
            <p v-if="course.comment" class="mt-3 text-sm text-slate-600">{{ course.comment }}</p>
            <div class="mt-4 grid grid-cols-2 gap-2">
              <UButton size="sm" variant="soft" label="Bearbeiten" @click="openEditModal(course)" />
              <UButton
                size="sm"
                :color="course.archived ? 'primary' : 'gray'"
                variant="ghost"
                :label="course.archived ? 'Reaktivieren' : 'Archivieren'"
                @click="toggleArchive(course)"
              />
            </div>
          </div>
        </div>
        <div class="hidden md:block">
          <UTable
            v-model:sort="sort"
            :columns="columns"
            :rows="courses"
            sort-mode="manual"
          >
            <template #type-data="{ row }">
              <div>
                <span class="font-medium">{{ row.type?.name || '–' }}</span>
                <UBadge class="ml-2" size="xs" variant="soft" color="gray">{{ row.type?.code }}</UBadge>
              </div>
            </template>
            <template #dayOfWeek-data="{ row }">
              {{ dayName(row.dayOfWeek) }}
            </template>
            <template #time-data="{ row }">
              {{ row.startTime }} – {{ row.endTime }}
            </template>
            <template #level-data="{ row }">
              {{ levelLabel(row.level) }}
            </template>
            <template #subscribers-data="{ row }">
              <UTooltip
                v-if="row.subscriberCount > 0"
                :text="row.subscribers.map((s: { name: string }) => s.name).join(', ')"
              >
                <span class="font-medium text-komm-600 cursor-default">{{ row.subscriberCount }}</span>
              </UTooltip>
              <span v-else class="text-slate-400">0</span>
            </template>
            <template #archived-data="{ row }">
              <UBadge :color="row.archived ? 'gray' : 'primary'" variant="soft" size="xs">
                {{ row.archived ? 'Archiviert' : 'Aktiv' }}
              </UBadge>
            </template>
            <template #actions-data="{ row }">
              <UDropdown :items="getRowActions(row)">
                <UButton variant="ghost" icon="i-heroicons-ellipsis-vertical" size="xs" />
              </UDropdown>
            </template>
          </UTable>
        </div>
        <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm text-slate-500">{{ resultSummary }}</p>
          <UPagination
            v-if="showPagination"
            v-model="currentPage"
            :page-count="pageSize"
            :total="totalCourses"
            :show-first="true"
            :show-last="true"
          />
        </div>
      </template>
    </UCard>

    <!-- Create/Edit Modal -->
    <UModal v-model="showModal">
      <UCard>
        <template #header>
          <h3 class="font-semibold text-slate-800">{{ editingCourse ? 'Kurs bearbeiten' : 'Neuer Kurs' }}</h3>
        </template>
        <form class="space-y-4" @submit.prevent="saveCourse">
          <UFormGroup label="Kurstyp (Code)">
            <UInput v-model="form.typeCode" placeholder="z.B. MH, JUHU, AGI" required />
          </UFormGroup>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <UFormGroup label="Wochentag">
              <USelectMenu v-model="form.dayOfWeek" :options="dayOptions" value-attribute="value" />
            </UFormGroup>
            <UFormGroup label="Stufe (0-4)">
              <UInput v-model.number="form.level" type="number" min="0" max="4" />
            </UFormGroup>
          </div>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <UFormGroup label="Startzeit">
              <UInput v-model="form.startTime" type="time" required />
            </UFormGroup>
            <UFormGroup label="Endzeit">
              <UInput v-model="form.endTime" type="time" required />
            </UFormGroup>
          </div>
          <UFormGroup label="Kommentar">
            <UTextarea v-model="form.comment" placeholder="Optionaler Kommentar" />
          </UFormGroup>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" label="Abbrechen" @click="showModal = false" />
            <UButton type="submit" :loading="saving" label="Speichern" />
          </div>
        </form>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Course } from '~/types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const toast = useToast()
const { dayName, levelLabel } = useHelpers()

const courses = ref<Course[]>([])
const loading = ref(true)
const showModal = ref(false)
const saving = ref(false)
const editingCourse = ref<Course | null>(null)
const currentPage = ref(1)
const totalCourses = ref(0)
const totalPages = ref(1)
const sort = ref<{ column: string | null; direction: 'asc' | 'desc' }>({
  column: 'dayOfWeek',
  direction: 'asc',
})
const pageSize = 20

const archiveFilter = ref('active')
const filterOptions = [
  { label: 'Aktive Kurse', value: 'active' },
  { label: 'Archiviert', value: 'archived' },
  { label: 'Alle', value: 'all' },
]

const dayOptions = [
  { label: 'Montag', value: 1 },
  { label: 'Dienstag', value: 2 },
  { label: 'Mittwoch', value: 3 },
  { label: 'Donnerstag', value: 4 },
  { label: 'Freitag', value: 5 },
  { label: 'Samstag', value: 6 },
  { label: 'Sonntag', value: 7 },
]

const form = reactive({
  typeCode: '',
  dayOfWeek: 1,
  startTime: '10:00',
  endTime: '11:00',
  level: 0,
  comment: '',
})

const columns = [
  { key: 'type', label: 'Kurstyp' },
  { key: 'dayOfWeek', label: 'Tag', sortable: true },
  { key: 'time', label: 'Uhrzeit' },
  { key: 'level', label: 'Stufe' },
  { key: 'subscribers', label: 'Abonnenten' },
  { key: 'archived', label: 'Status', sortable: true },
  { key: 'actions', label: '' },
]

const showPagination = computed(() => totalCourses.value > pageSize)
const pageStart = computed(() => (totalCourses.value === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1))
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, totalCourses.value))
const resultSummary = computed(() => {
  if (totalCourses.value === 0) return '0 Kurse'
  if (totalPages.value <= 1) return `${totalCourses.value} Kurse`

  return `${pageStart.value}–${pageEnd.value} von ${totalCourses.value} Kursen`
})

function getRowActions(row: Course) {
  return [[
    {
      label: 'Bearbeiten',
      icon: 'i-heroicons-pencil',
      click: () => openEditModal(row),
    },
    {
      label: row.archived ? 'Reaktivieren' : 'Archivieren',
      icon: row.archived ? 'i-heroicons-arrow-path' : 'i-heroicons-archive-box',
      click: () => toggleArchive(row),
    },
  ]]
}

function openCreateModal() {
  editingCourse.value = null
  form.typeCode = ''
  form.dayOfWeek = 1
  form.startTime = '10:00'
  form.endTime = '11:00'
  form.level = 0
  form.comment = ''
  showModal.value = true
}

function openEditModal(course: Course) {
  editingCourse.value = course
  form.typeCode = course.type?.code || ''
  form.dayOfWeek = course.dayOfWeek
  form.startTime = course.startTime
  form.endTime = course.endTime
  form.level = course.level
  form.comment = course.comment || ''
  showModal.value = true
}

async function saveCourse() {
  saving.value = true
  try {
    if (editingCourse.value) {
      await api.put(`/api/admin/courses/${editingCourse.value.id}`, form)
      toast.add({ title: 'Kurs aktualisiert', color: 'green' })
    } else {
      await api.post('/api/admin/courses', form)
      toast.add({ title: 'Kurs erstellt', color: 'green' })
    }
    showModal.value = false
    await loadCourses()
  } catch {
    toast.add({ title: 'Fehler beim Speichern', color: 'red' })
  } finally {
    saving.value = false
  }
}

async function toggleArchive(course: Course) {
  const action = course.archived ? 'unarchive' : 'archive'
  await api.post(`/api/admin/courses/${course.id}/${action}`)
  toast.add({ title: course.archived ? 'Kurs reaktiviert' : 'Kurs archiviert', color: 'green' })
  await loadCourses()
}

async function loadCourses(): Promise<void> {
  loading.value = true
  const params = new URLSearchParams({
    page: `${currentPage.value}`,
    limit: `${pageSize}`,
  })
  if (archiveFilter.value !== 'all') {
    params.set('archived', `${archiveFilter.value === 'archived'}`)
  }
  if (sort.value.column) {
    params.set('sort', sort.value.column)
    params.set('direction', sort.value.direction)
  }
  const res = await api.get<ApiListResponse<Course>>(`/api/admin/courses?${params.toString()}`)
  courses.value = res.items
  totalCourses.value = res.pagination?.total ?? res.items.length
  totalPages.value = res.pagination?.pages ?? 1
  loading.value = false
}

watch(currentPage, () => {
  void loadCourses()
})

watch(archiveFilter, () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1
    return
  }

  void loadCourses()
})

watch(sort, () => {
  if (currentPage.value !== 1) {
    currentPage.value = 1
    return
  }

  void loadCourses()
}, { deep: true })

onMounted(() => {
  void loadCourses()
})
</script>
