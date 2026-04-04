<template>
<UModal :model-value='modelValue' @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid='calendar-subscription-modal'>
        <template #header>
            <div class='flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between'>
                <div>
                    <h2 class='text-lg font-semibold text-slate-800'>
                        Kalender abonnieren
                    </h2>
                    <p class='text-sm text-slate-500'>
                        Mit diesem Link kannst du deine gebuchten Kurse in deiner Kalender-App als Abo einbinden.
                    </p>
                </div>

                <UBadge color='blue' variant='soft' size='sm'>
                    ICS Feed
                </UBadge>
            </div>
        </template>

        <div class='space-y-4'>
            <UAlert
                color='amber'
                variant='soft'
                icon='i-heroicons-lock-closed'
                title='Privater Link'
                description='Wenn du ihn teilst, können andere deine gebuchten Kurse sehen.'
            />

            <div class='flex flex-col gap-3'>
                <UInput
                    data-testid='calendar-subscription-url'
                    :model-value='calendarSubscriptionUrl'
                    readonly
                    class='flex-1'
                    placeholder='Kalender-Link wird geladen ...'
                />
                <div class='flex flex-wrap gap-2'>
                    <UButton
                        data-testid='copy-calendar-url'
                        label='Kopieren'
                        icon='i-heroicons-clipboard-document'
                        :disabled='!calendarSubscriptionUrl'
                        @click="emit('copy')"
                    />
                    <UButton
                        data-testid='open-calendar-url'
                        label='Öffnen'
                        icon='i-heroicons-arrow-top-right-on-square'
                        variant='soft'
                        :disabled='!calendarSubscriptionWebcalUrl'
                        @click="emit('open')"
                    />
                </div>
            </div>

            <p class='text-xs text-slate-500'>
                Änderungen an Buchungen und Kursabsagen erscheinen, sobald deine Kalender-App das Abo aktualisiert.
            </p>
        </div>

        <template #footer>
            <div class='flex justify-end'>
                <UButton
                    label='Schließen'
                    color='gray'
                    variant='ghost'
                    @click="emit('update:modelValue', false)"
                />
            </div>
        </template>
    </UCard>
</UModal>
</template>

<script setup lang="ts">
defineProps<{
    modelValue: boolean,
    calendarSubscriptionUrl: string,
    calendarSubscriptionWebcalUrl: string,
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
    (event: 'copy'): void,
    (event: 'open'): void,
}>();
</script>
