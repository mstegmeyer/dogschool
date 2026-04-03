<template>
  <div class="mx-auto w-full max-w-md min-w-0 overflow-hidden">
    <UCard class="auth-card">
      <template #header>
        <h2 class="text-lg font-semibold text-slate-800">Anmelden</h2>
      </template>

      <UTabs :items="tabs" @change="onTabChange">
        <template #item="{ item }">
          <form class="space-y-4 pt-4 min-w-0" @submit.prevent="handleLogin">
            <UFormGroup v-if="item.key === 'customer'" label="E-Mail" :error="errorFor('email')">
              <UInput
                v-model="email"
                type="email"
                placeholder="name@beispiel.de"
                autocomplete="email"
                autocapitalize="none"
                autocorrect="off"
                spellcheck="false"
                required
                @update:model-value="clearFieldError('email')"
              />
            </UFormGroup>

            <UFormGroup v-if="item.key === 'admin'" label="Benutzername" :error="errorFor('username')">
              <UInput
                v-model="username"
                placeholder="florian"
                autocomplete="username"
                autocapitalize="none"
                autocorrect="off"
                spellcheck="false"
                required
                @update:model-value="clearFieldError('username')"
              />
            </UFormGroup>

            <UFormGroup label="Passwort" :error="errorFor('password')">
              <UInput v-model="password" type="password" placeholder="••••••••" required @update:model-value="clearFieldError('password')" />
            </UFormGroup>

            <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />

            <UButton type="submit" block :loading="loading" size="lg">
              Anmelden
            </UButton>
          </form>
        </template>
      </UTabs>

      <template #footer>
        <p class="text-center text-sm text-slate-500">
          Noch kein Konto?
          <NuxtLink to="/register" class="text-komm-600 font-medium hover:underline">
            Registrieren
          </NuxtLink>
        </p>
      </template>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import type { FetchError } from 'ofetch'

definePageMeta({ layout: 'auth' })

const { loginAdmin, loginCustomer } = useAuth()

const tabs = [
  { key: 'customer', label: 'Kunde' },
  { key: 'admin', label: 'Trainer' },
]

const activeTab = ref('customer')
const email = ref('')
const username = ref('')
const password = ref('')
const loading = ref(false)
const { formError, clearFormErrors, clearFieldError, setFieldError, setFormError, errorFor } = useFormFeedback()

function onTabChange(index: number) {
  activeTab.value = tabs[index].key
  clearFormErrors()
}

function resolveLoginError(cause: unknown): string {
  const fetchError = cause as FetchError<{ message?: string }>
  const status = fetchError?.statusCode ?? fetchError?.response?.status

  if (status === 401) {
    return 'Anmeldung fehlgeschlagen. Bitte prüfe deine Zugangsdaten.'
  }

  if (status && status >= 500) {
    return 'Der Server ist gerade nicht erreichbar. Bitte versuche es gleich noch einmal.'
  }

  if (fetchError?.message?.includes('Failed to fetch') || fetchError?.message?.includes('NetworkError')) {
    return 'Die App konnte den Server nicht erreichen. Bitte prüfe die Verbindung.'
  }

  return 'Anmeldung fehlgeschlagen. Bitte versuche es noch einmal.'
}

async function handleLogin() {
  clearFormErrors()
  if (activeTab.value === 'admin') {
    if (!username.value.trim()) setFieldError('username', 'Bitte einen Benutzernamen angeben.')
  } else {
    if (!email.value.trim()) setFieldError('email', 'Bitte eine E-Mail-Adresse angeben.')
  }
  if (!password.value) setFieldError('password', 'Bitte ein Passwort angeben.')
  if (errorFor('email') || errorFor('username') || errorFor('password')) {
    setFormError('Bitte prüfe die markierten Felder.')
    return
  }

  loading.value = true
  try {
    if (activeTab.value === 'admin') {
      await loginAdmin(username.value, password.value)
      navigateTo('/admin')
    } else {
      await loginCustomer(email.value, password.value)
      navigateTo('/customer')
    }
  } catch (cause) {
    setFormError(resolveLoginError(cause))
  } finally {
    loading.value = false
  }
}
</script>
