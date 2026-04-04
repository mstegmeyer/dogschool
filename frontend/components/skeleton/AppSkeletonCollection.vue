<template>
<div>
    <div :class='mobileClass'>
        <div
            v-for='index in props.mobileCards'
            :key='`mobile-${index}`'
            data-testid='skeleton-mobile-card'
            class='rounded-lg border border-slate-200 bg-white p-4'
        >
            <div class='flex items-start justify-between gap-3'>
                <div class='min-w-0 flex-1 space-y-2'>
                    <USkeleton class='h-4 w-32 max-w-full rounded-md' />
                    <USkeleton class='h-3 w-24 max-w-full rounded-md' />
                </div>
                <USkeleton
                    v-if='props.showBadge'
                    class='h-5 w-16 shrink-0 rounded-full'
                />
            </div>

            <div v-if='props.metaColumns > 0' class='mt-4 grid grid-cols-2 gap-3'>
                <div v-for='metaIndex in props.metaColumns' :key='`meta-${index}-${metaIndex}`' class='space-y-2'>
                    <USkeleton class='h-3 w-16 rounded-md' />
                    <USkeleton class='h-4 w-20 rounded-md' />
                </div>
            </div>

            <div v-else class='mt-4 space-y-2'>
                <USkeleton
                    v-for='lineIndex in props.contentLines'
                    :key='`line-${index}-${lineIndex}`'
                    class='h-3 rounded-md'
                    :class="lineIndex === props.contentLines ? 'w-2/3' : 'w-full'"
                />
            </div>

            <div v-if='props.showActions' class='mt-4 flex gap-2'>
                <USkeleton class='h-8 w-24 rounded-md' />
                <USkeleton class='h-8 w-24 rounded-md' />
            </div>
        </div>
    </div>

    <div v-if='props.showDesktopTable' class='hidden md:block'>
        <div class='overflow-hidden rounded-lg border border-slate-200'>
            <div class='grid gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3' :style='desktopGridStyle'>
                <USkeleton
                    v-for='column in props.desktopColumns'
                    :key='`head-${column}`'
                    class='h-3 rounded-md'
                    :class="column === props.desktopColumns ? 'w-12' : 'w-20'"
                />
            </div>

            <div
                v-for='row in props.desktopRows'
                :key='`row-${row}`'
                data-testid='skeleton-desktop-row'
                class='grid gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0'
                :style='desktopGridStyle'
            >
                <USkeleton
                    v-for='column in props.desktopColumns'
                    :key='`cell-${row}-${column}`'
                    class='h-4 rounded-md'
                    :class="column === props.desktopColumns ? 'w-10 justify-self-end' : column === 1 ? 'w-32' : 'w-20'"
                />
            </div>
        </div>
    </div>
</div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
    mobileCards?: number,
    desktopRows?: number,
    desktopColumns?: number,
    metaColumns?: number,
    contentLines?: number,
    showBadge?: boolean,
    showActions?: boolean,
    showDesktopTable?: boolean,
}>(), {
    mobileCards: 3,
    desktopRows: 5,
    desktopColumns: 4,
    metaColumns: 4,
    contentLines: 3,
    showBadge: true,
    showActions: false,
    showDesktopTable: true,
});

const mobileClass = computed(() => props.showDesktopTable ? 'space-y-3 md:hidden' : 'space-y-3');
const desktopGridStyle = computed(() => ({
    gridTemplateColumns: `repeat(${props.desktopColumns}, minmax(0, 1fr))`,
}));
</script>
