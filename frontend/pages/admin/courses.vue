<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Kurse</h1>
      <div class="flex gap-2">
        <USelectMenu v-model="archiveFilter" :options="filterOptions" value-attribute="value" class="w-40" />
        <UButton icon="i-heroicons-plus" label="Neuer Kurs" @click="openCreateModal" />
      </div>
    </div>

    <UCard>
      <UTable :columns="columns" :rows="courses" :loading="loading">
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
            <span class="font-medium text-green-600 cursor-default">{{ row.subscriberCount }}</span>
          </UTooltip>
          <span v-else class="text-slate-400">0</span>
        </template>
        <template #archived-data="{ row }">
          <UBadge :color="row.archived ? 'gray' : 'green'" variant="soft" size="xs">
            {{ row.archived ? 'Archiviert' : 'Aktiv' }}
          </UBadge>
        </template>
        <template #actions-data="{ row }">
          <UDropdown :items="getRowActions(row)">
            <UButton variant="ghost" icon="i-heroicons-ellipsis-vertical" size="xs" />
          </UDropdown>
        </template>
      </UTable>
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
          <div class="grid grid-cols-2 gap-4">
            <UFormGroup label="Wochentag">
              <USelectMenu v-model="form.dayOfWeek" :options="dayOptions" value-attribute="value" />
            </UFormGroup>
            <UFormGroup label="Stufe (0-4)">
              <UInput v-model.number="form.level" type="number" min="0" max="4" />
            </UFormGroup>
          </div>
          <div class="grid grid-cols-2 gap-4">
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
  { key: 'archived', label: 'Status' },
  { key: 'actions', label: '' },
]

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
  const query = archiveFilter.value === 'all' ? '' : `?archived=${archiveFilter.value === 'archived'}`
  const res = await api.get<ApiListResponse<Course>>(`/api/admin/courses${query}`)
  courses.value = res.items
  loading.value = false
}

watch(archiveFilter, () => loadCourses())
onMounted(() => loadCourses())
</script>
