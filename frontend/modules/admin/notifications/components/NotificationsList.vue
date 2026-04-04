<template>
<UCard :ui="{ body: { padding: 'p-0 sm:p-0' } }">
    <div v-if='loading' class='p-4'>
        <AppSkeletonCollection
            :mobile-cards='4'
            :desktop-rows='6'
            :desktop-columns='7'
            :meta-columns='0'
            :content-lines='3'
            :show-actions='true'
        />
    </div>
    <div v-else-if='notifications.length === 0' class='py-8 text-center text-sm text-slate-400'>
        Noch keine Mitteilungen erstellt
    </div>
    <template v-else>
        <div class='space-y-3 p-4 md:hidden'>
            <div
                v-for='notification in notifications'
                :key='notification.id'
                :data-testid='`notification-card-${notification.id}`'
                class='rounded-lg border border-slate-200 bg-white p-4'
            >
                <div class='flex items-start justify-between gap-3'>
                    <div class='min-w-0'>
                        <p class='font-medium text-slate-800'>
                            {{ notification.title }}
                        </p>
                        <p class='mt-1 text-xs text-slate-500'>
                            {{ formatDateTime(notification.createdAt) }}
                        </p>
                    </div>
                    <div class='flex gap-1'>
                        <UButton
                            :data-testid='`edit-notification-mobile-${notification.id}`'
                            icon='i-heroicons-pencil'
                            size='xs'
                            variant='ghost'
                            @click="emit('edit', notification)"
                        />
                        <UButton
                            :data-testid='`delete-notification-mobile-${notification.id}`'
                            icon='i-heroicons-trash'
                            size='xs'
                            variant='ghost'
                            color='red'
                            @click="emit('delete', notification)"
                        />
                    </div>
                </div>
                <div class='mt-3 flex flex-wrap gap-2'>
                    <span v-if='notification.isPinned' class='inline-flex items-center gap-1 whitespace-nowrap rounded px-1.5 py-0.5 text-xs font-medium text-indigo-700 bg-indigo-50'>
                        <UIcon name='i-heroicons-map-pin' class='h-3.5 w-3.5' />
                        bis {{ formatDate(notification.pinnedUntil) }}
                    </span>
                    <span v-else-if='notification.pinnedUntil' class='whitespace-nowrap text-xs text-slate-400'>
                        abgelaufen
                    </span>
                    <span v-if='notification.isGlobal' class='inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium text-amber-700 bg-amber-50'>
                        <UIcon name='i-heroicons-globe-alt' class='h-3.5 w-3.5' />
                        Alle Kurse
                    </span>
                    <span
                        v-for='course in notification.isGlobal ? [] : notification.courses'
                        :key='course.id'
                        class='rounded px-1.5 py-0.5 text-xs text-slate-600 bg-slate-100'
                    >
                        {{ formatNotificationCourse(course) }}
                    </span>
                </div>
                <p class='mt-3 text-sm text-slate-600'>
                    {{ notification.message }}
                </p>
                <p class='mt-3 text-xs text-slate-400'>
                    Von {{ notification.authorName || '–' }}
                </p>
            </div>
        </div>
        <div class='hidden overflow-x-auto md:block'>
            <UTable
                :columns='columns'
                :rows='notifications'
                :ui="{
                    th: { base: 'text-left text-xs font-semibold text-slate-500 py-2 px-3' },
                    td: { base: 'py-1.5 px-3 align-top text-sm' },
                }"
            >
                <template #createdAt-data='{ row }'>
                    <span class='whitespace-nowrap text-xs text-slate-500 tabular-nums'>
                        {{ formatDateTime(row.createdAt) }}
                    </span>
                </template>
                <template #pinnedUntil-data='{ row }'>
                    <span v-if='row.isPinned' class='inline-flex items-center gap-1 whitespace-nowrap rounded px-1.5 py-0.5 text-xs font-medium text-indigo-700 bg-indigo-50'>
                        <UIcon name='i-heroicons-map-pin' class='h-3.5 w-3.5' />
                        bis {{ formatDate(row.pinnedUntil) }}
                    </span>
                    <span v-else-if='row.pinnedUntil' class='whitespace-nowrap text-xs text-slate-400' :title='`Abgelaufen: ${formatDate(row.pinnedUntil)}`'>
                        abgelaufen
                    </span>
                    <span v-else class='text-xs text-slate-300'>–</span>
                </template>
                <template #title-data='{ row }'>
                    <span :data-testid='`notification-row-${row.id}`' class='line-clamp-2 font-medium text-slate-800' :title='row.title'>{{ row.title }}</span>
                </template>
                <template #courses-data='{ row }'>
                    <span v-if='row.isGlobal' class='inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium text-amber-700 bg-amber-50'>
                        <UIcon name='i-heroicons-globe-alt' class='h-3.5 w-3.5' />
                        Alle Kurse
                    </span>
                    <div v-else class='flex max-w-[260px] flex-wrap gap-1'>
                        <span
                            v-for='course in row.courses'
                            :key='course.id'
                            class='whitespace-nowrap rounded px-1.5 py-0.5 text-xs text-slate-600 bg-slate-100'
                        >
                            {{ formatNotificationCourse(course) }}
                        </span>
                    </div>
                </template>
                <template #message-data='{ row }'>
                    <span class='block max-w-[280px] line-clamp-2 text-xs text-slate-500' :title='row.message'>
                        {{ row.message }}
                    </span>
                </template>
                <template #authorName-data='{ row }'>
                    <span class='whitespace-nowrap text-xs text-slate-500'>{{ row.authorName || '–' }}</span>
                </template>
                <template #actions-data='{ row }'>
                    <div class='flex shrink-0 justify-end gap-0.5'>
                        <UButton
                            :data-testid='`edit-notification-${row.id}`'
                            icon='i-heroicons-pencil'
                            size='xs'
                            variant='ghost'
                            @click="emit('edit', row)"
                        />
                        <UButton
                            :data-testid='`delete-notification-${row.id}`'
                            icon='i-heroicons-trash'
                            size='xs'
                            variant='ghost'
                            color='red'
                            @click="emit('delete', row)"
                        />
                    </div>
                </template>
            </UTable>
        </div>
        <div class='border-t border-slate-100 px-4 py-4 sm:flex sm:items-center sm:justify-between sm:px-6'>
            <p class='text-sm text-slate-500'>
                {{ resultSummary }}
            </p>
            <UPagination
                v-if='showPagination'
                :model-value='currentPage'
                :page-count='pageSize'
                :total='totalNotifications'
                :show-first='true'
                :show-last='true'
                @update:model-value="emit('update:currentPage', $event)"
            />
        </div>
    </template>
</UCard>
</template>

<script setup lang="ts">
import type { Notification } from '~/types';

defineProps<{
    loading: boolean,
    notifications: Notification[],
    columns: Array<{ key: string; label: string }>,
    resultSummary: string,
    showPagination: boolean,
    currentPage: number,
    pageSize: number,
    totalNotifications: number,
}>();

const emit = defineEmits<{
    (event: 'edit', value: Notification): void,
    (event: 'delete', value: Notification): void,
    (event: 'update:currentPage', value: number): void,
}>();

const { formatDate, formatDateTime, formatNotificationCourse } = useHelpers();
</script>
