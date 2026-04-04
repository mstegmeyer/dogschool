<template>
<img
    src='/logo-komm.png'
    alt='Komm! Hundeschule & Hundehotel'
    class='w-auto object-contain object-left'
    :class='[sizeClass, toneClass]'
    loading='lazy'
    decoding='async'
    width='600'
    height='157'
>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
    /** Natural colors on light backgrounds (sand/cream/white). */
        tone?: 'on-light' | 'on-dark',
        /** Visual height of the logo bar. */
        size?: 'sm' | 'md' | 'lg',
    }>(),
    { tone: 'on-light', size: 'md' },
);

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-7 max-w-[9rem]';
        case 'lg':
            return 'h-14 max-w-[18rem] mx-auto';
        default:
            return 'h-9 max-w-[12rem]';
    }
});

/** Dark sidebar / header: invert mark so it reads as light on komm-900. */
const toneClass = computed(() =>
    props.tone === 'on-dark' ? 'brightness-0 invert opacity-95' : '',
);
</script>
