<template>
<UModal :model-value='modelValue' @update:model-value="emit('update:modelValue', $event)">
    <UCard v-if='courseDate'>
        <template #header>
            <div class='flex items-start justify-between gap-3'>
                <div class='min-w-0'>
                    <h2 class='text-lg font-semibold text-slate-800'>
                        {{ formatCourseTitleWithLevel(courseDate.courseType?.name, courseDate.level) }}
                    </h2>
                    <p class='mt-1 text-sm text-slate-500'>
                        {{ formatDate(courseDate.date) }} · {{ courseDate.startTime }} – {{ courseDate.endTime }}
                    </p>
                </div>
                <UBadge color='primary' variant='soft' size='sm'>
                    Buchung
                </UBadge>
            </div>
        </template>

        <div class='space-y-4' data-testid='booking-modal'>
            <div class='rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600'>
                <p>Trainer: {{ courseDate.trainer?.fullName || 'Wird noch zugewiesen' }}</p>
            </div>

            <div v-if='dogs.length === 0' class='rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900'>
                Für die Buchung ist zuerst ein Hund erforderlich.
            </div>
            <div v-else-if='dogs.length === 1' class='rounded-lg border border-komm-200 bg-komm-50 px-3 py-2'>
                <p class='text-xs font-semibold uppercase tracking-wide text-komm-700'>
                    Hund
                </p>
                <p class='mt-1 text-sm font-medium text-komm-900'>
                    {{ dogs[0]?.name }}
                </p>
            </div>
            <UFormGroup v-else label='Hund auswählen'>
                <select
                    :value='bookingDogId'
                    data-testid='booking-dog-select'
                    class='block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm outline-none transition focus:border-komm-400 focus:ring-2 focus:ring-komm-200'
                    @change="emit('update:bookingDogId', ($event.target as HTMLSelectElement).value)"
                >
                    <option value=''>
                        Hund wählen
                    </option>
                    <option v-for='dog in dogs' :key='dog.id' :value='dog.id'>
                        {{ dog.name }}
                    </option>
                </select>
            </UFormGroup>
        </div>

        <template #footer>
            <div class='flex justify-end gap-2'>
                <UButton
                    label='Abbrechen'
                    color='gray'
                    variant='ghost'
                    :disabled='bookingInFlight'
                    @click="emit('cancel')"
                />
                <UButton
                    label='Buchen'
                    :loading='bookingInFlight'
                    :disabled='!selectedDogId'
                    data-testid='confirm-booking'
                    @click="emit('confirm')"
                />
            </div>
        </template>
    </UCard>
</UModal>
</template>

<script setup lang="ts">
import type { CourseDate, Dog } from '~/types';

const { formatCourseTitleWithLevel, formatDate } = useHelpers();

defineProps<{
    modelValue: boolean,
    courseDate: CourseDate | null,
    dogs: Dog[],
    bookingDogId: string,
    selectedDogId: string,
    bookingInFlight: boolean,
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
    (event: 'update:bookingDogId', value: string): void,
    (event: 'cancel'): void,
    (event: 'confirm'): void,
}>();
</script>
