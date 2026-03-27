<template>
  <div class="w-full min-w-0 overflow-hidden">
    <UCard class="auth-card">
      <template #header>
        <h2 class="text-lg font-semibold text-slate-800">Registrieren</h2>
      </template>

      <form class="space-y-4 min-w-0" @submit.prevent="handleRegister">
        <UFormGroup label="Name">
          <UInput v-model="form.name" placeholder="Max Mustermann" required />
        </UFormGroup>

        <UFormGroup label="E-Mail">
          <UInput v-model="form.email" type="email" placeholder="name@beispiel.de" required />
        </UFormGroup>

        <UFormGroup label="Passwort">
          <UInput v-model="form.password" type="password" placeholder="Mindestens 6 Zeichen" required />
        </UFormGroup>

        <UFormGroup label="Passwort bestätigen">
          <UInput v-model="confirmPassword" type="password" placeholder="Passwort wiederholen" required />
        </UFormGroup>

        <UAlert v-if="error" color="red" variant="soft" :title="error" icon="i-heroicons-exclamation-triangle" />

        <UButton type="submit" block :loading="loading" size="lg">
          Konto erstellen
        </UButton>
      </form>

      <template #footer>
        <p class="text-center text-sm text-slate-500">
          Bereits registriert?
          <NuxtLink to="/login" class="text-komm-600 font-medium hover:underline">
            Anmelden
          </NuxtLink>
        </p>
      </template>
    </UCard>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'auth' })

const { register } = useAuth()

const form = reactive({ name: '', email: '', password: '' })
const confirmPassword = ref('')
const error = ref('')
const loading = ref(false)

async function handleRegister() {
  if (form.password !== confirmPassword.value) {
    error.value = 'Passwörter stimmen nicht überein.'
    return
  }
  loading.value = true
  error.value = ''
  try {
    await register(form)
    navigateTo('/customer')
  } catch {
    error.value = 'Registrierung fehlgeschlagen. Möglicherweise ist die E-Mail bereits vergeben.'
  } finally {
    loading.value = false
  }
}
</script>
