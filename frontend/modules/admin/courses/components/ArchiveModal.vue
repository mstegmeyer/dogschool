<template>
<UModal :model-value='modelValue' @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid='course-archive-modal'>
        <template #header>
            <h3 class='font-semibold text-slate-800'>
                Kurs archivieren
            </h3>
        </template>

        <div v-if='course' class='space-y-4'>
            <p class='text-sm text-slate-600'>
                Der Kurs <span class='font-medium text-slate-800'>{{ courseLabel }}</span> wird archiviert. Alle Kurstermine ab dem gewählten Datum werden entfernt, und bestehende Buchungen auf diesen Terminen bekommen ihre Credits automatisch zurück.
            </p>

            <UFormGroup label='Termine entfernen ab' :error='error'>
                <UInput
                    data-testid='archive-remove-from-date'
                    :model-value='removeFromDate'
                    type='date'
                    :min='minDate'
                    required
                    @update:model-value="emit('update:removeFromDate', $event)"
                />
            </UFormGroup>

            <div class='rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900'>
                <p class='font-medium'>
                    Bitte bestätigen:
                </p>
                <ul class='mt-2 list-disc space-y-1 pl-5'>
                    <li>Der Kurs wird auf archiviert gesetzt.</li>
                    <li>Alle zukünftigen Termine ab {{ formatDate(removeFromDate) }} werden gelöscht.</li>
                    <li>Bereits vorhandene Buchungen auf gelöschten Terminen werden automatisch erstattet.</li>
                </ul>
            </div>

            <div class='flex justify-end gap-2'>
                <UButton
                    variant='ghost'
                    label='Abbrechen'
                    :disabled='archiving'
                    @click="emit('cancel')"
                />
                <UButton
                    data-testid='confirm-course-archive'
                    color='red'
                    :loading='archiving'
                    label='Verbindlich archivieren'
                    @click="emit('confirm')"
                />
            </div>
        </div>
    </UCard>
</UModal>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Course } from '~/types';

const props = defineProps<{
    modelValue: boolean,
    course: Course | null,
    removeFromDate: string,
    minDate: string,
    error: string,
    archiving: boolean,
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
    (event: 'update:removeFromDate', value: string): void,
    (event: 'cancel'): void,
    (event: 'confirm'): void,
}>();

const { dayName, formatDate } = useHelpers();

const courseLabel = computed(() => {
    if (!props.course) {return '';}
    return `${props.course.type?.name || 'Kurs'} · ${dayName(props.course.dayOfWeek)} · ${props.course.startTime} – ${props.course.endTime}`;
});
</script>
