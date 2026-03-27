<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-slate-800 mb-6">Mein Profil</h1>

    <UCard v-if="loading">
      <div class="space-y-4">
        <div v-for="index in 5" :key="index" class="space-y-2">
          <USkeleton class="h-4 w-24 rounded-md" />
          <USkeleton class="h-10 w-full rounded-md" />
        </div>
        <USkeleton class="h-10 w-28 rounded-md" />
      </div>
    </UCard>
    <UCard v-else>
      <form class="space-y-4" @submit.prevent="saveProfile">
        <UFormGroup label="Name" :error="errorFor('name')">
          <UInput v-model="form.name" @update:model-value="clearFieldError('name')" />
        </UFormGroup>
        <UFormGroup label="E-Mail" :error="errorFor('email')">
          <UInput v-model="form.email" type="email" @update:model-value="clearFieldError('email')" />
        </UFormGroup>

        <UDivider label="Adresse" />

        <div class="grid grid-cols-2 gap-4">
          <UFormGroup label="Straße" class="col-span-2" :error="errorFor('address.street')">
            <UInput v-model="form.address.street" @update:model-value="clearFieldError('address.street')" />
          </UFormGroup>
          <UFormGroup label="PLZ" :error="errorFor('address.postalCode')">
            <UInput v-model="form.address.postalCode" @update:model-value="clearFieldError('address.postalCode')" />
          </UFormGroup>
          <UFormGroup label="Ort" :error="errorFor('address.city')">
            <UInput v-model="form.address.city" @update:model-value="clearFieldError('address.city')" />
          </UFormGroup>
        </div>

        <UDivider label="Bankverbindung" />

        <div class="grid grid-cols-2 gap-4">
          <UFormGroup label="IBAN" class="col-span-2" :error="errorFor('bankAccount.iban')">
            <UInput v-model="form.bankAccount.iban" @update:model-value="clearFieldError('bankAccount.iban')" />
          </UFormGroup>
          <UFormGroup label="BIC" :error="errorFor('bankAccount.bic')">
            <UInput v-model="form.bankAccount.bic" @update:model-value="clearFieldError('bankAccount.bic')" />
          </UFormGroup>
          <UFormGroup label="Kontoinhaber" :error="errorFor('bankAccount.accountHolder')">
            <UInput v-model="form.bankAccount.accountHolder" @update:model-value="clearFieldError('bankAccount.accountHolder')" />
          </UFormGroup>
        </div>

        <UDivider label="Passwort ändern" />

        <UFormGroup label="Neues Passwort (optional)" :error="errorFor('password')">
          <UInput v-model="form.password" type="password" placeholder="Leer lassen für keine Änderung" @update:model-value="clearFieldError('password')" />
        </UFormGroup>

        <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />
        <UButton type="submit" :loading="saving" label="Speichern" />
      </form>
    </UCard>

    <UCard v-if="loading">
      <div class="space-y-4">
        <div class="flex items-start justify-between gap-3">
          <div class="space-y-2">
            <USkeleton class="h-6 w-40 rounded-md" />
            <USkeleton class="h-4 w-64 rounded-md" />
          </div>
          <USkeleton class="h-6 w-20 rounded-full" />
        </div>
        <USkeleton class="h-24 w-full rounded-lg" />
        <div class="flex gap-3">
          <USkeleton class="h-10 w-52 rounded-md" />
        </div>
      </div>
    </UCard>
    <UCard v-else>
      <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-slate-800">Benachrichtigungen</h2>
            <p class="text-sm text-slate-500">
              Aktiviere Push-Mitteilungen für wichtige Hinweise und neue Nachrichten.
            </p>
          </div>

          <UBadge :color="notificationBadgeColor" variant="soft" size="sm">
            {{ notificationStatusLabel }}
          </UBadge>
        </div>

        <UAlert
          :color="notificationAlertColor"
          variant="soft"
          :title="notificationStatusTitle"
          :description="notificationStatusDescription"
          icon="i-heroicons-bell-alert"
        />

        <div class="flex flex-wrap gap-3">
          <UButton
            v-if="canEnableNotifications"
            label="Benachrichtigungen aktivieren"
            :loading="notificationSaving"
            @click="enableNotifications"
          />
          <UButton
            v-if="canDisableNotifications"
            label="Benachrichtigungen deaktivieren"
            color="gray"
            variant="soft"
            :loading="notificationSaving"
            @click="disableNotifications"
          />
        </div>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import type { ProfileUpdatePayload } from '~/types'

definePageMeta({ layout: 'customer' })

const { user, fetchProfile } = useAuth()
const api = useApi()
const toast = useToast()
const {
  pushStatus,
  pushError,
  refreshStatus,
  enablePush,
  disablePush,
} = usePushNotifications()
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError, errorFor } = useFormFeedback()

const saving = ref(false)
const notificationSaving = ref(false)
const loading = ref(true)

const form = reactive({
  name: '',
  email: '',
  password: '',
  address: { street: '', postalCode: '', city: '' },
  bankAccount: { iban: '', bic: '', accountHolder: '' },
})

const notificationStatusLabel = computed(() => {
  switch (pushStatus.value) {
    case 'enabled':
      return 'Aktiv'
    case 'install-required':
      return 'Home-Bildschirm'
    case 'blocked':
      return 'Blockiert'
    case 'available':
      return 'Verfügbar'
    case 'error':
      return 'Fehler'
    default:
      return 'Nicht verfügbar'
  }
})

const notificationStatusTitle = computed(() => {
  switch (pushStatus.value) {
    case 'enabled':
      return 'Push-Mitteilungen sind aktiv'
    case 'install-required':
      return 'Installation auf dem Home-Bildschirm erforderlich'
    case 'blocked':
      return 'Benachrichtigungen wurden blockiert'
    case 'available':
      return 'Benachrichtigungen können aktiviert werden'
    case 'error':
      return 'Benachrichtigungen konnten nicht eingerichtet werden'
    default:
      return 'Web Push wird auf diesem Gerät nicht unterstützt'
  }
})

const notificationStatusDescription = computed(() => {
  if (pushError.value) {
    return pushError.value
  }

  switch (pushStatus.value) {
    case 'enabled':
      return 'Dieses Gerät ist für Push-Mitteilungen registriert.'
    case 'install-required':
      return 'Auf dem iPhone funktioniert Web Push nur in der zum Home-Bildschirm hinzugefügten App.'
    case 'blocked':
      return 'Bitte erlaube Benachrichtigungen in den Browser- oder System-Einstellungen.'
    case 'available':
      return 'Du kannst Push-Mitteilungen für dieses Gerät direkt hier aktivieren.'
    case 'error':
      return 'Bitte versuche es erneut. Falls das Problem bleibt, melde dich kurz bei uns.'
    default:
      return 'Dieses Gerät oder dieser Browser unterstützt Web Push aktuell nicht.'
  }
})

const notificationBadgeColor = computed(() => {
  switch (pushStatus.value) {
    case 'enabled':
      return 'green'
    case 'install-required':
      return 'amber'
    case 'blocked':
    case 'error':
      return 'red'
    case 'available':
      return 'blue'
    default:
      return 'gray'
  }
})

const notificationAlertColor = computed(() => {
  switch (pushStatus.value) {
    case 'enabled':
      return 'green'
    case 'install-required':
      return 'amber'
    case 'blocked':
    case 'error':
      return 'red'
    case 'available':
      return 'blue'
    default:
      return 'gray'
  }
})

const canEnableNotifications = computed(() => ['available', 'install-required', 'error'].includes(pushStatus.value))
const canDisableNotifications = computed(() => pushStatus.value === 'enabled')

async function saveProfile(): Promise<void> {
  clearFormErrors()
  if (!form.name.trim()) setFieldError('name', 'Bitte einen Namen angeben.')
  if (!form.email.trim()) setFieldError('email', 'Bitte eine E-Mail-Adresse angeben.')
  if (Object.keys(fieldErrors.value).length > 0) {
    setFormError('Bitte prüfe die markierten Felder.')
    return
  }

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
  } catch (cause) {
    applyApiError(cause, 'Das Profil konnte nicht gespeichert werden.')
  } finally {
    saving.value = false
  }
}

async function enableNotifications(): Promise<void> {
  notificationSaving.value = true
  try {
    const enabled = await enablePush('customer')
    if (enabled) {
      toast.add({ title: 'Benachrichtigungen aktiviert', color: 'green' })
    } else {
      toast.add({ title: notificationStatusTitle.value, description: notificationStatusDescription.value, color: 'amber' })
    }
  } catch {
    toast.add({ title: 'Benachrichtigungen konnten nicht aktiviert werden', color: 'red' })
  } finally {
    notificationSaving.value = false
  }
}

async function disableNotifications(): Promise<void> {
  notificationSaving.value = true
  try {
    const disabled = await disablePush('customer')
    toast.add({
      title: disabled ? 'Benachrichtigungen deaktiviert' : 'Keine aktive Registrierung gefunden',
      color: disabled ? 'green' : 'amber',
    })
  } catch {
    toast.add({ title: 'Benachrichtigungen konnten nicht deaktiviert werden', color: 'red' })
  } finally {
    notificationSaving.value = false
  }
}

onMounted(async () => {
  try {
    await Promise.all([
      fetchProfile(),
      refreshStatus(),
    ])

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
  } finally {
    loading.value = false
  }
})
</script>
