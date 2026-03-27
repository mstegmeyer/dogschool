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
          <UFormGroup label="Kurstyp (Code)" :error="errorFor('typeCode')">
            <UInput v-model="form.typeCode" placeholder="z.B. MH, JUHU, AGI" required @update:model-value="clearFieldError('typeCode')" />
          </UFormGroup>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <UFormGroup label="Wochentag" :error="errorFor('dayOfWeek')">
              <USelectMenu v-model="form.dayOfWeek" :options="dayOptions" value-attribute="value" @update:model-value="clearFieldError('dayOfWeek')" />
            </UFormGroup>
            <UFormGroup label="Stufe (0-4)" :error="errorFor('level')">
              <UInput v-model.number="form.level" type="number" min="0" max="4" @update:model-value="clearFieldError('level')" />
            </UFormGroup>
          </div>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <UFormGroup label="Startzeit" :error="errorFor('startTime')">
              <UInput v-model="form.startTime" type="time" required @update:model-value="clearFieldError('startTime')" />
            </UFormGroup>
            <UFormGroup label="Endzeit" :error="errorFor('endTime')">
              <UInput v-model="form.endTime" type="time" required @update:model-value="clearFieldError('endTime')" />
            </UFormGroup>
          </div>
          <div
            v-if="showScheduleHint"
            class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
          >
            {{ scheduleHintText }}
          </div>
          <UFormGroup label="Kommentar" :error="errorFor('comment')">
            <UTextarea v-model="form.comment" placeholder="Optionaler Kommentar" @update:model-value="clearFieldError('comment')" />
          </UFormGroup>
          <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" label="Abbrechen" @click="closeCourseModal" />
            <UButton type="submit" :loading="saving" label="Speichern" />
          </div>
        </form>
      </UCard>
    </UModal>

    <UModal v-model="showArchiveModal">
      <UCard>
        <template #header>
          <h3 class="font-semibold text-slate-800">Kurs archivieren</h3>
        </template>

        <div v-if="archiveCourse" class="space-y-4">
          <p class="text-sm text-slate-600">
            Der Kurs <span class="font-medium text-slate-800">{{ archiveCourseLabel }}</span> wird archiviert. Alle Kurstermine ab dem gewählten Datum werden entfernt, und bestehende Buchungen auf diesen Terminen bekommen ihre Credits automatisch zurück.
          </p>

          <UFormGroup label="Termine entfernen ab" :error="archiveError">
            <UInput
              v-model="archiveForm.removeFromDate"
              type="date"
              :min="archiveMinDate"
              required
            />
          </UFormGroup>

          <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-medium">Bitte bestätigen:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
              <li>Der Kurs wird auf archiviert gesetzt.</li>
              <li>Alle zukünftigen Termine ab {{ formatDate(archiveForm.removeFromDate) }} werden gelöscht.</li>
              <li>Bereits vorhandene Buchungen auf gelöschten Terminen werden automatisch erstattet.</li>
            </ul>
          </div>

          <div class="flex justify-end gap-2">
            <UButton variant="ghost" label="Abbrechen" :disabled="archiving" @click="closeArchiveModal" />
            <UButton color="red" :loading="archiving" label="Verbindlich archivieren" @click="confirmArchive" />
          </div>
        </div>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import type { FetchError } from 'ofetch'
import type { ApiListResponse, Course } from '~/types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const toast = useToast()
const { dayName, formatDate, levelLabel, todayIso } = useHelpers()
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError, errorFor } = useFormFeedback()

const courses = ref<Course[]>([])
const loading = ref(true)
const showModal = ref(false)
const showArchiveModal = ref(false)
const saving = ref(false)
const archiving = ref(false)
const editingCourse = ref<Course | null>(null)
const archiveCourse = ref<Course | null>(null)
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

const archiveMinDate = computed(() => todayIso())
const showScheduleHint = computed(() => editingCourse.value !== null && (
  form.dayOfWeek !== editingCourse.value.dayOfWeek
  || form.startTime !== editingCourse.value.startTime
  || form.endTime !== editingCourse.value.endTime
))
const scheduleHintText = computed(() => {
  if (editingCourse.value === null) return ''

  const dayChanged = form.dayOfWeek !== editingCourse.value.dayOfWeek
  const timeChanged = form.startTime !== editingCourse.value.startTime || form.endTime !== editingCourse.value.endTime

  if (dayChanged && timeChanged) {
    return 'Beim Speichern werden alle zukünftigen Kurstermine dieses Kurses auf den gewählten Wochentag und die neue Uhrzeit umgestellt.'
  }

  if (dayChanged) {
    return 'Beim Speichern werden alle zukünftigen Kurstermine dieses Kurses auf den gewählten Wochentag umgestellt.'
  }

  return 'Beim Speichern wird die Uhrzeit aller zukünftigen Kurstermine dieses Kurses aktualisiert.'
})

const form = reactive({
  typeCode: '',
  dayOfWeek: 1,
  startTime: '10:00',
  endTime: '11:00',
  level: 0,
  comment: '',
})

const archiveForm = reactive({
  removeFromDate: todayIso(),
})
const archiveError = ref('')

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

const archiveCourseLabel = computed(() => {
  if (!archiveCourse.value) return ''

  return `${archiveCourse.value.type?.name || 'Kurs'} · ${dayName(archiveCourse.value.dayOfWeek)} · ${archiveCourse.value.startTime} – ${archiveCourse.value.endTime}`
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
  clearFormErrors()
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
  clearFormErrors()
  showModal.value = true
}

function closeCourseModal() {
  showModal.value = false
  clearFormErrors()
}

function openArchiveModal(course: Course) {
  archiveCourse.value = course
  archiveForm.removeFromDate = archiveMinDate.value
  archiveError.value = ''
  showArchiveModal.value = true
}

function closeArchiveModal() {
  if (archiving.value) return

  showArchiveModal.value = false
  archiveCourse.value = null
  archiveForm.removeFromDate = archiveMinDate.value
  archiveError.value = ''
}

function resolveArchiveError(cause: unknown): string {
  const error = cause as FetchError<{ error?: string }>

  return error.data?.error || 'Fehler beim Archivieren des Kurses'
}

async function saveCourse() {
  clearFormErrors()
  if (!form.typeCode.trim()) setFieldError('typeCode', 'Bitte einen Kurstyp angeben.')
  if (!form.dayOfWeek) setFieldError('dayOfWeek', 'Bitte einen Wochentag wählen.')
  if (form.level < 0 || form.level > 4) setFieldError('level', 'Bitte eine Stufe zwischen 0 und 4 angeben.')
  if (!form.startTime) setFieldError('startTime', 'Bitte eine Startzeit angeben.')
  if (!form.endTime) setFieldError('endTime', 'Bitte eine Endzeit angeben.')
  if (Object.keys(fieldErrors.value).length > 0) {
    setFormError('Bitte prüfe die markierten Felder.')
    return
  }

  saving.value = true
  try {
    if (editingCourse.value) {
      await api.put(`/api/admin/courses/${editingCourse.value.id}`, form)
      toast.add({ title: 'Kurs aktualisiert', color: 'green' })
    } else {
      await api.post('/api/admin/courses', form)
      toast.add({ title: 'Kurs erstellt', color: 'green' })
    }
    closeCourseModal()
    await loadCourses()
  } catch (cause) {
    applyApiError(cause, 'Der Kurs konnte nicht gespeichert werden.')
  } finally {
    saving.value = false
  }
}

async function toggleArchive(course: Course) {
  if (!course.archived) {
    openArchiveModal(course)
    return
  }

  try {
    await api.post(`/api/admin/courses/${course.id}/unarchive`)
    toast.add({ title: 'Kurs reaktiviert', color: 'green' })
    await loadCourses()
  } catch (cause) {
    toast.add({ title: extractApiErrorMessage(cause, 'Der Kurs konnte nicht reaktiviert werden.', { preferFieldSummary: false }), color: 'red' })
  }
}

async function confirmArchive() {
  if (!archiveCourse.value) return
  archiveError.value = ''
  if (!archiveForm.removeFromDate) {
    archiveError.value = 'Bitte ein Datum auswählen.'
    return
  }

  archiving.value = true

  try {
    const response = await api.post<Course & {
      removeFromDate: string
      removedCourseDates: number
      refundedBookings: number
    }>(`/api/admin/courses/${archiveCourse.value.id}/archive`, {
      removeFromDate: archiveForm.removeFromDate,
    })

    toast.add({
      title: 'Kurs archiviert',
      description: `${response.removedCourseDates} Termine ab ${formatDate(response.removeFromDate)} entfernt, ${response.refundedBookings} Buchungen erstattet.`,
      color: 'green',
    })

    showArchiveModal.value = false
    archiveCourse.value = null
    archiveForm.removeFromDate = archiveMinDate.value
    archiveError.value = ''
    await loadCourses()
  } catch (error) {
    archiveError.value = resolveArchiveError(error)
  } finally {
    archiving.value = false
  }
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
