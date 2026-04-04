<template>
<div class='mx-auto w-full max-w-md min-w-0 overflow-hidden'>
    <UCard class='auth-card'>
        <template #header>
            <h2 class='text-lg font-semibold text-slate-800'>
                Registrieren
            </h2>
        </template>

        <form class='space-y-4 min-w-0' @submit.prevent='handleRegister'>
            <UFormGroup label='Name' :error="errorFor('name')">
                <UInput
                    v-model='form.name'
                    placeholder='Max Mustermann'
                    required
                    @update:model-value="clearFieldError('name')"
                />
            </UFormGroup>

            <UFormGroup label='E-Mail' :error="errorFor('email')">
                <UInput
                    v-model='form.email'
                    type='email'
                    placeholder='name@beispiel.de'
                    required
                    @update:model-value="clearFieldError('email')"
                />
            </UFormGroup>

            <UFormGroup label='Passwort' :error="errorFor('password')">
                <UInput
                    v-model='form.password'
                    type='password'
                    placeholder='Mindestens 6 Zeichen'
                    required
                    @update:model-value="clearFieldError('password')"
                />
            </UFormGroup>

            <UFormGroup label='Passwort bestätigen' :error="errorFor('confirmPassword')">
                <UInput
                    v-model='confirmPassword'
                    type='password'
                    placeholder='Passwort wiederholen'
                    required
                    @update:model-value="clearFieldError('confirmPassword')"
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
                type='submit'
                block
                :loading='loading'
                size='lg'
            >
                Konto erstellen
            </UButton>
        </form>

        <template #footer>
            <p class='text-center text-sm text-slate-500'>
                Bereits registriert?
                <NuxtLink to='/login' class='text-komm-600 font-medium hover:underline'>
                    Anmelden
                </NuxtLink>
            </p>
        </template>
    </UCard>
</div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'auth' });

const { register } = useAuth();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError, errorFor } = useFormFeedback();

const form = reactive({ name: '', email: '', password: '' });
const confirmPassword = ref('');
const loading = ref(false);

async function handleRegister() {
    clearFormErrors();

    if (!form.name.trim()) {setFieldError('name', 'Bitte einen Namen angeben.');}
    if (!form.email.trim()) {setFieldError('email', 'Bitte eine E-Mail-Adresse angeben.');}
    if (!form.password) {setFieldError('password', 'Bitte ein Passwort angeben.');}
    if (!confirmPassword.value) {setFieldError('confirmPassword', 'Bitte das Passwort bestätigen.');}

    if (form.password !== confirmPassword.value) {
        setFieldError('confirmPassword', 'Passwörter stimmen nicht überein.');
    }

    if (Object.keys(fieldErrors.value).length > 0) {
        setFormError('Bitte prüfe die markierten Felder.');
        return;
    }

    loading.value = true;
    try {
        await register(form);
        navigateTo('/customer');
    } catch (cause) {
        applyApiError(cause, 'Registrierung fehlgeschlagen. Bitte versuche es noch einmal.');
    } finally {
        loading.value = false;
    }
}
</script>
