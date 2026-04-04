<template>
<div :class='props.columnsClass'>
    <div
        v-for='day in props.days'
        :key='day'
        data-testid='skeleton-calendar-day'
        :class='props.dayClass'
    >
        <div class='mb-2 text-center'>
            <USkeleton class='mx-auto h-3 w-10 rounded-md' />
            <USkeleton class='mx-auto mt-2 h-4 w-14 rounded-md' />
        </div>

        <div class='space-y-2'>
            <div
                v-for='card in props.cardsPerDay'
                :key='`${day}-${card}`'
                class='rounded-lg border border-slate-200 bg-white p-3'
            >
                <USkeleton class='h-4 w-24 rounded-md' />
                <USkeleton class='mt-2 h-3 w-20 rounded-md' />
                <div class='mt-3 flex items-center gap-2'>
                    <USkeleton class='h-3 w-8 rounded-md' />
                    <USkeleton class='h-3 w-8 rounded-md' />
                    <USkeleton class='ml-auto h-5 w-14 rounded-full' />
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
    days?: number,
    cardsPerDay?: number,
    columnsClass?: string,
    dayClass?: string,
}>(), {
    days: 7,
    cardsPerDay: 2,
    columnsClass: 'grid grid-cols-1 gap-3 lg:grid-cols-7',
    dayClass: 'min-h-[180px]',
});
</script>
