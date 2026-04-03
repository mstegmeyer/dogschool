<template>
  <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-3">
      <h1 class="text-2xl font-bold text-slate-800">{{ title }}</h1>
      <slot name="title-actions" />
    </div>

    <div class="flex items-center gap-2">
      <UButtonGroup size="xs">
        <UButton
          :variant="viewMode === 'day' ? 'solid' : 'outline'"
          label="Tag"
          @click="emit('update:viewMode', 'day')"
        />
        <UButton
          :variant="viewMode === 'week' ? 'solid' : 'outline'"
          label="Woche"
          @click="emit('update:viewMode', 'week')"
        />
      </UButtonGroup>
      <UButton icon="i-heroicons-chevron-left" variant="ghost" size="sm" @click="emit('prev')" />
      <span class="min-w-[100px] text-center text-sm font-medium text-slate-600 sm:min-w-[160px]">
        {{ rangeLabel }}
      </span>
      <UButton icon="i-heroicons-chevron-right" variant="ghost" size="sm" @click="emit('next')" />
      <UButton variant="outline" size="sm" label="Heute" class="ml-1" @click="emit('today')" />
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  viewMode: 'day' | 'week'
  rangeLabel: string
}>()

const emit = defineEmits<{
  (event: 'update:viewMode', value: 'day' | 'week'): void
  (event: 'prev'): void
  (event: 'next'): void
  (event: 'today'): void
}>()
</script>
