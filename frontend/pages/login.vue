<template>
  <UCard>
    <template #header>
      <h2 class="text-lg font-semibold text-slate-800">Anmelden</h2>
    </template>

    <UTabs :items="tabs" @change="onTabChange">
      <template #item="{ item }">
        <form class="space-y-4 pt-4" @submit.prevent="handleLogin">
          <UFormGroup v-if="item.key === 'customer'" label="E-Mail">
            <UInput v-model="email" type="email" placeholder="name@beispiel.de" required />
          </UFormGroup>

          <UFormGroup v-if="item.key === 'admin'" label="Benutzername">
            <UInput v-model="username" placeholder="florian" required />
          </UFormGroup>

          <UFormGroup label="Passwort">
            <UInput v-model="password" type="password" placeholder="••••••••" required />
          </UFormGroup>

          <UAlert v-if="error" color="red" variant="soft" :title="error" icon="i-heroicons-exclamation-triangle" />

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
</template>

<script setup lang="ts">
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
const error = ref('')
const loading = ref(false)

function onTabChange(index: number) {
  activeTab.value = tabs[index].key
  error.value = ''
}

async function handleLogin() {
  loading.value = true
  error.value = ''
  try {
    if (activeTab.value === 'admin') {
      await loginAdmin(username.value, password.value)
      navigateTo('/admin')
    } else {
      await loginCustomer(email.value, password.value)
      navigateTo('/customer')
    }
  } catch {
    error.value = 'Anmeldung fehlgeschlagen. Bitte prüfe deine Zugangsdaten.'
  } finally {
    loading.value = false
  }
}
</script>
