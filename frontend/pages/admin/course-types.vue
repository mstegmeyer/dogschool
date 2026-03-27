<template>
  <div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-slate-800">Kursarten</h1>
      <UButton icon="i-heroicons-plus" label="Neue Kursart" class="justify-center" @click="openCreateModal" />
    </div>

    <UCard :ui="{ body: { padding: 'p-0 sm:p-0' } }">
      <div v-if="courseTypes.length === 0 && !loading" class="text-center py-8 text-slate-400 text-sm">
        Noch keine Kursarten erstellt
      </div>
      <template v-else>
        <div class="space-y-3 p-4 md:hidden">
          <div
            v-for="courseType in courseTypes"
            :key="courseType.id"
            class="rounded-lg border border-slate-200 bg-white p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="font-medium text-slate-800">{{ courseType.name }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                  <span class="font-mono text-xs font-semibold text-slate-700 bg-slate-100 rounded px-1.5 py-0.5">
                    {{ courseType.code }}
                  </span>
                  <span
                    class="inline-flex items-center text-xs font-medium rounded px-1.5 py-0.5"
                    :class="recurrenceBadgeClass(courseType.recurrenceKind)"
                  >
                    {{ recurrenceLabel(courseType.recurrenceKind) }}
                  </span>
                </div>
              </div>
              <div class="flex gap-1">
                <UButton icon="i-heroicons-pencil" size="xs" variant="ghost" @click="openEditModal(courseType)" />
                <UButton icon="i-heroicons-trash" size="xs" variant="ghost" color="red" @click="deleteCourseType(courseType)" />
              </div>
            </div>
          </div>
        </div>
        <div class="hidden overflow-x-auto md:block">
          <UTable
            :columns="columns"
            :rows="courseTypes"
            :loading="loading"
            :ui="{
              th: { base: 'text-left text-xs font-semibold text-slate-500 py-2 px-3' },
              td: { base: 'py-1.5 px-3 align-top text-sm' },
            }"
          >
            <template #code-data="{ row }">
              <span class="font-mono text-xs font-semibold text-slate-700 bg-slate-100 rounded px-1.5 py-0.5">
                {{ row.code }}
              </span>
            </template>
            <template #name-data="{ row }">
              <span class="font-medium text-slate-800">{{ row.name }}</span>
            </template>
            <template #recurrenceKind-data="{ row }">
              <span
                class="inline-flex items-center text-xs font-medium rounded px-1.5 py-0.5"
                :class="recurrenceBadgeClass(row.recurrenceKind)"
              >
                {{ recurrenceLabel(row.recurrenceKind) }}
              </span>
            </template>
            <template #actions-data="{ row }">
              <div class="flex gap-0.5 justify-end shrink-0">
                <UButton icon="i-heroicons-pencil" size="xs" variant="ghost" @click="openEditModal(row)" />
                <UButton icon="i-heroicons-trash" size="xs" variant="ghost" color="red" @click="deleteCourseType(row)" />
              </div>
            </template>
          </UTable>
        </div>
      </template>
    </UCard>

    <UModal v-model="showModal">
      <UCard>
        <template #header>
          <h3 class="font-semibold text-slate-800">
            {{ editingCourseType ? 'Kursart bearbeiten' : 'Neue Kursart' }}
          </h3>
        </template>
        <form class="space-y-4" @submit.prevent="saveCourseType">
          <UFormGroup label="Kürzel">
            <UInput v-model="form.code" placeholder="z.B. AGI" maxlength="20" required />
          </UFormGroup>
          <UFormGroup label="Name">
            <UInput v-model="form.name" placeholder="z.B. Agility" required />
          </UFormGroup>
          <UFormGroup label="Wiederholungsart">
            <USelectMenu
              v-model="form.recurrenceKind"
              :options="recurrenceOptions"
              value-attribute="value"
            />
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
import type { ApiListResponse, CourseType } from '~/types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const toast = useToast()

const courseTypes = ref<CourseType[]>([])
const loading = ref(true)
const showModal = ref(false)
const saving = ref(false)
const editingCourseType = ref<CourseType | null>(null)

const form = reactive({ code: '', name: '', recurrenceKind: 'RECURRING' })

const columns = [
  { key: 'code', label: 'Kürzel' },
  { key: 'name', label: 'Name' },
  { key: 'recurrenceKind', label: 'Wiederholungsart' },
  { key: 'actions', label: '' },
]

const recurrenceOptions = [
  { label: 'Wiederkehrend', value: 'RECURRING' },
  { label: 'Einmalig', value: 'ONE_TIME' },
  { label: 'Drop-In', value: 'DROP_IN' },
]

function recurrenceLabel(kind: string): string {
  return recurrenceOptions.find(o => o.value === kind)?.label ?? kind
}

function recurrenceBadgeClass(kind: string): string {
  switch (kind) {
    case 'RECURRING': return 'text-emerald-700 bg-emerald-50'
    case 'ONE_TIME': return 'text-blue-700 bg-blue-50'
    case 'DROP_IN': return 'text-amber-700 bg-amber-50'
    default: return 'text-slate-600 bg-slate-100'
  }
}

function openCreateModal() {
  editingCourseType.value = null
  form.code = ''
  form.name = ''
  form.recurrenceKind = 'RECURRING'
  showModal.value = true
}

function openEditModal(ct: CourseType) {
  editingCourseType.value = ct
  form.code = ct.code
  form.name = ct.name
  form.recurrenceKind = ct.recurrenceKind
  showModal.value = true
}

async function saveCourseType() {
  saving.value = true
  try {
    const payload = { code: form.code, name: form.name, recurrenceKind: form.recurrenceKind }
    if (editingCourseType.value) {
      await api.put(`/api/admin/course-types/${editingCourseType.value.id}`, payload)
      toast.add({ title: 'Kursart aktualisiert', color: 'green' })
    } else {
      await api.post('/api/admin/course-types', payload)
      toast.add({ title: 'Kursart erstellt', color: 'green' })
    }
    showModal.value = false
    await loadCourseTypes()
  } catch {
    toast.add({ title: 'Fehler beim Speichern', color: 'red' })
  } finally {
    saving.value = false
  }
}

async function deleteCourseType(ct: CourseType) {
  await api.del(`/api/admin/course-types/${ct.id}`)
  toast.add({ title: 'Kursart gelöscht', color: 'green' })
  await loadCourseTypes()
}

async function loadCourseTypes(): Promise<void> {
  loading.value = true
  const res = await api.get<ApiListResponse<CourseType>>('/api/admin/course-types')
  courseTypes.value = res.items
  loading.value = false
}

onMounted(() => loadCourseTypes())
</script>
