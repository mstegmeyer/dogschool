<template>
<UCard v-if='loading'>
    <div class='space-y-4'>
        <div v-for='index in 5' :key='index' class='space-y-2'>
            <USkeleton class='h-4 w-24 rounded-md' />
            <USkeleton class='h-10 w-full rounded-md' />
        </div>
        <USkeleton class='h-10 w-28 rounded-md' />
    </div>
</UCard>
<UCard v-else data-testid='profile-form-card'>
    <form class='space-y-4' @submit.prevent="emit('submit')">
        <UFormGroup label='Name' :error='fieldErrors.name'>
            <UInput v-model='form.name' @update:model-value="emit('clear-field-error', 'name')" />
        </UFormGroup>
        <UFormGroup label='E-Mail' :error='fieldErrors.email'>
            <UInput v-model='form.email' type='email' @update:model-value="emit('clear-field-error', 'email')" />
        </UFormGroup>

        <UDivider label='Adresse' />

        <div class='grid grid-cols-2 gap-4'>
            <UFormGroup label='Straße' class='col-span-2' :error="fieldErrors['address.street']">
                <UInput v-model='form.address.street' @update:model-value="emit('clear-field-error', 'address.street')" />
            </UFormGroup>
            <UFormGroup label='PLZ' :error="fieldErrors['address.postalCode']">
                <UInput v-model='form.address.postalCode' @update:model-value="emit('clear-field-error', 'address.postalCode')" />
            </UFormGroup>
            <UFormGroup label='Ort' :error="fieldErrors['address.city']">
                <UInput v-model='form.address.city' @update:model-value="emit('clear-field-error', 'address.city')" />
            </UFormGroup>
        </div>

        <UDivider label='Bankverbindung' />

        <div class='grid grid-cols-2 gap-4'>
            <UFormGroup label='IBAN' class='col-span-2' :error="fieldErrors['bankAccount.iban']">
                <UInput v-model='form.bankAccount.iban' @update:model-value="emit('clear-field-error', 'bankAccount.iban')" />
            </UFormGroup>
            <UFormGroup label='BIC' :error="fieldErrors['bankAccount.bic']">
                <UInput v-model='form.bankAccount.bic' @update:model-value="emit('clear-field-error', 'bankAccount.bic')" />
            </UFormGroup>
            <UFormGroup label='Kontoinhaber' :error="fieldErrors['bankAccount.accountHolder']">
                <UInput v-model='form.bankAccount.accountHolder' @update:model-value="emit('clear-field-error', 'bankAccount.accountHolder')" />
            </UFormGroup>
        </div>

        <UDivider label='Passwort ändern' />

        <UFormGroup label='Neues Passwort (optional)' :error='fieldErrors.password'>
            <UInput
                v-model='form.password'
                type='password'
                placeholder='Leer lassen für keine Änderung'
                @update:model-value="emit('clear-field-error', 'password')"
            />
        </UFormGroup>

        <UAlert
            v-if='formError'
            color='red'
            variant='soft'
            :title='formError'
            icon='i-heroicons-exclamation-triangle'
        />
        <UButton
            data-testid='save-profile'
            type='submit'
            :loading='saving'
            label='Speichern'
        />
    </form>
</UCard>
</template>

<script setup lang="ts">
defineProps<{
    loading: boolean,
    form: {
        name: string,
        email: string,
        password: string,
        address: { street: string; postalCode: string; city: string },
        bankAccount: { iban: string; bic: string; accountHolder: string },
    },
    fieldErrors: Record<string, string>,
    formError: string,
    saving: boolean,
}>();

const emit = defineEmits<{
    (event: 'submit'): void,
    (event: 'clear-field-error', value: string): void,
}>();
</script>
