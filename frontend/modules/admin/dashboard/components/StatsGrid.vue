<template>
<div>
    <AppSkeletonStatGrid
        v-if='loading'
        class='mb-8'
        :count='3'
        grid-classes='grid grid-cols-1 gap-4 mb-8 sm:grid-cols-2 lg:grid-cols-3'
    />
    <div v-else class='mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3'>
        <UCard v-for='stat in stats' :key='stat.label'>
            <div class='flex items-center gap-3'>
                <div class='rounded-lg p-2' :class='stat.bgClass'>
                    <UIcon :name='stat.icon' class='h-6 w-6' :class='stat.iconClass' />
                </div>
                <div>
                    <p class='text-2xl font-bold text-slate-800'>
                        {{ stat.value }}
                    </p>
                    <p class='text-xs text-slate-500'>
                        {{ stat.label }}
                    </p>
                </div>
            </div>
        </UCard>
    </div>
</div>
</template>

<script setup lang="ts">
import type { DashboardStat } from '../types';

defineProps<{
    loading: boolean,
    stats: DashboardStat[],
}>();
</script>
