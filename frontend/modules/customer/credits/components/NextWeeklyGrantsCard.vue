<template>
<UCard v-if='loading' class='mb-6'>
    <template #header>
        <USkeleton class='h-5 w-36 rounded-md' />
    </template>
    <div class='space-y-3'>
        <USkeleton class='h-4 w-full rounded-md' />
        <div
            v-for='index in 2'
            :key='index'
            class='rounded-lg border border-slate-100 bg-slate-50/80 p-3'
        >
            <USkeleton class='h-4 w-32 rounded-md' />
            <USkeleton class='mt-2 h-3 w-40 rounded-md' />
        </div>
    </div>
</UCard>
<UCard v-else-if='items.length > 0' class='mb-6'>
    <template #header>
        <h3 class='font-semibold text-slate-800'>
            Nächste Gutschriften
        </h3>
    </template>
    <p class='mb-3 text-xs text-slate-500'>
        Bei laufenden Dauer-Verträgen werden Credits einmal pro Kalenderwoche gutgeschrieben (siehe Verlauf). Unten siehst du den nächsten voraussichtlichen Zuschlagstermin.
    </p>
    <ul class='space-y-3'>
        <li
            v-for='item in items'
            :key='item.contractId'
            class='rounded-lg border border-slate-100 bg-slate-50/80 p-3 text-sm'
        >
            <p class='font-medium text-slate-800'>
                +{{ item.amount }} {{ item.amount === 1 ? 'Credit' : 'Credits' }}
                <span v-if='item.dogName' class='font-normal text-slate-500'> · {{ item.dogName }}</span>
            </p>
            <p class='mt-1 text-xs text-slate-600'>
                Nächster Zuschlag: <span class='font-medium'>{{ formatDateTime(item.nextGrantAt) }}</span>
                <span v-if='item.pendingGrantThisWeek' class='text-amber-700'> · diese Woche noch ausstehend</span>
            </p>
        </li>
    </ul>
</UCard>
</template>

<script setup lang="ts">
import type { NextWeeklyGrantHint } from '~/types';

defineProps<{
    loading: boolean,
    items: NextWeeklyGrantHint[],
}>();

const { formatDateTime } = useHelpers();
</script>
