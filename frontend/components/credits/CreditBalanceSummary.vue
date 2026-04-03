<template>
  <div :class="containerClass">
    <template v-if="loading">
      <USkeleton :class="valueSkeletonClass" />
      <USkeleton :class="labelSkeletonClass" />
    </template>
    <template v-else>
      <p :class="valueClass">
        {{ balance }}
      </p>
      <p class="mt-2 text-sm text-slate-400">{{ label }}</p>
    </template>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  loading: boolean
  balance: number
  label?: string
  compact?: boolean
}>(), {
  label: 'Verfügbare Credits',
  compact: false,
})

const containerClass = computed(() => props.compact ? 'py-2 text-center' : 'py-4 text-center')
const valueClass = computed(() => [
  props.compact ? 'text-4xl' : 'text-5xl',
  'font-bold',
  props.balance >= 0 ? 'text-komm-600' : 'text-red-500',
])
const valueSkeletonClass = computed(() => props.compact
  ? 'mx-auto h-10 w-20 rounded-md'
  : 'mx-auto h-12 w-24 rounded-md')
const labelSkeletonClass = 'mx-auto mt-3 h-4 w-32 rounded-md'
</script>
