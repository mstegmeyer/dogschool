<template>
  <div>
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Mein Profil</h1>

    <UCard>
        <form class="space-y-4" @submit.prevent="saveProfile">
          <UFormGroup label="Name">
            <UInput v-model="form.name" />
          </UFormGroup>
          <UFormGroup label="E-Mail">
            <UInput v-model="form.email" type="email" />
          </UFormGroup>

          <UDivider label="Adresse" />

          <div class="grid grid-cols-2 gap-4">
            <UFormGroup label="Straße" class="col-span-2">
              <UInput v-model="form.address.street" />
            </UFormGroup>
            <UFormGroup label="PLZ">
              <UInput v-model="form.address.postalCode" />
            </UFormGroup>
            <UFormGroup label="Ort">
              <UInput v-model="form.address.city" />
            </UFormGroup>
          </div>

          <UDivider label="Bankverbindung" />

          <div class="grid grid-cols-2 gap-4">
            <UFormGroup label="IBAN" class="col-span-2">
              <UInput v-model="form.bankAccount.iban" />
            </UFormGroup>
            <UFormGroup label="BIC">
              <UInput v-model="form.bankAccount.bic" />
            </UFormGroup>
            <UFormGroup label="Kontoinhaber">
              <UInput v-model="form.bankAccount.accountHolder" />
            </UFormGroup>
          </div>

          <UDivider label="Passwort ändern" />

          <UFormGroup label="Neues Passwort (optional)">
            <UInput v-model="form.password" type="password" placeholder="Leer lassen für keine Änderung" />
          </UFormGroup>

          <UButton type="submit" :loading="saving" label="Speichern" />
        </form>
      </UCard>
  </div>
</template>

<script setup lang="ts">
import type { ProfileUpdatePayload } from '~/types'

definePageMeta({ layout: 'customer' })

const { user, fetchProfile } = useAuth()
const api = useApi()
const toast = useToast()

const saving = ref(false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  address: { street: '', postalCode: '', city: '' },
  bankAccount: { iban: '', bic: '', accountHolder: '' },
})

async function saveProfile(): Promise<void> {
  saving.value = true
  try {
    const payload: ProfileUpdatePayload = {
      name: form.name || undefined,
      email: form.email || undefined,
      address: form.address,
      bankAccount: form.bankAccount,
    }
    if (form.password) payload.password = form.password
    await api.put('/api/customer/me', payload)
    await fetchProfile()
    toast.add({ title: 'Profil gespeichert', color: 'green' })
  } catch {
    toast.add({ title: 'Fehler beim Speichern', color: 'red' })
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await fetchProfile()
  if (user.value) {
    form.name = user.value.name || ''
    form.email = user.value.email || ''
    form.address.street = user.value.address?.street || ''
    form.address.postalCode = user.value.address?.postalCode || ''
    form.address.city = user.value.address?.city || ''
    form.bankAccount.iban = user.value.bankAccount?.iban || ''
    form.bankAccount.bic = user.value.bankAccount?.bic || ''
    form.bankAccount.accountHolder = user.value.bankAccount?.accountHolder || ''
  }
})
</script>
