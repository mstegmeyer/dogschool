<template>
<div>
    <div class='mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'>
        <h1 class='text-2xl font-bold text-slate-800'>
            Kursarten
        </h1>
        <UButton
            icon='i-heroicons-plus'
            label='Neue Kursart'
            class='justify-center'
            @click='openCreateModal'
        />
    </div>

    <CourseTypesList
        :loading='loading'
        :course-types='courseTypes'
        :columns='columns'
        @edit='openEditModal'
        @delete='deleteCourseType'
    />

    <CourseTypeFormModal
        v-model='showModal'
        :editing='editingCourseType !== null'
        :form='form'
        :recurrence-options='recurrenceOptions'
        :field-errors='fieldErrors'
        :form-error='formError'
        :saving='saving'
        @submit='saveCourseType'
        @cancel='closeModal'
        @clear-field-error='clearFieldError'
    />
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, CourseType } from '~/types';
import CourseTypeFormModal from './components/FormModal.vue';
import CourseTypesList from './components/List.vue';

definePageMeta({ layout: 'admin' });

const api = useApi();
const toast = useToast();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError, errorFor } = useFormFeedback();

const courseTypes = ref<CourseType[]>([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const editingCourseType = ref<CourseType | null>(null);

const form = reactive({ code: '', name: '', recurrenceKind: 'RECURRING' });

const columns = [
    { key: 'code', label: 'Kürzel' },
    { key: 'name', label: 'Name' },
    { key: 'recurrenceKind', label: 'Wiederholungsart' },
    { key: 'actions', label: '' },
];

const recurrenceOptions = [
    { label: 'Wiederkehrend', value: 'RECURRING' },
    { label: 'Einmalig', value: 'ONE_TIME' },
    { label: 'Drop-In', value: 'DROP_IN' },
];

function openCreateModal() {
    editingCourseType.value = null;
    form.code = '';
    form.name = '';
    form.recurrenceKind = 'RECURRING';
    clearFormErrors();
    showModal.value = true;
}

function openEditModal(ct: CourseType) {
    editingCourseType.value = ct;
    form.code = ct.code;
    form.name = ct.name;
    form.recurrenceKind = ct.recurrenceKind;
    clearFormErrors();
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    clearFormErrors();
}

async function saveCourseType() {
    clearFormErrors();
    if (!form.code.trim()) {setFieldError('code', 'Bitte ein Kürzel angeben.');}
    if (!form.name.trim()) {setFieldError('name', 'Bitte einen Namen angeben.');}
    if (!form.recurrenceKind) {setFieldError('recurrenceKind', 'Bitte eine Wiederholungsart wählen.');}
    if (errorFor('code') || errorFor('name') || errorFor('recurrenceKind')) {
        setFormError('Bitte prüfe die markierten Felder.');
        return;
    }

    saving.value = true;
    try {
        const payload = { code: form.code, name: form.name, recurrenceKind: form.recurrenceKind };
        if (editingCourseType.value) {
            await api.put(`/api/admin/course-types/${editingCourseType.value.id}`, payload);
            toast.add({ title: 'Kursart aktualisiert', color: 'green' });
        } else {
            await api.post('/api/admin/course-types', payload);
            toast.add({ title: 'Kursart erstellt', color: 'green' });
        }
        closeModal();
        await loadCourseTypes();
    } catch (cause) {
        applyApiError(cause, 'Die Kursart konnte nicht gespeichert werden.');
    } finally {
        saving.value = false;
    }
}

async function deleteCourseType(ct: CourseType) {
    try {
        await api.del(`/api/admin/course-types/${ct.id}`);
        toast.add({ title: 'Kursart gelöscht', color: 'green' });
        await loadCourseTypes();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Die Kursart konnte nicht gelöscht werden.', { preferFieldSummary: false }), color: 'red' });
    }
}

async function loadCourseTypes(): Promise<void> {
    loading.value = true;
    const res = await api.get<ApiListResponse<CourseType>>('/api/admin/course-types');
    courseTypes.value = res.items;
    loading.value = false;
}

onMounted(() => loadCourseTypes());
</script>
