<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Meine Hunde</h1>
      <UButton icon="i-heroicons-plus" label="Hund hinzufügen" @click="showAdd = true" />
    </div>

    <DogsGrid :loading="loading" :dogs="dogs" @add="showAdd = true" />

    <AddDogModal
      v-model="showAdd"
      :form="newDog"
      :field-errors="fieldErrors"
      :form-error="formError"
      :saving="saving"
      @submit="addDog"
      @cancel="closeAddModal"
      @clear-field-error="clearFieldError"
    />
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Dog } from '~/types'
import AddDogModal from './components/AddDogModal.vue'
import DogsGrid from './components/DogsGrid.vue'

definePageMeta({ layout: 'customer' })

const api = useApi()
const toast = useToast()
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError } = useFormFeedback()

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
