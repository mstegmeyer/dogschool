<template>
  <UCard>
    <AppSkeletonCollection
      v-if="loading"
      :mobile-cards="4"
      :desktop-rows="6"
      :desktop-columns="7"
      :meta-columns="4"
      :show-actions="true"
    />
    <div v-else-if="contracts.length === 0" class="text-sm text-slate-400">Keine Verträge für diesen Filter gefunden.</div>
    <template v-else>
      <div class="space-y-3 md:hidden">
        <div
          v-for="contract in contracts"
          :key="contract.id"
          class="rounded-lg border border-slate-200 bg-white p-4"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="font-medium text-slate-800">{{ contract.dogName || '–' }}</p>
              <p class="text-sm text-slate-500">{{ contract.customerName || '–' }}</p>
            </div>
            <UBadge :color="contractStateColor(contract.state)" variant="soft" size="xs">
              {{ contractStateLabel(contract.state) }}
            </UBadge>
          </div>
          <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
            <div>
              <p class="text-slate-400">Kurse</p>
              <p class="font-medium text-slate-700">{{ contract.coursesPerWeek }}× / Wo.</p>
            </div>
            <div>
              <p class="text-slate-400">Preis</p>
              <p class="font-medium text-slate-700">{{ formatContractMonthlyPrice(contract.price, contract.type) }}</p>
            </div>
            <div>
              <p class="text-slate-400">Zeitraum</p>
              <p class="font-medium text-slate-700">
                {{ contract.startDate ? `${formatDate(contract.startDate)} – ${contract.endDate ? formatDate(contract.endDate) : '∞'}` : '–' }}
              </p>
            </div>
            <div>
              <p class="text-slate-400">Erstellt</p>
              <p class="font-medium text-slate-700">{{ formatDate(contract.createdAt) }}</p>
            </div>
          </div>
          <div class="mt-4 flex flex-wrap gap-2">
            <template v-if="contract.state === 'REQUESTED'">
              <UButton size="sm" color="primary" variant="soft" label="Genehmigen" @click="emit('approve', contract)" />
              <UButton size="sm" color="red" variant="soft" label="Ablehnen" @click="emit('decline', contract)" />
            </template>
            <UButton
              v-else-if="contract.state === 'ACTIVE'"
              size="sm"
              color="red"
              variant="soft"
              label="Kündigen"
              @click="emit('cancel', contract)"
            />
          </div>
        </div>
      </div>
      <div class="hidden overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 md:block">
        <UTable
          :sort="sort"
          :columns="columns"
          :rows="contracts"
          class="min-w-[720px]"
          sort-mode="manual"
          @update:sort="emit('update:sort', $event)"
        >
          <template #participant-data="{ row }">
            <div class="min-w-[8rem] max-w-[14rem] py-0.5">
              <p class="truncate font-medium text-slate-800" :title="row.dogName || ''">
                {{ row.dogName || '–' }}
              </p>
              <p class="truncate text-xs text-slate-500" :title="row.customerName || ''">
                {{ row.customerName || '–' }}
              </p>
            </div>
          </template>
          <template #state-data="{ row }">
            <UBadge :color="contractStateColor(row.state)" variant="soft" size="xs">
              {{ contractStateLabel(row.state) }}
            </UBadge>
          </template>
          <template #coursesPerWeek-data="{ row }">
            {{ row.coursesPerWeek }}× / Wo.
          </template>
          <template #price-data="{ row }">
            <span class="whitespace-nowrap text-sm">{{ formatContractMonthlyPrice(row.price, row.type) }}</span>
          </template>
          <template #dates-data="{ row }">
            <span v-if="row.startDate" class="whitespace-nowrap text-sm">
              {{ formatDate(row.startDate) }} – {{ row.endDate ? formatDate(row.endDate) : '∞' }}
            </span>
            <span v-else class="text-slate-400">–</span>
          </template>
          <template #createdAt-data="{ row }">
            <span class="whitespace-nowrap text-sm">{{ formatDate(row.createdAt) }}</span>
          </template>
          <template #actions-data="{ row }">
            <div class="flex min-w-[9rem] flex-wrap justify-end gap-1 whitespace-nowrap">
              <template v-if="row.state === 'REQUESTED'">
                <UButton size="xs" color="primary" variant="soft" label="Genehmigen" @click="emit('approve', row)" />
                <UButton size="xs" color="red" variant="soft" label="Ablehnen" @click="emit('decline', row)" />
              </template>
              <UButton
                v-else-if="row.state === 'ACTIVE'"
                size="xs"
                color="red"
                variant="soft"
                label="Kündigen"
                @click="emit('cancel', row)"
              />
            </div>
          </template>
        </UTable>
      </div>
      <div class="mx-4 mt-6 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm text-slate-500 sm:mx-0 sm:flex-row sm:items-center sm:justify-between">
        <p>{{ resultSummary }}</p>
        <UPagination
          v-if="showPagination"
          :model-value="currentPage"
          :page-count="pageSize"
          :total="totalContracts"
          :show-first="true"
          :show-last="true"
          @update:model-value="emit('update:currentPage', $event)"
        />
      </div>
    </template>
  </UCard>
</template>

<script setup lang="ts">
import type { Contract } from '~/types'

defineProps<{
  loading: boolean
  contracts: Contract[]
  sort: { column: string | null; direction: 'asc' | 'desc' }
  columns: Array<{ key: string; label: string; sortable?: boolean }>
  resultSummary: string
  showPagination: boolean
  currentPage: number
  pageSize: number
  totalContracts: number
}>()

const emit = defineEmits<{
  (event: 'update:sort', value: { column: string | null; direction: 'asc' | 'desc' }): void
  (event: 'update:currentPage', value: number): void
  (event: 'approve', value: Contract): void
  (event: 'decline', value: Contract): void
  (event: 'cancel', value: Contract): void
}>()

const { formatDate, contractStateLabel, contractStateColor, formatContractMonthlyPrice } = useHelpers()
</script>
