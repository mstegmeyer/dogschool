<template>
<div class='space-y-5'>
    <section v-for='section in navSections' :key='section.label' class='space-y-2'>
        <h2
            class='px-3 text-xs font-semibold uppercase tracking-wide'
            :class="variant === 'desktop' ? 'text-komm-400' : 'text-komm-300'"
        >
            {{ section.label }}
        </h2>
        <UVerticalNavigation
            v-if='section.links.length'
            :links='section.links'
            :ui='navigationUi'
            @click='handleNavigate'
        />
    </section>
</div>
</template>

<script setup lang="ts">
import type { NavLink } from '~/types';

interface NavSection {
    label: string,
    links: NavLink[],
}

interface Props {
    variant?: 'desktop' | 'mobile',
    closeOnNavigate?: boolean,
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'desktop',
    closeOnNavigate: false,
});

const emit = defineEmits<{
    navigate: [],
}>();

const navigationUi = computed(() => ({
    ...(props.variant === 'desktop'
        ? {
            base: 'group relative flex items-center gap-1.5 focus:outline-none focus-visible:outline-none dark:focus-visible:outline-none before:absolute before:inset-px before:rounded-md disabled:cursor-not-allowed disabled:opacity-75',
        }
        : {}),
    active: 'text-white before:bg-komm-800',
    inactive: 'text-komm-200 hover:text-white hover:before:bg-komm-800/60',
    icon: { active: 'text-sand-200', inactive: 'text-komm-400 group-hover:text-sand-200' },
}));

const navSections: NavSection[] = [
    {
        label: 'Allgemein',
        links: [
            { label: 'Dashboard', icon: 'i-heroicons-home', to: '/admin' },
            { label: 'Kunden', icon: 'i-heroicons-users', to: '/admin/customers' },
            { label: 'Mitteilungen', icon: 'i-heroicons-bell', to: '/admin/notifications' },
            { label: 'Preise', icon: 'i-heroicons-banknotes', to: '/admin/pricing' },
        ],
    },
    {
        label: 'Hundeschule',
        links: [
            { label: 'Kurse', icon: 'i-heroicons-academic-cap', to: '/admin/courses' },
            { label: 'Kursarten', icon: 'i-heroicons-tag', to: '/admin/course-types' },
            { label: 'Kalender', icon: 'i-heroicons-calendar-days', to: '/admin/calendar' },
            { label: 'Verträge', icon: 'i-heroicons-document-text', to: '/admin/contracts' },
        ],
    },
    {
        label: 'Hundehotel',
        links: [
            { label: 'Hotelbuchungen', icon: 'i-heroicons-home-modern', to: '/admin/hotel/bookings' },
            { label: 'Räume', icon: 'i-heroicons-building-office-2', to: '/admin/hotel/rooms' },
            { label: 'Belegung', icon: 'i-heroicons-squares-2x2', to: '/admin/hotel/occupancy' },
            { label: 'An- & Abreisen', icon: 'i-heroicons-arrows-right-left', to: '/admin/hotel/movements' },
        ],
    },
];

function handleNavigate(): void {
    if (props.closeOnNavigate) {
        emit('navigate');
    }
}
</script>
