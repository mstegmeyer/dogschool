<template>
  <div class="min-h-screen bg-slate-50 overflow-x-hidden">
    <!-- Desktop sidebar -->
    <aside class="hidden lg:flex fixed inset-y-0 left-0 w-64 bg-white border-r border-slate-200 flex-col z-10">
      <div class="px-6 py-5 border-b border-slate-100">
        <NuxtLink to="/customer" class="block">
          <span class="text-2xl font-extrabold text-green-700 tracking-tight">Komm!</span>
          <span class="block text-xs text-slate-400 mt-0.5">Mein Bereich</span>
        </NuxtLink>
      </div>

      <nav class="flex-1 px-3 py-4 overflow-y-auto">
        <UVerticalNavigation :links="navLinks" />
      </nav>

      <div class="p-3 border-t border-slate-200">
        <div class="px-3 py-2 mb-2">
          <p class="text-sm font-medium text-slate-700 truncate">{{ user?.name || 'Kunde' }}</p>
          <p class="text-xs text-slate-400 truncate">{{ user?.email }}</p>
        </div>
        <UButton
          color="gray"
          variant="ghost"
          block
          icon="i-heroicons-arrow-right-on-rectangle"
          label="Abmelden"
          @click="handleLogout"
        />
      </div>
    </aside>

    <!-- Mobile slideover -->
    <USlideover v-model="mobileMenuOpen" side="left" :ui="{ width: 'max-w-[16rem]' }">
      <div class="flex flex-col h-full bg-white">
        <div class="px-6 py-5 border-b border-slate-100">
          <NuxtLink to="/customer" class="block" @click="mobileMenuOpen = false">
            <span class="text-2xl font-extrabold text-green-700 tracking-tight">Komm!</span>
            <span class="block text-xs text-slate-400 mt-0.5">Mein Bereich</span>
          </NuxtLink>
        </div>

        <nav class="flex-1 px-3 py-4 overflow-y-auto">
          <UVerticalNavigation :links="navLinks" @click="mobileMenuOpen = false" />
        </nav>

        <div class="p-3 border-t border-slate-200">
          <div class="px-3 py-2 mb-2">
            <p class="text-sm font-medium text-slate-700 truncate">{{ user?.name || 'Kunde' }}</p>
            <p class="text-xs text-slate-400 truncate">{{ user?.email }}</p>
          </div>
          <UButton
            color="gray"
            variant="ghost"
            block
            icon="i-heroicons-arrow-right-on-rectangle"
            label="Abmelden"
            @click="handleLogout"
          />
        </div>
      </div>
    </USlideover>

    <!-- Mobile top bar -->
    <header class="lg:hidden fixed top-0 inset-x-0 h-14 bg-white border-b border-slate-200 flex items-center px-4 z-10">
      <UButton
        color="gray"
        variant="ghost"
        icon="i-heroicons-bars-3"
        @click="mobileMenuOpen = true"
      />
      <span class="ml-3 text-lg font-extrabold text-green-700 tracking-tight">Komm!</span>
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

const { user, logout } = useAuth()
const mobileMenuOpen = ref(false)

const route = useRoute()
watch(() => route.fullPath, () => {
  mobileMenuOpen.value = false
})

const navLinks: NavLink[][] = [[
  { label: 'Dashboard', icon: 'i-heroicons-home', to: '/customer' },
  { label: 'Mein Profil', icon: 'i-heroicons-user', to: '/customer/profile' },
  { label: 'Meine Hunde', icon: 'i-heroicons-heart', to: '/customer/dogs' },
  { label: 'Kurse', icon: 'i-heroicons-academic-cap', to: '/customer/courses' },
  { label: 'Kalender', icon: 'i-heroicons-calendar-days', to: '/customer/calendar' },
  { label: 'Guthaben', icon: 'i-heroicons-banknotes', to: '/customer/credits' },
  { label: 'Verträge', icon: 'i-heroicons-document-text', to: '/customer/contracts' },
  { label: 'Mitteilungen', icon: 'i-heroicons-bell', to: '/customer/notifications' },
]]

function handleLogout(): void {
  logout()
  navigateTo('/login')
}
</script>
