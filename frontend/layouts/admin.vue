<template>
<div class='min-h-screen bg-gray-50 overflow-x-hidden'>
    <!-- Desktop sidebar -->
    <aside data-testid='admin-desktop-sidebar' class='hidden lg:flex fixed inset-y-0 left-0 w-64 bg-komm-900 flex-col z-10'>
        <div class='px-6 py-5 border-b border-komm-800'>
            <NuxtLink to='/admin' class='block'>
                <AppLogo tone='on-dark' />
                <span class='block text-xs text-komm-400 mt-1.5'>Admin-Bereich</span>
            </NuxtLink>
        </div>

        <nav class='flex-1 px-3 py-4 overflow-y-auto'>
            <AdminNavigationMenu />
        </nav>

        <div class='p-3 border-t border-komm-800'>
            <UButton
                data-testid='admin-logout'
                color='white'
                variant='ghost'
                block
                icon='i-heroicons-arrow-right-on-rectangle'
                label='Abmelden'
                :ui="{ color: { white: { ghost: 'text-komm-300 hover:text-white hover:bg-komm-800' } } }"
                @click='handleLogout'
            />
        </div>
    </aside>

    <!-- Mobile slideover -->
    <USlideover v-model='mobileMenuOpen' side='left' :ui="{ width: 'w-[min(16rem,calc(100vw-2rem))]' }">
        <div data-testid='admin-mobile-menu' class='mobile-shell-drawer flex flex-col h-full bg-komm-900'>
            <div class='flex items-start justify-between gap-3 px-6 py-5 border-b border-komm-800'>
                <NuxtLink to='/admin' class='block min-w-0' @click='mobileMenuOpen = false'>
                    <AppLogo tone='on-dark' />
                    <span class='block text-xs text-komm-400 mt-1.5'>Admin-Bereich</span>
                </NuxtLink>
                <UButton
                    data-testid='admin-logout'
                    color='white'
                    variant='ghost'
                    icon='i-heroicons-x-mark'
                    aria-label='Menü schließen'
                    class='shrink-0'
                    :ui="{ color: { white: { ghost: 'text-komm-300 hover:text-white hover:bg-komm-800' } } }"
                    @click='mobileMenuOpen = false'
                />
            </div>

            <nav class='flex-1 px-3 py-4 overflow-y-auto'>
                <AdminNavigationMenu variant='mobile' close-on-navigate @navigate='mobileMenuOpen = false' />
            </nav>

            <div class='p-3 border-t border-komm-800'>
                <UButton
                    color='white'
                    variant='ghost'
                    block
                    icon='i-heroicons-arrow-right-on-rectangle'
                    label='Abmelden'
                    :ui="{ color: { white: { ghost: 'text-komm-300 hover:text-white hover:bg-komm-800' } } }"
                    @click='handleLogout'
                />
            </div>
        </div>
    </USlideover>

    <!-- Mobile top bar -->
    <header class='mobile-shell-header lg:hidden fixed top-0 inset-x-0 bg-komm-900 flex items-center z-10'>
        <UButton
            data-testid='admin-mobile-menu-toggle'
            color='white'
            variant='ghost'
            icon='i-heroicons-bars-3'
            class='shrink-0'
            aria-label='Menü öffnen'
            :ui="{ color: { white: { ghost: 'text-komm-200 hover:text-white hover:bg-komm-800' } } }"
            @click='mobileMenuOpen = true'
        />
        <div class='ml-3 flex items-center gap-2 min-w-0'>
            <AppLogo tone='on-dark' size='sm' class='h-6 max-w-[7.5rem] shrink min-w-0' />
            <span class='text-xs text-komm-400 shrink-0'>Admin</span>
        </div>
    </header>

    <main class='mobile-shell-main lg:ml-64 min-h-screen lg:pt-0'>
        <div class='mobile-shell-content w-full py-6 lg:px-6 lg:py-8'>
            <slot />
        </div>
    </main>
</div>
</template>

<script setup lang="ts">
import AdminNavigationMenu from '~/modules/admin/AdminNavigationMenu.vue';

const { logout } = useAuth();
const mobileMenuOpen = ref(false);

const route = useRoute();
watch(() => route.fullPath, () => {
    mobileMenuOpen.value = false;
});

function handleLogout(): void {
    logout();
    navigateTo('/login');
}
</script>
