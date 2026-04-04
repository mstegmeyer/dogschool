<template>
  <div class="space-y-5">
    <section v-for="section in navSections" :key="section.label" class="space-y-2">
      <p class="px-3 text-xs font-semibold uppercase tracking-wide text-sand-500">
        {{ section.label }}
      </p>
      <UVerticalNavigation
        v-if="section.links.length"
        :links="section.links"
        :ui="navigationUi"
        @click="handleNavigate"
      />
    </section>
  </div>
</template>

<script setup lang="ts">
import type { NavLink } from '~/types'

interface NavSection {
  label: string
  links: NavLink[]
}

interface Props {
  closeOnNavigate?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  closeOnNavigate: false,
})

const emit = defineEmits<{
  navigate: []
}>()

const navigationUi = {
  active: 'text-komm-900 before:bg-sand-200',
  inactive: 'text-komm-700 hover:text-komm-900 hover:before:bg-sand-200/60',
  icon: { active: 'text-komm-700', inactive: 'text-sand-500 group-hover:text-komm-700' },
}

const navSections: NavSection[] = [
  {
    label: 'Allgemein',
    links: [
      { label: 'Dashboard', icon: 'i-heroicons-home', to: '/customer' },
      { label: 'Mein Profil', icon: 'i-heroicons-user', to: '/customer/profile' },
      { label: 'Meine Hunde', icon: 'i-heroicons-heart', to: '/customer/dogs' },
    ],
  },
  {
    label: 'Hundeschule',
    links: [
      { label: 'Kurse', icon: 'i-heroicons-academic-cap', to: '/customer/courses' },
      { label: 'Kalender', icon: 'i-heroicons-calendar-days', to: '/customer/calendar' },
      { label: 'Guthaben', icon: 'i-heroicons-banknotes', to: '/customer/credits' },
      { label: 'Verträge', icon: 'i-heroicons-document-text', to: '/customer/contracts' },
      { label: 'Mitteilungen', icon: 'i-heroicons-bell', to: '/customer/notifications' },
    ],
  },
  {
    label: 'Hundehotel',
    links: [],
  },
]

function handleNavigate(): void {
  if (props.closeOnNavigate) {
    emit('navigate')
  }
}
</script>
