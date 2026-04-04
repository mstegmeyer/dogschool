<template>
  <UVerticalNavigation
    :links="navLinks"
    :ui="navigationUi"
    @click="handleNavigate"
  />
</template>

<script setup lang="ts">
import type { NavLink } from '~/types'

interface Props {
  variant?: 'desktop' | 'mobile'
  closeOnNavigate?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'desktop',
  closeOnNavigate: false,
})

const emit = defineEmits<{
  navigate: []
}>()

const navigationUi = computed(() => ({
  ...(props.variant === 'desktop'
    ? {
        base: 'group relative flex items-center gap-1.5 focus:outline-none focus-visible:outline-none dark:focus-visible:outline-none before:absolute before:inset-px before:rounded-md disabled:cursor-not-allowed disabled:opacity-75',
      }
    : {}),
  active: 'text-white before:bg-komm-800',
  inactive: 'text-komm-200 hover:text-white hover:before:bg-komm-800/60',
  icon: { active: 'text-sand-200', inactive: 'text-komm-400 group-hover:text-sand-200' },
}))

const navLinks: NavLink[] = [
  { label: 'Dashboard', icon: 'i-heroicons-home', to: '/admin' },
  { label: 'Kunden', icon: 'i-heroicons-users', to: '/admin/customers' },
  { label: 'Kurse', icon: 'i-heroicons-academic-cap', to: '/admin/courses' },
  { label: 'Kursarten', icon: 'i-heroicons-tag', to: '/admin/course-types' },
  { label: 'Kalender', icon: 'i-heroicons-calendar-days', to: '/admin/calendar' },
  { label: 'Verträge', icon: 'i-heroicons-document-text', to: '/admin/contracts' },
  { label: 'Mitteilungen', icon: 'i-heroicons-bell', to: '/admin/notifications' },
]

function handleNavigate(): void {
  if (props.closeOnNavigate) {
    emit('navigate')
  }
}
</script>
