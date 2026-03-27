<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Meine Hunde</h1>
      <UButton icon="i-heroicons-plus" label="Hund hinzufügen" @click="showAdd = true" />
    </div>

    <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <UCard v-for="index in 6" :key="index">
        <div class="flex items-start gap-3">
          <USkeleton class="h-10 w-10 shrink-0 rounded-full" />
          <div class="min-w-0 flex-1 space-y-2">
            <USkeleton class="h-4 w-24 rounded-md" />
            <USkeleton class="h-3 w-32 rounded-md" />
            <div class="flex gap-2 pt-1">
              <USkeleton class="h-5 w-16 rounded-full" />
              <USkeleton class="h-5 w-14 rounded-full" />
            </div>
          </div>
        </div>
      </UCard>
    </div>
    <div v-else-if="dogs.length === 0" class="text-center py-12">
      <UIcon name="i-heroicons-heart" class="w-12 h-12 text-slate-300 mx-auto mb-3" />
      <p class="text-slate-500">Du hast noch keinen Hund registriert.</p>
      <UButton class="mt-4" label="Jetzt hinzufügen" @click="showAdd = true" />
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <UCard v-for="dog in dogs" :key="dog.id">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-full bg-sand-200 flex items-center justify-center shrink-0">
            <UIcon name="i-heroicons-heart" class="w-5 h-5 text-komm-700" />
          </div>
          <div>
            <h3 class="font-semibold text-slate-800">{{ dog.name }}</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ dog.race || 'Rasse unbekannt' }}</p>
            <div class="flex gap-2 mt-2">
              <UBadge v-if="dog.gender" variant="soft" color="gray" size="xs">
                {{ dog.gender === 'male' ? 'Rüde' : 'Hündin' }}
              </UBadge>
              <UBadge v-if="dog.color" variant="soft" color="gray" size="xs">{{ dog.color }}</UBadge>
            </div>
          </div>
        </div>
      </UCard>
    </div>

    <!-- Add Dog Modal -->
    <UModal v-model="showAdd">
      <UCard>
        <template #header>
          <h3 class="font-semibold text-slate-800">Hund hinzufügen</h3>
        </template>
        <form class="space-y-4" @submit.prevent="addDog">
          <UFormGroup label="Name" :error="errorFor('name')">
            <UInput v-model="newDog.name" placeholder="z.B. Bella" required @update:model-value="clearFieldError('name')" />
          </UFormGroup>
          <UFormGroup label="Rasse" :error="errorFor('race')">
            <UInput v-model="newDog.race" placeholder="z.B. Golden Retriever" />
          </UFormGroup>
          <div class="grid grid-cols-2 gap-4">
            <UFormGroup label="Geschlecht" :error="errorFor('gender')">
              <USelectMenu
                v-model="newDog.gender"
                :options="[{ label: 'Rüde', value: 'male' }, { label: 'Hündin', value: 'female' }]"
                value-attribute="value"
                placeholder="Auswählen"
                @update:model-value="clearFieldError('gender')"
              />
            </UFormGroup>
            <UFormGroup label="Farbe" :error="errorFor('color')">
              <UInput v-model="newDog.color" placeholder="z.B. Golden" />
            </UFormGroup>
          </div>
          <UAlert v-if="formError" color="red" variant="soft" :title="formError" icon="i-heroicons-exclamation-triangle" />
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" label="Abbrechen" @click="closeAddModal" />
            <UButton type="submit" :loading="saving" label="Hinzufügen" />
          </div>
        </form>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Dog } from '~/types'

definePageMeta({ layout: 'customer' })

const api = useApi()
const toast = useToast()
const { formError, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError, errorFor } = useFormFeedback()

const dogs = ref<Dog[]>([])
const loading = ref(true)
const showAdd = ref(false)
const saving = ref(false)

const newDog = reactive({ name: '', race: '', gender: '', color: '' })

function resetDogForm() {
  newDog.name = ''
  newDog.race = ''
  newDog.gender = ''
  newDog.color = ''
}

function closeAddModal() {
  showAdd.value = false
  clearFormErrors()
}

async function addDog() {
  clearFormErrors()
  if (!newDog.name.trim()) {
    setFieldError('name', 'Bitte einen Namen angeben.')
    setFormError('Bitte prüfe die markierten Felder.')
    return
  }

  saving.value = true
  try {
    await api.post('/api/customer/dogs', {
      name: newDog.name,
      race: newDog.race || null,
      gender: newDog.gender || null,
      color: newDog.color || null,
    })
    toast.add({ title: `${newDog.name} wurde hinzugefügt`, color: 'green' })
    closeAddModal()
    resetDogForm()
    await loadDogs()
  } catch (cause) {
    applyApiError(cause, 'Der Hund konnte nicht hinzugefügt werden.')
  } finally {
    saving.value = false
  }
}

async function loadDogs(): Promise<void> {
  loading.value = true
  const res = await api.get<ApiListResponse<Dog>>('/api/customer/dogs')
  dogs.value = res.items
  loading.value = false
}

onMounted(() => loadDogs())
</script>
