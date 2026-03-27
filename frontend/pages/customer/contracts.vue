<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-800">Verträge</h1>
      <UButton icon="i-heroicons-plus" label="Vertrag anfragen" @click="showRequest = true" />
    </div>

    <AppSkeletonCollection
      v-if="loading"
      :show-desktop-table="false"
      :mobile-cards="3"
      :meta-columns="4"
      :content-lines="0"
      :show-badge="true"
    />
    <div v-else-if="contracts.length === 0" class="text-center py-12">
      <UIcon name="i-heroicons-document-text" class="w-12 h-12 text-slate-300 mx-auto mb-3" />
      <p class="text-slate-500">Du hast noch keine Verträge.</p>
    </div>

    <div v-else class="space-y-4">
      <UCard v-for="c in contracts" :key="c.id">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-slate-800">Vertrag</h3>
              <UBadge :color="contractStateColor(c.state)" variant="soft" size="xs">
                {{ contractStateLabel(c.state) }}
              </UBadge>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-3 text-sm">
              <div>
                <p class="text-xs text-slate-400">Kurse / Woche</p>
                <p class="font-medium text-slate-700">{{ c.coursesPerWeek }}x</p>
              </div>
              <div>
                <p class="text-xs text-slate-400">Preis</p>
                <p class="font-medium text-slate-700">{{ formatContractMonthlyPrice(c.price, c.type) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-400">Beginn</p>
                <p class="font-medium text-slate-700">{{ c.startDate ? formatDate(c.startDate) : '–' }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-400">Erstellt</p>
                <p class="font-medium text-slate-700">{{ formatDate(c.createdAt) }}</p>
              </div>
            </div>
          </div>
        </div>
      </UCard>
    </div>

    <!-- Request Contract Modal -->
    <UModal v-model="showRequest">
      <UCard>
        <template #header>
          <h3 class="font-semibold text-slate-800">Vertrag anfragen</h3>
        </template>
        <form class="space-y-4" @submit.prevent="requestContract">
          <UFormGroup label="Hund">
            <USelectMenu
              v-model="requestForm.dogId"
              :options="dogOptions"
              value-attribute="value"
              placeholder="Hund auswählen"
            />
          </UFormGroup>
          <UFormGroup label="Kurse pro Woche">
            <UInput v-model.number="requestForm.coursesPerWeek" type="number" min="1" max="7" />
          </UFormGroup>
          <div class="grid grid-cols-2 gap-4">
            <UFormGroup label="Beginn">
              <UInput v-model="requestForm.startDate" type="date" />
            </UFormGroup>
            <UFormGroup label="Ende (optional)">
              <UInput v-model="requestForm.endDate" type="date" />
            </UFormGroup>
          </div>
          <div class="flex justify-end gap-2">
            <UButton variant="ghost" label="Abbrechen" @click="showRequest = false" />
            <UButton type="submit" :loading="saving" label="Anfragen" />
          </div>
        </form>
      </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
import type { ApiListResponse, Contract, Dog } from '~/types'

definePageMeta({ layout: 'customer' })

const api = useApi()
const toast = useToast()
const { formatDate, contractStateLabel, contractStateColor, formatContractMonthlyPrice } = useHelpers()

const contracts = ref<Contract[]>([])
const dogs = ref<Dog[]>([])
const loading = ref(true)
const showRequest = ref(false)
const saving = ref(false)

const requestForm = reactive({ dogId: '', coursesPerWeek: 2, startDate: '', endDate: '' })
const dogOptions = computed(() => dogs.value.map(d => ({ label: d.name, value: d.id })))

async function requestContract() {
  saving.value = true
  try {
    await api.post('/api/customer/contracts', {
      dogId: requestForm.dogId,
      coursesPerWeek: requestForm.coursesPerWeek,
      startDate: requestForm.startDate || null,
      endDate: requestForm.endDate || null,
    })
    toast.add({ title: 'Vertrag angefragt', color: 'green' })
    showRequest.value = false
    await loadContracts()
  } catch {
    toast.add({ title: 'Fehler bei der Anfrage', color: 'red' })
  } finally {
    saving.value = false
  }
}

async function loadContracts(): Promise<void> {
  loading.value = true
  const res = await api.get<ApiListResponse<Contract>>('/api/customer/contracts')
  contracts.value = res.items
  loading.value = false
}

onMounted(async () => {
  const [, dogRes] = await Promise.all([
    loadContracts(),
    api.get<ApiListResponse<Dog>>('/api/customer/dogs'),
  ])
  dogs.value = dogRes.items
})
</script>
