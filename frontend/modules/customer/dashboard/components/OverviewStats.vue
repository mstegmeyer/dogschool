<template>
<section class='mt-8'>
    <div class='mb-3'>
        <h3 class='font-semibold text-slate-800'>
            Überblick
        </h3>
        <p class='text-sm text-slate-500'>
            Die wichtigsten Kennzahlen findest du hier als schnelle Zusammenfassung.
        </p>
    </div>

    <AppSkeletonStatGrid
        v-if='loading'
        :count='3'
        centered
        grid-classes='grid grid-cols-1 gap-4 sm:grid-cols-3'
    />
    <div v-else class='grid grid-cols-1 gap-4 sm:grid-cols-3'>
        <UCard>
            <div class='text-center'>
                <p class='text-3xl font-bold' :class="creditBalance >= 0 ? 'text-komm-600' : 'text-red-500'">
                    {{ creditBalance }}
                </p>
                <p class='mt-1 text-xs text-slate-400'>
                    Guthaben (Credits)
                </p>
            </div>
        </UCard>
        <UCard>
            <div class='text-center'>
                <p class='text-3xl font-bold text-blue-600'>
                    {{ subscribedCourseCount }}
                </p>
                <p class='mt-1 text-xs text-slate-400'>
                    Abonnierte Kurse
                </p>
            </div>
        </UCard>
        <UCard>
            <div class='text-center'>
                <p class='text-3xl font-bold text-purple-600'>
                    {{ dogCount }}
                </p>
                <p class='mt-1 text-xs text-slate-400'>
                    Registrierte Hunde
                </p>
            </div>
        </UCard>
    </div>
</section>
</template>

<script setup lang="ts">
defineProps<{
    loading: boolean,
    creditBalance: number,
    subscribedCourseCount: number,
    dogCount: number,
}>();
</script>
