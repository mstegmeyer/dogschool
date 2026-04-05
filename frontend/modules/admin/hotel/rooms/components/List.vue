<template>
<UCard>
    <AppSkeletonCollection
        v-if='loading'
        :mobile-cards='4'
        :desktop-rows='5'
        :desktop-columns='4'
        :meta-columns='1'
        :show-actions='true'
    />
    <div v-else-if='rooms.length === 0' class='py-10 text-center text-sm text-slate-400'>
        Noch keine Räume angelegt.
    </div>
    <div v-else class='space-y-3'>
        <div
            v-for='room in rooms'
            :key='room.id'
            :data-testid='`room-card-${room.id}`'
            class='flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between'
        >
            <div>
                <p class='font-medium text-slate-800'>
                    {{ room.name }}
                </p>
                <p class='text-sm text-slate-500'>
                    {{ formatSquareMeters(room.squareMeters) }}
                </p>
            </div>
            <UButton
                :data-testid='`edit-room-${room.id}`'
                size='sm'
                variant='soft'
                label='Bearbeiten'
                @click="emit('edit', room)"
            />
        </div>
    </div>
</UCard>
</template>

<script setup lang="ts">
import type { Room } from '~/types';

defineProps<{
    loading: boolean,
    rooms: Room[],
}>();

const emit = defineEmits<{
    (event: 'edit', value: Room): void,
}>();

const { formatSquareMeters } = useHelpers();
</script>
