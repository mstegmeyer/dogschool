<template>
<UModal :model-value='modelValue' @update:model-value="emit('update:modelValue', $event)">
    <UCard v-if='selectedDate' data-testid='calendar-detail-modal'>
        <template #header>
            <div class='flex items-center justify-between'>
                <h3 class='font-semibold text-slate-800'>
                    {{ formatCourseTitleWithLevel(selectedDate.courseType?.name, selectedDate.level) }}
                </h3>
                <UBadge v-if='selectedDate.cancelled' color='red' variant='soft'>
                    Abgesagt
                </UBadge>
            </div>
        </template>

        <div class='space-y-3 text-sm'>
            <div class='flex justify-between'>
                <span class='text-slate-500'>Datum</span>
                <span class='font-medium'>{{ formatDate(selectedDate.date) }}</span>
            </div>
            <div class='flex justify-between'>
                <span class='text-slate-500'>Uhrzeit</span>
                <span class='font-medium'>{{ selectedDate.startTime }} – {{ selectedDate.endTime }}</span>
            </div>
            <div class='flex justify-between gap-4'>
                <span class='text-slate-500'>Trainer</span>
                <span class='text-right font-medium'>
                    {{ selectedDate.trainer?.fullName || 'Nicht zugewiesen' }}
                </span>
            </div>
            <div
                v-if='selectedDate.trainerOverridden && selectedDate.courseTrainer'
                class='rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900'
            >
                Standard für den Kurs: {{ selectedDate.courseTrainer.fullName }}
            </div>
            <div class='flex justify-between'>
                <span class='text-slate-500'>Buchungen</span>
                <span class='font-medium'>{{ selectedDate.bookingCount }}</span>
            </div>
            <div class='flex justify-between'>
                <span class='text-slate-500'>Abonnenten</span>
                <span class='font-medium'>{{ selectedDate.subscriberCount ?? 0 }}</span>
            </div>

            <div v-if='selectedDate.subscribers?.length' class='border-t border-slate-100 pt-3'>
                <p class='mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500'>
                    Kurs-Abonnenten
                </p>
                <ul class='max-h-32 overflow-y-auto rounded-md border border-slate-100 divide-y divide-slate-100'>
                    <li
                        v-for='subscriber in selectedDate.subscribers'
                        :key='subscriber.id'
                        class='px-3 py-2 text-sm font-medium text-slate-800'
                    >
                        {{ subscriber.name }}
                    </li>
                </ul>
            </div>

            <div class='border-t border-slate-100 pt-3'>
                <UFormGroup label='Trainer für diesen Termin'>
                    <div class='flex flex-col gap-2 sm:flex-row'>
                        <USelectMenu
                            data-testid='calendar-detail-trainer'
                            :model-value='selectedTrainerId'
                            :options='trainerOptions'
                            value-attribute='value'
                            class='flex-1'
                            @update:model-value="emit('update:selectedTrainerId', $event)"
                        />
                        <UButton
                            data-testid='save-calendar-trainer'
                            label='Trainer speichern'
                            :loading='savingTrainer'
                            :disabled="selectedTrainerId === (selectedDate.trainer?.id || '')"
                            @click="emit('save-trainer')"
                        />
                    </div>
                </UFormGroup>
            </div>

            <div class='border-t border-slate-100 pt-3'>
                <p class='mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500'>
                    Angemeldete Hunde
                </p>
                <div v-if='!selectedDate.bookings?.length' class='py-2 text-sm text-slate-400'>
                    Noch keine Buchungen für diesen Termin.
                </div>
                <ul v-else class='max-h-48 overflow-y-auto rounded-md border border-slate-100 divide-y divide-slate-100'>
                    <li
                        v-for='booking in selectedDate.bookings'
                        :key='booking.id'
                        class='flex flex-col gap-0.5 px-3 py-2'
                    >
                        <span class='font-medium text-slate-800'>{{ booking.dogName || 'Hund' }}</span>
                        <span class='text-xs text-slate-500'>Halter: {{ booking.customerName || '–' }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div v-if='!selectedDate.cancelled' class='mt-4 space-y-3 border-t border-slate-100 pt-4'>
            <div class='flex items-center gap-3'>
                <UToggle data-testid='calendar-cancel-notify' :model-value='cancelNotify' @update:model-value="emit('update:cancelNotify', $event)" />
                <span class='text-sm text-slate-600'>Mitteilung an Kursteilnehmer senden</span>
            </div>
            <template v-if='cancelNotify'>
                <UFormGroup label='Titel'>
                    <UInput
                        data-testid='calendar-cancel-title'
                        :model-value='cancelNotifyTitle'
                        placeholder='Betreff'
                        @update:model-value="emit('update:cancelNotifyTitle', $event)"
                    />
                </UFormGroup>
                <UFormGroup label='Nachricht'>
                    <UTextarea
                        data-testid='calendar-cancel-message'
                        :model-value='cancelNotifyMessage'
                        placeholder='Grund für die Absage…'
                        :rows='3'
                        @update:model-value="emit('update:cancelNotifyMessage', $event)"
                    />
                </UFormGroup>
            </template>
        </div>

        <template #footer>
            <div class='flex justify-end gap-2'>
                <UButton
                    v-if='!selectedDate.cancelled'
                    data-testid='cancel-calendar-date'
                    color='red'
                    variant='soft'
                    label='Absagen'
                    :loading='cancelling'
                    @click="emit('cancel-date')"
                />
                <UButton
                    v-else
                    data-testid='reactivate-calendar-date'
                    color='primary'
                    variant='soft'
                    label='Reaktivieren'
                    @click="emit('uncancel-date')"
                />
            </div>
        </template>
    </UCard>
</UModal>
</template>

<script setup lang="ts">
import type { CourseDate } from '~/types';

const { formatCourseTitleWithLevel, formatDate } = useHelpers();

defineProps<{
    modelValue: boolean,
    selectedDate: CourseDate | null,
    trainerOptions: Array<{ label: string; value: string }>,
    selectedTrainerId: string,
    savingTrainer: boolean,
    cancelling: boolean,
    cancelNotify: boolean,
    cancelNotifyTitle: string,
    cancelNotifyMessage: string,
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
    (event: 'update:selectedTrainerId', value: string): void,
    (event: 'update:cancelNotify', value: boolean): void,
    (event: 'update:cancelNotifyTitle', value: string): void,
    (event: 'update:cancelNotifyMessage', value: string): void,
    (event: 'save-trainer'): void,
    (event: 'cancel-date'): void,
    (event: 'uncancel-date'): void,
}>();
</script>
