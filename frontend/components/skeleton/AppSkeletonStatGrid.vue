<template>
<div :class='props.gridClasses'>
    <UCard v-for='index in props.count' :key='index' data-testid='skeleton-stat-card'>
        <div v-if='props.centered' class='space-y-3 py-1 text-center'>
            <USkeleton class='mx-auto h-8 w-20 rounded-md' />
            <USkeleton class='mx-auto h-3 w-28 rounded-md' />
        </div>
        <div v-else class='flex items-center gap-3'>
            <USkeleton class='h-10 w-10 rounded-lg' />
            <div class='space-y-2'>
                <USkeleton class='h-7 w-16 rounded-md' />
                <USkeleton class='h-3 w-24 rounded-md' />
            </div>
        </div>
    </UCard>
</div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
    count?: number,
    gridClasses?: string,
    centered?: boolean,
}>(), {
    count: 4,
    gridClasses: 'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4',
    centered: false,
});
</script>
