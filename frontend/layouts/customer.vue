<template>
  <div class="min-h-screen bg-sand-50 overflow-x-hidden">
    <!-- Desktop sidebar -->
    <aside data-testid="customer-desktop-sidebar" class="hidden lg:flex fixed inset-y-0 left-0 w-64 bg-sand-100 border-r border-sand-200 flex-col z-10">
      <div class="px-6 py-5 border-b border-sand-200">
        <NuxtLink to="/customer" class="block">
          <AppLogo tone="on-light" />
          <span class="block text-xs text-sand-500 mt-1.5">Mein Bereich</span>
        </NuxtLink>
      </div>

      <nav class="flex-1 px-3 py-4 overflow-y-auto">
        <UVerticalNavigation
          :links="navLinks"
          :ui="{
            active: 'text-komm-900 before:bg-sand-200',
            inactive: 'text-komm-700 hover:text-komm-900 hover:before:bg-sand-200/60',
            icon: { active: 'text-komm-700', inactive: 'text-sand-500 group-hover:text-komm-700' },
          }"
        />
      </nav>

      <div class="p-3 border-t border-sand-200">
        <div class="px-3 py-2 mb-2">
          <p class="text-sm font-medium text-komm-900 truncate">{{ user?.name || 'Kunde' }}</p>
          <p class="text-xs text-sand-500 truncate">{{ user?.email }}</p>
        </div>
        <UButton
          data-testid="customer-logout"
          color="gray"
          variant="ghost"
          block
          icon="i-heroicons-arrow-right-on-rectangle"
          label="Abmelden"
          :ui="{ color: { gray: { ghost: 'text-komm-600 hover:text-komm-900 hover:bg-sand-200' } } }"
          @click="handleLogout"
        />
      </div>
    </aside>

    <!-- Mobile slideover -->
    <USlideover v-model="mobileMenuOpen" side="left" :ui="{ width: 'w-[min(16rem,calc(100vw-2rem))]' }">
      <div data-testid="customer-mobile-menu" class="mobile-shell-drawer flex flex-col h-full bg-sand-100">
        <div class="flex items-start justify-between gap-3 px-6 py-5 border-b border-sand-200">
          <NuxtLink to="/customer" class="block min-w-0" @click="mobileMenuOpen = false">
            <AppLogo tone="on-light" />
            <span class="block text-xs text-sand-500 mt-1.5">Mein Bereich</span>
          </NuxtLink>
          <UButton
            data-testid="customer-logout"
            color="gray"
            variant="ghost"
            icon="i-heroicons-x-mark"
            aria-label="Menü schließen"
            class="shrink-0"
            :ui="{ color: { gray: { ghost: 'text-komm-600 hover:text-komm-900 hover:bg-sand-200' } } }"
            @click="mobileMenuOpen = false"
          />
        </div>

        <nav class="flex-1 px-3 py-4 overflow-y-auto">
          <UVerticalNavigation
            :links="navLinks"
            :ui="{
              active: 'text-komm-900 before:bg-sand-200',
              inactive: 'text-komm-700 hover:text-komm-900 hover:before:bg-sand-200/60',
              icon: { active: 'text-komm-700', inactive: 'text-sand-500 group-hover:text-komm-700' },
            }"
            @click="mobileMenuOpen = false"
          />
        </nav>

        <div class="p-3 border-t border-sand-200">
          <div class="px-3 py-2 mb-2">
            <p class="text-sm font-medium text-komm-900 truncate">{{ user?.name || 'Kunde' }}</p>
            <p class="text-xs text-sand-500 truncate">{{ user?.email }}</p>
          </div>
          <UButton
            color="gray"
            variant="ghost"
            block
            icon="i-heroicons-arrow-right-on-rectangle"
            label="Abmelden"
            :ui="{ color: { gray: { ghost: 'text-komm-600 hover:text-komm-900 hover:bg-sand-200' } } }"
            @click="handleLogout"
          />
        </div>
      </div>
    </USlideover>

    <!-- Mobile top bar -->
    <header class="mobile-shell-header lg:hidden fixed top-0 inset-x-0 bg-sand-100 border-b border-sand-200 flex items-center z-10">
      <UButton
        data-testid="customer-mobile-menu-toggle"
        color="gray"
        variant="ghost"
        icon="i-heroicons-bars-3"
        class="shrink-0"
        aria-label="Menü öffnen"
        @click="mobileMenuOpen = true"
      />
      <AppLogo tone="on-light" size="sm" class="ml-3 h-6 max-w-[7.5rem] shrink min-w-0" />
    </header>

    <main class="mobile-shell-main lg:ml-64 min-h-screen lg:pt-0">
      <div class="mobile-shell-content w-full py-6 lg:px-6 lg:py-8">
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
