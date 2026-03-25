<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Mitteilungen</h1>
      <UButton icon="i-heroicons-plus" label="Neue Mitteilung" @click="openCreateModal" />
    </div>

    <UCard :ui="{ body: { padding: 'p-0 sm:p-0' } }">
      <div v-if="notifications.length === 0 && !loading" class="text-center py-8 text-slate-400 text-sm">
        Noch keine Mitteilungen erstellt
      </div>
      <div v-else class="overflow-x-auto">
        <UTable
          :columns="columns"
          :rows="notifications"
          :loading="loading"
          :ui="{
            th: { base: 'text-left text-xs font-semibold text-slate-500 py-2 px-3' },
            td: { base: 'py-1.5 px-3 align-top text-sm' },
          }"
        >
          <template #createdAt-data="{ row }">
            <span class="text-xs text-slate-500 whitespace-nowrap tabular-nums">
              {{ formatDateTime(row.createdAt) }}
            </span>
          </template>
          <template #pinnedUntil-data="{ row }">
            <span v-if="row.isPinned" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-700 bg-indigo-50 rounded px-1.5 py-0.5 whitespace-nowrap">
              <UIcon name="i-heroicons-map-pin" class="w-3.5 h-3.5" />
              bis {{ formatDate(row.pinnedUntil) }}
            </span>
            <span v-else-if="row.pinnedUntil" class="text-xs text-slate-400 whitespace-nowrap" :title="`Abgelaufen: ${formatDate(row.pinnedUntil)}`">
              abgelaufen
            </span>
            <span v-else class="text-xs text-slate-300">–</span>
          </template>
          <template #title-data="{ row }">
            <span class="font-medium text-slate-800 line-clamp-2" :title="row.title">{{ row.title }}</span>
          </template>
          <template #courses-data="{ row }">
            <span v-if="row.isGlobal" class="inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-50 rounded px-1.5 py-0.5">
              <UIcon name="i-heroicons-globe-alt" class="w-3.5 h-3.5" />
              Alle Kurse
            </span>
            <div v-else class="flex flex-wrap gap-1 max-w-[260px]">
              <span
                v-for="c in row.courses"
                :key="c.id"
                class="text-xs text-slate-600 bg-slate-100 rounded px-1.5 py-0.5 whitespace-nowrap"
              >
                {{ formatNotificationCourse(c) }}
              </span>
            </div>
          </template>
          <template #message-data="{ row }">
            <span class="text-xs text-slate-500 line-clamp-2 block max-w-[280px]" :title="row.message">
              {{ row.message }}
            </span>
          </template>
          <template #authorName-data="{ row }">
            <span class="text-xs text-slate-500 whitespace-nowrap">{{ row.authorName || '–' }}</span>
          </template>
          <template #actions-data="{ row }">
            <div class="flex gap-0.5 justify-end shrink-0">
              <UButton icon="i-heroicons-pencil" size="xs" variant="ghost" @click="openEditModal(row)" />
              <UButton icon="i-heroicons-trash" size="xs" variant="ghost" color="red" @click="deleteNotification(row)" />
            </div>
          </template>
        </UTable>
      </div>
    </UCard>

    <UModal v-model="showModal">
      <UCard>
        <template #header>
          <h3 class="font-semibold text-slate-800">
            {{ editingNotification ? 'Mitteilung bearbeiten' : 'Neue Mitteilung' }}
          </h3>
        </template>
        <form class="space-y-4" @submit.prevent="saveNotification">
          <UFormGroup label="Reichweite">
            <div class="flex items-center gap-3 mb-2">
              <UToggle v-model="form.isGlobal" />
              <span class="text-sm text-slate-600">
                {{ form.isGlobal ? 'Globale Mitteilung (alle Kunden)' : 'Bestimmte Kurse auswählen' }}
              </span>
            </div>
            <USelectMenu
              v-if="!form.isGlobal"
              v-model="form.courseIds"
              :options="courseOptions"
              value-attribute="value"
              placeholder="Kurse auswählen…"
              multiple
            />
          </UFormGroup>
          <UFormGroup label="Titel">
            <UInput v-model="form.title" placeholder="Betreff" required />
          </UFormGroup>
          <UFormGroup label="Nachricht">
            <UTextarea v-model="form.message" placeholder="Nachricht an die Kursteilnehmer…" :rows="4" required />
          </UFormGroup>
          <UFormGroup label="Angepinnt bis" hint="Optional – Mitteilung wird bis zu diesem Datum oben angezeigt">
            <UInput v-model="form.pinnedUntil" type="date" />
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
import type { ApiListResponse, Notification as AppNotification, Course } from '~/types'

definePageMeta({ layout: 'admin' })

const api = useApi()
const toast = useToast()
const { formatDate, formatDateTime, formatNotificationCourse, dayName } = useHelpers()

const notifications = ref<AppNotification[]>([])
const courses = ref<Course[]>([])
const loading = ref(true)
const showModal = ref(false)
const saving = ref(false)
const editingNotification = ref<AppNotification | null>(null)

const form = reactive({ courseIds: [] as string[], title: '', message: '', isGlobal: false, pinnedUntil: '' })

const columns = [
  { key: 'createdAt', label: 'Datum' },
  { key: 'pinnedUntil', label: 'Angepinnt' },
  { key: 'title', label: 'Titel' },
  { key: 'courses', label: 'Kurse' },
  { key: 'message', label: 'Text' },
  { key: 'authorName', label: 'Von' },
  { key: 'actions', label: '' },
]

const courseOptions = computed(() =>
  courses.value.filter(c => !c.archived).map(c => ({
    label: `${c.type?.name || c.type?.code || 'Kurs'} · ${dayName(c.dayOfWeek)} ${c.startTime}`,
    value: c.id,
  })),
)

function pinnedUntilToDateInput(iso: string | null): string {
  if (!iso) return ''
  return iso.slice(0, 10)
}

function openCreateModal() {
  editingNotification.value = null
  form.courseIds = []
  form.title = ''
  form.message = ''
  form.isGlobal = false
  form.pinnedUntil = ''
  showModal.value = true
}

function openEditModal(n: AppNotification) {
  editingNotification.value = n
  form.title = n.title
  form.message = n.message
  form.isGlobal = n.isGlobal
  form.courseIds = n.isGlobal ? [] : [...n.courseIds]
  form.pinnedUntil = pinnedUntilToDateInput(n.pinnedUntil)
  showModal.value = true
}

async function saveNotification() {
  saving.value = true
  try {
    const courseIds = form.isGlobal ? [] : form.courseIds
    const pinnedUntil = form.pinnedUntil ? `${form.pinnedUntil}T23:59:59` : ''
    if (editingNotification.value) {
      await api.put(`/api/admin/notifications/${editingNotification.value.id}`, {
        title: form.title,
        message: form.message,
        courseIds,
        pinnedUntil,
      })
      toast.add({ title: 'Mitteilung aktualisiert', color: 'green' })
    } else {
      await api.post('/api/admin/notifications', {
        title: form.title,
        message: form.message,
        courseIds,
        pinnedUntil: pinnedUntil || null,
      })
      toast.add({ title: 'Mitteilung erstellt', color: 'green' })
    }
    showModal.value = false
    await loadNotifications()
  } catch {
    toast.add({ title: 'Fehler beim Speichern', color: 'red' })
  } finally {
    saving.value = false
  }
}

async function deleteNotification(n: AppNotification) {
  await api.del(`/api/admin/notifications/${n.id}`)
  toast.add({ title: 'Mitteilung gelöscht', color: 'green' })
  await loadNotifications()
}

async function loadNotifications(): Promise<void> {
  loading.value = true
  const res = await api.get<ApiListResponse<AppNotification>>('/api/admin/notifications')
  notifications.value = res.items
  loading.value = false
}

onMounted(async () => {
  const [, courseRes] = await Promise.all([
    loadNotifications(),
    api.get<ApiListResponse<Course>>('/api/admin/courses?archived=false'),
  ])
  courses.value = courseRes.items
})
</script>
