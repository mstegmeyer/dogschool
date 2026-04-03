<template>
  <AppSkeletonCollection
    v-if="loading"
    :show-desktop-table="false"
    :mobile-cards="3"
    :meta-columns="4"
    :content-lines="0"
    :show-badge="true"
  />
  <div v-else-if="contracts.length === 0" class="py-12 text-center">
    <UIcon name="i-heroicons-document-text" class="mx-auto mb-3 h-12 w-12 text-slate-300" />
    <p class="text-slate-500">Du hast noch keine Verträge.</p>
  </div>
  <div v-else class="space-y-4">
    <UCard v-for="contract in contracts" :key="contract.id">
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2">
            <h3 class="font-semibold text-slate-800">Vertrag</h3>
            <UBadge :color="contractStateColor(contract.state)" variant="soft" size="xs">
              {{ contractStateLabel(contract.state) }}
            </UBadge>
          </div>
          <div
            class="mt-3 grid grid-cols-2 gap-4 text-sm"
            :class="contract.endDate ? 'sm:grid-cols-4' : 'sm:grid-cols-3'"
          >
            <div>
              <p class="text-xs text-slate-400">Kurse / Woche</p>
              <p class="font-medium text-slate-700">{{ contract.coursesPerWeek }}x</p>
            </div>
            <div>
              <p class="text-xs text-slate-400">Preis</p>
              <p class="font-medium text-slate-700">{{ formatContractMonthlyPrice(contract.price, contract.type) }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400">Beginn</p>
              <p class="font-medium text-slate-700">{{ contract.startDate ? formatDate(contract.startDate) : '–' }}</p>
            </div>
            <div v-if="contract.endDate">
              <p class="text-xs text-slate-400">Ende</p>
              <p class="font-medium text-slate-700">{{ formatDate(contract.endDate) }}</p>
            </div>
          </div>
        </div>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import type { Contract } from '~/types'

defineProps<{
  loading: boolean
  contracts: Contract[]
}>()

const { formatDate, contractStateLabel, contractStateColor, formatContractMonthlyPrice } = useHelpers()
</script>
