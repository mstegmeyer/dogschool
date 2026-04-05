<template>
<UModal :model-value='modelValue' @update:model-value="emit('update:modelValue', $event)">
    <UCard data-testid='room-form-modal'>
        <template #header>
            <h3 class='font-semibold text-slate-800'>
                {{ editing ? 'Raum bearbeiten' : 'Raum anlegen' }}
            </h3>
        </template>
        <form class='space-y-4' @submit.prevent="emit('submit')">
            <UFormGroup label='Name' :error='fieldErrors.name'>
                <UInput
                    v-model='form.name'
                    data-testid='room-name'
                    placeholder='z.B. Waldzimmer'
                    @update:model-value="emit('clear-field-error', 'name')"
                />
            </UFormGroup>
            <UFormGroup label='Quadratmeter' :error='fieldErrors.squareMeters'>
                <UInput
                    v-model.number='form.squareMeters'
                    data-testid='room-square-meters'
                    type='number'
                    min='1'
                    placeholder='z.B. 12'
                    @update:model-value="emit('clear-field-error', 'squareMeters')"
                />
            </UFormGroup>
            <UAlert
                v-if='formError'
                color='red'
                variant='soft'
                :title='formError'
                icon='i-heroicons-exclamation-triangle'
            />
            <div class='flex justify-end gap-2'>
                <UButton variant='ghost' label='Abbrechen' @click="emit('cancel')" />
                <UButton type='submit' :loading='saving' :label='editing ? "Speichern" : "Anlegen"' />
            </div>
        </form>
    </UCard>
</UModal>
</template>

<script setup lang="ts">
defineProps<{
    modelValue: boolean,
    editing: boolean,
    form: { name: string; squareMeters: number },
    fieldErrors: Record<string, string>,
    formError: string,
    saving: boolean,
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void,
    (event: 'submit'): void,
    (event: 'cancel'): void,
    (event: 'clear-field-error', value: string): void,
}>();
</script>
