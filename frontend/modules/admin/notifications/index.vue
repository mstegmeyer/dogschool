<template>
<div>
    <div class='mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'>
        <h1 class='text-2xl font-bold text-slate-800'>
            Mitteilungen
        </h1>
        <UButton
            icon='i-heroicons-plus'
            label='Neue Mitteilung'
            class='justify-center'
            @click='openCreateModal'
        />
    </div>

    <NotificationsList
        :loading='loading'
        :notifications='notifications'
        :columns='columns'
        :result-summary='resultSummary'
        :show-pagination='showPagination'
        :current-page='currentPage'
        :page-size='pageSize'
        :total-notifications='totalNotifications'
        @edit='openEditModal'
        @delete='deleteNotification'
        @update:current-page='currentPage = $event'
    />

    <NotificationFormModal
        v-model='showModal'
        :editing='editingNotification !== null'
        :form='form'
        :course-options='courseOptions'
        :field-errors='fieldErrors'
        :form-error='formError'
        :saving='saving'
        @submit='saveNotification'
        @cancel='closeModal'
        @clear-field-error='clearFieldError'
    />
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Notification as AppNotification, Course } from '~/types';
import NotificationFormModal from './components/FormModal.vue';
import NotificationsList from './components/NotificationsList.vue';

definePageMeta({ layout: 'admin' });

const api = useApi();
const toast = useToast();
const { dayName } = useHelpers();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError } = useFormFeedback();

const notifications = ref<AppNotification[]>([]);
const courses = ref<Course[]>([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const editingNotification = ref<AppNotification | null>(null);
const currentPage = ref(1);
const totalNotifications = ref(0);
const totalPages = ref(1);
const pageSize = 20;

const form = reactive({ courseIds: [] as string[], title: '', message: '', isGlobal: false, pinnedUntil: '' });

const columns = [
    { key: 'createdAt', label: 'Datum' },
    { key: 'pinnedUntil', label: 'Angepinnt' },
    { key: 'title', label: 'Titel' },
    { key: 'courses', label: 'Kurse' },
    { key: 'message', label: 'Text' },
    { key: 'authorName', label: 'Von' },
    { key: 'actions', label: '' },
];

const courseOptions = computed(() =>
    courses.value.filter(c => !c.archived).map(c => ({
        label: `${c.type?.name || c.type?.code || 'Kurs'} · ${dayName(c.dayOfWeek)} ${c.startTime}`,
        value: c.id,
    })),
);

const showPagination = computed(() => totalNotifications.value > pageSize);
const pageStart = computed(() => (totalNotifications.value === 0 ? 0 : ((currentPage.value - 1) * pageSize) + 1));
const pageEnd = computed(() => Math.min(currentPage.value * pageSize, totalNotifications.value));
const resultSummary = computed(() => {
    if (totalNotifications.value === 0) {return '0 Mitteilungen';}
    if (totalPages.value <= 1) {return `${totalNotifications.value} Mitteilungen`;}

    return `${pageStart.value}–${pageEnd.value} von ${totalNotifications.value} Mitteilungen`;
});

function pinnedUntilToDateInput(iso: string | null): string {
    if (!iso) {return '';}
    return iso.slice(0, 10);
}

function openCreateModal(): void {
    editingNotification.value = null;
    form.courseIds = [];
    form.title = '';
    form.message = '';
    form.isGlobal = false;
    form.pinnedUntil = '';
    clearFormErrors();
    showModal.value = true;
}

function openEditModal(n: AppNotification): void {
    editingNotification.value = n;
    form.title = n.title;
    form.message = n.message;
    form.isGlobal = n.isGlobal;
    form.courseIds = n.isGlobal ? [] : [...n.courseIds];
    form.pinnedUntil = pinnedUntilToDateInput(n.pinnedUntil);
    clearFormErrors();
    showModal.value = true;
}

function closeModal(): void {
    showModal.value = false;
    clearFormErrors();
}

async function saveNotification(): Promise<void> {
    clearFormErrors();
    if (!form.isGlobal && form.courseIds.length === 0) {setFieldError('courseIds', 'Bitte mindestens einen Kurs auswählen.');}
    if (!form.title.trim()) {setFieldError('title', 'Bitte einen Titel angeben.');}
    if (!form.message.trim()) {setFieldError('message', 'Bitte eine Nachricht eingeben.');}
    if (Object.keys(fieldErrors.value).length > 0) {
        setFormError('Bitte prüfe die markierten Felder.');
        return;
    }

    saving.value = true;
    try {
        const courseIds = form.isGlobal ? [] : form.courseIds;
        const pinnedUntil = form.pinnedUntil ? `${form.pinnedUntil}T23:59:59` : '';
        if (editingNotification.value) {
            await api.put(`/api/admin/notifications/${editingNotification.value.id}`, {
                title: form.title,
                message: form.message,
                courseIds,
                pinnedUntil,
            });
            toast.add({ title: 'Mitteilung aktualisiert', color: 'green' });
        } else {
            await api.post('/api/admin/notifications', {
                title: form.title,
                message: form.message,
                courseIds,
                pinnedUntil: pinnedUntil || null,
            });
            toast.add({ title: 'Mitteilung erstellt', color: 'green' });
        }
        closeModal();
        await loadNotifications();
    } catch (cause) {
        applyApiError(cause, 'Die Mitteilung konnte nicht gespeichert werden.');
    } finally {
        saving.value = false;
    }
}

async function deleteNotification(n: AppNotification): Promise<void> {
    try {
        await api.del(`/api/admin/notifications/${n.id}`);
        toast.add({ title: 'Mitteilung gelöscht', color: 'green' });
        await loadNotifications();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Die Mitteilung konnte nicht gelöscht werden.', { preferFieldSummary: false }), color: 'red' });
    }
}

async function loadNotifications(): Promise<void> {
    loading.value = true;
    const params = new URLSearchParams({
        page: `${currentPage.value}`,
        limit: `${pageSize}`,
    });
    const res = await api.get<ApiListResponse<AppNotification>>(`/api/admin/notifications?${params.toString()}`);
    notifications.value = res.items;
    totalNotifications.value = res.pagination?.total ?? res.items.length;
    totalPages.value = res.pagination?.pages ?? 1;
    loading.value = false;
}

watch(currentPage, () => {
    void loadNotifications();
});

onMounted(async () => {
    const [, courseRes] = await Promise.all([
        loadNotifications(),
        api.get<ApiListResponse<Course>>('/api/admin/courses?archived=false'),
    ]);
    courses.value = courseRes.items;
});
</script>
