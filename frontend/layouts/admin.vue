<template>
  <div class="min-h-screen bg-gray-50 overflow-x-hidden">
    <!-- Desktop sidebar -->
    <aside class="hidden lg:flex fixed inset-y-0 left-0 w-64 bg-komm-900 flex-col z-10">
      <div class="px-6 py-5 border-b border-komm-800">
        <NuxtLink to="/admin" class="block">
          <AppLogo tone="on-dark" />
          <span class="block text-xs text-komm-400 mt-1.5">Admin-Bereich</span>
        </NuxtLink>
      </div>

      <nav class="flex-1 px-3 py-4 overflow-y-auto">
        <UVerticalNavigation
          :links="navLinks"
          :ui="{
            base: 'group relative flex items-center gap-1.5 focus:outline-none focus-visible:outline-none dark:focus-visible:outline-none before:absolute before:inset-px before:rounded-md disabled:cursor-not-allowed disabled:opacity-75',
            active: 'text-white before:bg-komm-800',
            inactive: 'text-komm-200 hover:text-white hover:before:bg-komm-800/60',
            icon: { active: 'text-sand-200', inactive: 'text-komm-400 group-hover:text-sand-200' },
          }"
        />
      </nav>

      <div class="p-3 border-t border-komm-800">
        <UButton
          color="white"
          variant="ghost"
          block
          icon="i-heroicons-arrow-right-on-rectangle"
          label="Abmelden"
          :ui="{ color: { white: { ghost: 'text-komm-300 hover:text-white hover:bg-komm-800' } } }"
          @click="handleLogout"
        />
      </div>
    </aside>

    <!-- Mobile slideover -->
    <USlideover v-model="mobileMenuOpen" side="left" :ui="{ width: 'max-w-[16rem]' }">
      <div class="flex flex-col h-full bg-komm-900">
        <div class="px-6 py-5 border-b border-komm-800">
          <NuxtLink to="/admin" class="block" @click="mobileMenuOpen = false">
            <AppLogo tone="on-dark" />
            <span class="block text-xs text-komm-400 mt-1.5">Admin-Bereich</span>
          </NuxtLink>
        </div>

        <nav class="flex-1 px-3 py-4 overflow-y-auto">
          <UVerticalNavigation
            :links="navLinks"
            :ui="{
              active: 'text-white before:bg-komm-800',
              inactive: 'text-komm-200 hover:text-white hover:before:bg-komm-800/60',
              icon: { active: 'text-sand-200', inactive: 'text-komm-400 group-hover:text-sand-200' },
            }"
            @click="mobileMenuOpen = false"
          />
        </nav>

        <div class="p-3 border-t border-komm-800">
          <UButton
            color="white"
            variant="ghost"
            block
            icon="i-heroicons-arrow-right-on-rectangle"
            label="Abmelden"
            :ui="{ color: { white: { ghost: 'text-komm-300 hover:text-white hover:bg-komm-800' } } }"
            @click="handleLogout"
          />
        </div>
      </div>
    </USlideover>

    <!-- Mobile top bar -->
    <header class="lg:hidden fixed top-0 inset-x-0 h-14 bg-komm-900 flex items-center px-4 z-10">
      <UButton
        color="white"
        variant="ghost"
        icon="i-heroicons-bars-3"
        :ui="{ color: { white: { ghost: 'text-komm-200 hover:text-white hover:bg-komm-800' } } }"
        @click="mobileMenuOpen = true"
      />
      <div class="ml-3 flex items-center gap-2 min-w-0">
        <AppLogo tone="on-dark" size="sm" />
        <span class="text-xs text-komm-400 shrink-0">Admin</span>
      </div>
    </header>

    <main class="lg:ml-64 min-h-screen pt-14 lg:pt-0">
      <div class="w-full px-4 py-6 lg:px-6 lg:py-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import type { NavLink } from '~/types'

const { logout } = useAuth()
const mobileMenuOpen = ref(false)

const route = useRoute()
watch(() => route.fullPath, () => {
  mobileMenuOpen.value = false
})

const navLinks: NavLink[][] = [[
  { label: 'Dashboard', icon: 'i-heroicons-home', to: '/admin' },
  { label: 'Kunden', icon: 'i-heroicons-users', to: '/admin/customers' },
  { label: 'Kurse', icon: 'i-heroicons-academic-cap', to: '/admin/courses' },
  { label: 'Kursarten', icon: 'i-heroicons-tag', to: '/admin/course-types' },
  { label: 'Kalender', icon: 'i-heroicons-calendar-days', to: '/admin/calendar' },
  { label: 'Verträge', icon: 'i-heroicons-document-text', to: '/admin/contracts' },
  { label: 'Mitteilungen', icon: 'i-heroicons-bell', to: '/admin/notifications' },
]]

function handleLogout(): void {
  logout()
  navigateTo('/login')
}
</script>
