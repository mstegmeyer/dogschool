<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Meine Hunde</h1>
      <UButton icon="i-heroicons-plus" label="Hund hinzufügen" @click="showAdd = true" />
    </div>

    <div v-if="dogs.length === 0 && !loading" class="text-center py-12">
      <UIcon name="i-heroicons-heart" class="w-12 h-12 text-slate-300 mx-auto mb-3" />
      <p class="text-slate-500">Du hast noch keinen Hund registriert.</p>
      <UButton class="mt-4" label="Jetzt hinzufügen" @click="showAdd = true" />
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <UCard v-for="dog in dogs" :key="dog.id">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
            <UIcon name="i-heroicons-heart" class="w-5 h-5 text-green-600" />
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
          <UFormGroup label="Name">
            <UInput v-model="newDog.name" placeholder="z.B. Bella" required />
          </UFormGroup>
          <UFormGroup label="Rasse">
            <UInput v-model="newDog.race" placeholder="z.B. Golden Retriever" />
          </UFormGroup>
          <div class="grid grid-cols-2 gap-4">
            <UFormGroup label="Geschlecht">
              <USelectMenu
                v-model="newDog.gender"
                :options="[{ label: 'Rüde', value: 'male' }, { label: 'Hündin', value: 'female' }]"
                value-attribute="value"
                placeholder="Auswählen"
              />
            </UFormGroup>
            <UFormGroup label="Farbe">
              <UInput v-model="newDog.color" placeholder="z.B. Golden" />
            </UFormGroup>
          </div>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" label="Abbrechen" @click="showAdd = false" />
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

const dogs = ref<Dog[]>([])
const loading = ref(true)
const showAdd = ref(false)
const saving = ref(false)

const newDog = reactive({ name: '', race: '', gender: '', color: '' })

async function addDog() {
  saving.value = true
  try {
    await api.post('/api/customer/dogs', {
      name: newDog.name,
      race: newDog.race || null,
      gender: newDog.gender || null,
      color: newDog.color || null,
    })
    toast.add({ title: `${newDog.name} wurde hinzugefügt`, color: 'green' })
    showAdd.value = false
    newDog.name = ''
    newDog.race = ''
    newDog.gender = ''
    newDog.color = ''
    await loadDogs()
  } catch {
    toast.add({ title: 'Fehler beim Hinzufügen', color: 'red' })
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
