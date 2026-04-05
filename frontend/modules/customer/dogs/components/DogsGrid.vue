<template>
<div v-if='loading' class='grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3'>
    <UCard v-for='index in 6' :key='index'>
        <div class='flex items-start gap-3'>
            <USkeleton class='h-10 w-10 shrink-0 rounded-full' />
            <div class='min-w-0 flex-1 space-y-2'>
                <USkeleton class='h-4 w-24 rounded-md' />
                <USkeleton class='h-3 w-32 rounded-md' />
                <div class='flex gap-2 pt-1'>
                    <USkeleton class='h-5 w-16 rounded-full' />
                    <USkeleton class='h-5 w-14 rounded-full' />
                </div>
            </div>
        </div>
    </UCard>
</div>
<div v-else-if='dogs.length === 0' data-testid='dogs-empty-state' class='py-12 text-center'>
    <UIcon name='i-heroicons-heart' class='mx-auto mb-3 h-12 w-12 text-slate-300' />
    <p class='text-slate-500'>
        Du hast noch keinen Hund registriert.
    </p>
    <UButton class='mt-4' label='Jetzt hinzufügen' @click="emit('add')" />
</div>
<div v-else class='grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3'>
    <UCard v-for='dog in dogs' :key='dog.id'>
        <div class='flex items-start gap-3'>
            <div class='flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sand-200'>
                <UIcon name='i-heroicons-heart' class='h-5 w-5 text-komm-700' />
            </div>
            <div>
                <h3 class='font-semibold text-slate-800'>
                    {{ dog.name }}
                </h3>
                <p class='mt-0.5 text-sm text-slate-500'>
                    {{ dog.race || 'Rasse unbekannt' }}
                </p>
                <p class='mt-1 text-sm text-slate-500'>
                    Schulterhöhe: {{ dog.shoulderHeightCm }} cm
                </p>
                <div class='mt-2 flex gap-2'>
                    <UBadge
                        v-if='dog.gender'
                        variant='soft'
                        color='gray'
                        size='xs'
                    >
                        {{ dog.gender === 'male' ? 'Rüde' : 'Hündin' }}
                    </UBadge>
                    <UBadge
                        v-if='dog.color'
                        variant='soft'
                        color='gray'
                        size='xs'
                    >
                        {{ dog.color }}
                    </UBadge>
                </div>
            </div>
        </div>
    </UCard>
</div>
</template>

<script setup lang="ts">
import type { Dog } from '~/types';

defineProps<{
    loading: boolean,
    dogs: Dog[],
}>();

const emit = defineEmits<{
    (event: 'add'): void,
}>();
</script>
