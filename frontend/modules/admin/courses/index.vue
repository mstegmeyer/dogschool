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
        <AdminCourseListMobile
          :courses="courses"
          @edit="openEditModal"
          @toggle-archive="toggleArchive"
        />
        <AdminCourseTable
          :courses="courses"
          :sort="sort"
          @update:sort="sort = $event"
          @edit="openEditModal"
          @toggle-archive="toggleArchive"
        />
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

    <AdminCourseFormModal
      v-model="courseFormModalOpen"
      :editing-course="editingCourse !== null"
      :form="form"
      :day-options="dayOptions"
      :trainer-options="trainerOptions"
      :show-schedule-hint="showScheduleHint"
      :schedule-hint-text="scheduleHintText"
      :form-error="formError"
      :field-errors="fieldErrors"
      :saving="saving"
      @submit="saveCourse"
      @cancel="closeCourseModal"
      @clear-field-error="clearFieldError"
    />

    <AdminCourseArchiveModal
      v-model="archiveModalOpen"
      :course="archiveCourse"
      :remove-from-date="archiveForm.removeFromDate"
      :min-date="archiveMinDate"
      :error="archiveError"
      :archiving="archiving"
      @update:remove-from-date="archiveForm.removeFromDate = $event"
      @cancel="closeArchiveModal"
      @confirm="confirmArchive"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import type { FetchError } from 'ofetch'
import type { ApiListResponse, Course, TrainerInfo } from '~/types'
import AdminCourseArchiveModal from './components/ArchiveModal.vue'
import AdminCourseFormModal from './components/FormModal.vue'
import AdminCourseListMobile from './components/ListMobile.vue'
import AdminCourseTable from './components/CourseTable.vue'
import type { CourseFormState, CourseTableSort } from './types'

const api = useApi()
const toast = useToast()
const { todayIso, formatDate } = useHelpers()
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError } = useFormFeedback()

const courses = ref<Course[]>([])
const trainers = ref<TrainerInfo[]>([])
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
const sort = ref<CourseTableSort>({
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
const trainerOptions = computed(() => [
  { label: 'Keine Zuordnung', value: '' },
  ...trainers.value.map(trainer => ({
    label: trainer.fullName,
    value: trainer.id,
  })),
])

const form = reactive<CourseFormState>({
  typeCode: '',
  dayOfWeek: 1,
  startTime: '10:00',
  endTime: '11:00',
  level: 0,
  trainerId: '',
  comment: '',
})

const archiveForm = reactive({
  removeFromDate: todayIso(),
})

const archiveError = ref('')

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

const showPagination = computed(() => totalCourses.value > pageSize)
const pageStart = computed(() => (totalCourses.value === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1))
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, totalCourses.value))
const resultSummary = computed(() => {
  if (totalCourses.value === 0) return '0 Kurse'
  if (totalPages.value <= 1) return `${totalCourses.value} Kurse`
  return `${pageStart.value}–${pageEnd.value} von ${totalCourses.value} Kursen`
})

const courseFormModalOpen = computed({
  get: () => showModal.value,
  set: (open: boolean) => {
    if (open) {
      showModal.value = true
      return
    }

    closeCourseModal()
  },
})

const archiveModalOpen = computed({
  get: () => showArchiveModal.value,
  set: (open: boolean) => {
    if (open) {
      showArchiveModal.value = true
      return
    }

    closeArchiveModal()
  },
})

function resetCourseForm(): void {
  form.typeCode = ''
  form.dayOfWeek = 1
  form.startTime = '10:00'
  form.endTime = '11:00'
  form.level = 0
  form.trainerId = ''
  form.comment = ''
}

function openCreateModal(): void {
  editingCourse.value = null
  resetCourseForm()
  clearFormErrors()
  showModal.value = true
}

function openEditModal(course: Course): void {
  editingCourse.value = course
  form.typeCode = course.type?.code || ''
  form.dayOfWeek = course.dayOfWeek
  form.startTime = course.startTime
  form.endTime = course.endTime
  form.level = course.level
  form.trainerId = course.trainer?.id || ''
  form.comment = course.comment || ''
  clearFormErrors()
  showModal.value = true
}

function closeCourseModal(): void {
  showModal.value = false
  clearFormErrors()
}

function openArchiveModal(course: Course): void {
  archiveCourse.value = course
  archiveForm.removeFromDate = archiveMinDate.value
  archiveError.value = ''
  showArchiveModal.value = true
}

function closeArchiveModal(): void {
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

async function saveCourse(): Promise<void> {
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
    const payload = {
      ...form,
      trainerId: form.trainerId || null,
    }

    if (editingCourse.value) {
      await api.put(`/api/admin/courses/${editingCourse.value.id}`, payload)
      toast.add({ title: 'Kurs aktualisiert', color: 'green' })
    } else {
      await api.post('/api/admin/courses', payload)
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

async function loadTrainers(): Promise<void> {
  const response = await api.get<ApiListResponse<TrainerInfo>>('/api/admin/trainers')
  trainers.value = response.items
}

async function toggleArchive(course: Course): Promise<void> {
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

async function confirmArchive(): Promise<void> {
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

    closeArchiveModal()
    await loadCourses()
  } catch (error) {
    archiveError.value = resolveArchiveError(error)
  } finally {
    archiving.value = false
  }
}

async function loadCourses(): Promise<void> {
  loading.value = true
  try {
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

    const response = await api.get<ApiListResponse<Course>>(`/api/admin/courses?${params.toString()}`)
    courses.value = response.items
    totalCourses.value = response.pagination?.total ?? response.items.length
    totalPages.value = response.pagination?.pages ?? 1
  } finally {
    loading.value = false
  }
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
  void Promise.all([
    loadCourses(),
    loadTrainers(),
  ])
})
</script>
