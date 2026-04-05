<template>
<div>
    <div class='mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'>
        <div>
            <h1 class='text-2xl font-bold text-slate-800'>
                Räume
            </h1>
            <p class='mt-1 text-sm text-slate-500'>
                Räume werden bei der Bestätigung von Hotelbuchungen zugewiesen.
            </p>
        </div>
        <UButton
            data-testid='open-room-create'
            icon='i-heroicons-plus'
            label='Raum anlegen'
            @click='openCreateModal'
        />
    </div>

    <RoomList :loading='loading' :rooms='rooms' @edit='openEditModal' />

    <RoomFormModal
        v-model='showModal'
        :editing='editingRoom !== null'
        :form='form'
        :field-errors='fieldErrors'
        :form-error='formError'
        :saving='saving'
        @submit='saveRoom'
        @cancel='closeModal'
        @clear-field-error='clearFieldError'
    />
</div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Room } from '~/types';
import RoomFormModal from './components/FormModal.vue';
import RoomList from './components/List.vue';

definePageMeta({ layout: 'admin' });

const api = useApi();
const toast = useToast();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError } = useFormFeedback();

const rooms = ref<Room[]>([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingRoom = ref<Room | null>(null);

const form = reactive({
    name: '',
    squareMeters: 0,
});

function resetForm(): void {
    form.name = '';
    form.squareMeters = 0;
}

function openCreateModal(): void {
    editingRoom.value = null;
    resetForm();
    clearFormErrors();
    showModal.value = true;
}

function openEditModal(room: Room): void {
    editingRoom.value = room;
    form.name = room.name;
    form.squareMeters = room.squareMeters;
    clearFormErrors();
    showModal.value = true;
}

function closeModal(): void {
    showModal.value = false;
    editingRoom.value = null;
    clearFormErrors();
}

function validateForm(): boolean {
    clearFormErrors();
    if (!form.name.trim()) {
        setFieldError('name', 'Bitte einen Namen angeben.');
    }
    if (!form.squareMeters || form.squareMeters <= 0) {
        setFieldError('squareMeters', 'Bitte eine gültige Raumgröße angeben.');
    }
    if (Object.keys(fieldErrors.value).length > 0) {
        setFormError('Bitte prüfe die markierten Felder.');
        return false;
    }

    return true;
}

async function loadRooms(): Promise<void> {
    loading.value = true;
    try {
        const response = await api.get<ApiListResponse<Room>>('/api/admin/hotel/rooms');
        rooms.value = response.items;
    } finally {
        loading.value = false;
    }
}

async function saveRoom(): Promise<void> {
    if (!validateForm()) {
        return;
    }

    saving.value = true;
    try {
        if (editingRoom.value) {
            await api.put(`/api/admin/hotel/rooms/${editingRoom.value.id}`, {
                name: form.name,
                squareMeters: form.squareMeters,
            });
            toast.add({ title: 'Raum aktualisiert', color: 'green' });
        } else {
            await api.post('/api/admin/hotel/rooms', {
                name: form.name,
                squareMeters: form.squareMeters,
            });
            toast.add({ title: 'Raum angelegt', color: 'green' });
        }
        closeModal();
        resetForm();
        await loadRooms();
    } catch (cause) {
        applyApiError(cause, 'Der Raum konnte nicht gespeichert werden.');
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    void loadRooms();
});
</script>
