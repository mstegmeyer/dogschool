<template>
  <div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-slate-800">Mitteilungen</h1>
      <UButton icon="i-heroicons-plus" label="Neue Mitteilung" class="justify-center" @click="openCreateModal" />
    </div>

    <UCard :ui="{ body: { padding: 'p-0 sm:p-0' } }">
      <div v-if="loading" class="p-4">
        <AppSkeletonCollection
          :mobile-cards="4"
          :desktop-rows="6"
          :desktop-columns="7"
          :meta-columns="0"
          :content-lines="3"
          :show-actions="true"
        />
      </div>
      <div v-else-if="notifications.length === 0" class="text-center py-8 text-slate-400 text-sm">
        Noch keine Mitteilungen erstellt
      </div>
      <template v-else>
        <div class="space-y-3 p-4 md:hidden">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="rounded-lg border border-slate-200 bg-white p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="font-medium text-slate-800">{{ notification.title }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ formatDateTime(notification.createdAt) }}</p>
              </div>
              <div class="flex gap-1">
                <UButton icon="i-heroicons-pencil" size="xs" variant="ghost" @click="openEditModal(notification)" />
                <UButton icon="i-heroicons-trash" size="xs" variant="ghost" color="red" @click="deleteNotification(notification)" />
              </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <span v-if="notification.isPinned" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-700 bg-indigo-50 rounded px-1.5 py-0.5 whitespace-nowrap">
                <UIcon name="i-heroicons-map-pin" class="h-3.5 w-3.5" />
                bis {{ formatDate(notification.pinnedUntil) }}
              </span>
              <span v-else-if="notification.pinnedUntil" class="text-xs text-slate-400 whitespace-nowrap">
                abgelaufen
              </span>
              <span v-if="notification.isGlobal" class="inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-50 rounded px-1.5 py-0.5">
                <UIcon name="i-heroicons-globe-alt" class="h-3.5 w-3.5" />
                Alle Kurse
              </span>
              <span
                v-for="course in notification.isGlobal ? [] : notification.courses"
                :key="course.id"
                class="text-xs text-slate-600 bg-slate-100 rounded px-1.5 py-0.5"
              >
                {{ formatNotificationCourse(course) }}
              </span>
            </div>
            <p class="mt-3 text-sm text-slate-600">
              {{ notification.message }}
            </p>
            <p class="mt-3 text-xs text-slate-400">
              Von {{ notification.authorName || '–' }}
            </p>
          </div>
        </div>
        <div class="hidden overflow-x-auto md:block">
          <UTable
            :columns="columns"
            :rows="notifications"
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
        <div class="border-t border-slate-100 px-4 py-4 sm:flex sm:items-center sm:justify-between sm:px-6">
          <p class="text-sm text-slate-500">{{ resultSummary }}</p>
          <UPagination
            v-if="showPagination"
            v-model="currentPage"
            :page-count="pageSize"
            :total="totalNotifications"
            :show-first="true"
            :show-last="true"
          />
        </div>
      </template>
    </UCard>

    <UModal v-model="showModal">
      <UCard>
        <template #header>
          <h3 class="font-semibold text-slate-800">
            {{ editingNotification ? 'Mitteilung bearbeiten' : 'Neue Mitteilung' }}
          </h3>
        </template>
        <form class="space-y-4" @submit.prevent="saveNotification">
          <UFormGroup label="Reichweite" :error="errorFor('courseIds')">
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
              @update:model-value="clearFieldError('courseIds')"
            />
          </UFormGroup>
          <UFormGroup label="Titel" :error="errorFor('title')">
            <UInput v-model="form.title" placeholder="Betreff" required @update:model-value="clearFieldError('title')" />
          </UFormGroup>
          <UFormGroup label="Nachricht" :error="errorFor('message')">
            <UTextarea v-model="form.message" placeholder="Nachricht an die Kursteilnehmer…" :rows="4" required @update:model-value="clearFieldError('message')" />
          </UFormGroup>
          <UFormGroup label="Angepinnt bis" hint="Optional – Mitteilung wird bis zu diesem Datum oben angezeigt" :error="errorFor('pinnedUntil')">
            <UInput v-model="form.pinnedUntil" type="date" @update:model-value="clearFieldError('pinnedUntil')" />
          </UFormGroup>
          <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" label="Abbrechen" @click="closeModal" />
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
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError, errorFor } = useFormFeedback()

const notifications = ref<AppNotification[]>([])
const courses = ref<Course[]>([])
const loading = ref(true)
const showModal = ref(false)
const saving = ref(false)
const editingNotification = ref<AppNotification | null>(null)
const currentPage = ref(1)
const totalNotifications = ref(0)
const totalPages = ref(1)
const pageSize = 20

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

const showPagination = computed(() => totalNotifications.value > pageSize)
const pageStart = computed(() => (totalNotifications.value === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1))
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, totalNotifications.value))
const resultSummary = computed(() => {
  if (totalNotifications.value === 0) return '0 Mitteilungen'
  if (totalPages.value <= 1) return `${totalNotifications.value} Mitteilungen`

  return `${pageStart.value}–${pageEnd.value} von ${totalNotifications.value} Mitteilungen`
})

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
  clearFormErrors()
  showModal.value = true
}

function openEditModal(n: AppNotification) {
  editingNotification.value = n
  form.title = n.title
  form.message = n.message
  form.isGlobal = n.isGlobal
  form.courseIds = n.isGlobal ? [] : [...n.courseIds]
  form.pinnedUntil = pinnedUntilToDateInput(n.pinnedUntil)
  clearFormErrors()
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  clearFormErrors()
}

async function saveNotification() {
  clearFormErrors()
  if (!form.isGlobal && form.courseIds.length === 0) setFieldError('courseIds', 'Bitte mindestens einen Kurs auswählen.')
  if (!form.title.trim()) setFieldError('title', 'Bitte einen Titel angeben.')
  if (!form.message.trim()) setFieldError('message', 'Bitte eine Nachricht eingeben.')
  if (Object.keys(fieldErrors.value).length > 0) {
    setFormError('Bitte prüfe die markierten Felder.')
    return
  }

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
    closeModal()
    await loadNotifications()
  } catch (cause) {
    applyApiError(cause, 'Die Mitteilung konnte nicht gespeichert werden.')
  } finally {
    saving.value = false
  }
}

async function deleteNotification(n: AppNotification) {
  try {
    await api.del(`/api/admin/notifications/${n.id}`)
    toast.add({ title: 'Mitteilung gelöscht', color: 'green' })
    await loadNotifications()
  } catch (cause) {
    toast.add({ title: extractApiErrorMessage(cause, 'Die Mitteilung konnte nicht gelöscht werden.', { preferFieldSummary: false }), color: 'red' })
  }
}

async function loadNotifications(): Promise<void> {
  loading.value = true
  const params = new URLSearchParams({
    page: `${currentPage.value}`,
    limit: `${pageSize}`,
  })
  const res = await api.get<ApiListResponse<AppNotification>>(`/api/admin/notifications?${params.toString()}`)
  notifications.value = res.items
  totalNotifications.value = res.pagination?.total ?? res.items.length
  totalPages.value = res.pagination?.pages ?? 1
  loading.value = false
}

watch(currentPage, () => {
  void loadNotifications()
})

onMounted(async () => {
  const [, courseRes] = await Promise.all([
    loadNotifications(),
    api.get<ApiListResponse<Course>>('/api/admin/courses?archived=false'),
  ])
  courses.value = courseRes.items
})
</script>
