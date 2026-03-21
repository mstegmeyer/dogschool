<template>
  <div class="min-h-screen flex bg-slate-50">
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0">
      <div class="px-6 py-5 border-b border-slate-100">
        <NuxtLink to="/admin" class="block">
          <span class="text-2xl font-extrabold text-green-700 tracking-tight">Komm!</span>
          <span class="block text-xs text-slate-400 mt-0.5">Admin-Bereich</span>
        </NuxtLink>
      </div>

      <nav class="flex-1 px-3 py-4">
        <UVerticalNavigation :links="navLinks" />
      </nav>

      <div class="p-3 border-t border-slate-200">
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

    <main class="flex-1 min-w-0 overflow-auto">
      <div class="w-full px-6 py-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import type { NavLink } from '~/types'

const { logout } = useAuth()

const navLinks: NavLink[][] = [[
  { label: 'Dashboard', icon: 'i-heroicons-home', to: '/admin' },
  { label: 'Kunden', icon: 'i-heroicons-users', to: '/admin/customers' },
  { label: 'Kurse', icon: 'i-heroicons-academic-cap', to: '/admin/courses' },
  { label: 'Kalender', icon: 'i-heroicons-calendar-days', to: '/admin/calendar' },
  { label: 'Verträge', icon: 'i-heroicons-document-text', to: '/admin/contracts' },
  { label: 'Mitteilungen', icon: 'i-heroicons-bell', to: '/admin/notifications' },
]]

function handleLogout(): void {
  logout()
  navigateTo('/login')
}
</script>
