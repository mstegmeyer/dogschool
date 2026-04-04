<template>
<div class='overflow-hidden rounded-lg border border-slate-200 bg-white'>
    <div
        class='flex items-center justify-between border-b px-3 py-2'
        :class="variant === 'available' ? 'border-slate-200 bg-slate-50' : 'border-komm-100 bg-komm-50'"
    >
        <span :class="variant === 'available' ? 'text-sm font-semibold text-slate-700' : 'text-sm font-semibold text-komm-800'">
            {{ dayName(group.dayOfWeek) }}
        </span>
        <template v-if="variant === 'available'">
            <span class='text-xs text-slate-400'>{{ group.courses.length }} Kurse</span>
        </template>
        <template v-else>
            <UBadge color='primary' variant='soft' size='xs'>
                {{ group.courses.length }}
            </UBadge>
        </template>
    </div>

    <div class='divide-y divide-slate-100 md:hidden'>
        <div
            v-for='course in group.courses'
            :key='course.id'
            :data-testid='`course-${variant}-card-${course.id}`'
            class='space-y-3 p-3 transition hover:bg-slate-50/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-komm-300'
            role='button'
            tabindex='0'
            @click="emit('select', course)"
            @keydown.enter.prevent="emit('select', course)"
            @keydown.space.prevent="emit('select', course)"
        >
            <div class='flex items-start justify-between gap-3'>
                <div class='min-w-0'>
                    <p class='font-medium leading-tight text-slate-800'>
                        {{ course.type?.name || 'Kurs' }}
                    </p>
                    <p v-if='course.comment' class='mt-1 text-xs text-slate-500'>
                        {{ course.comment }}
                    </p>
                </div>
                <UBadge
                    v-if='course.type?.code'
                    variant='soft'
                    color='gray'
                    size='xs'
                >
                    {{ course.type.code }}
                </UBadge>
            </div>
            <div class='grid grid-cols-2 gap-3 text-xs'>
                <div>
                    <p class='text-slate-400'>
                        Zeit
                    </p>
                    <p class='font-medium text-slate-700'>
                        {{ course.startTime }}–{{ course.endTime }}
                    </p>
                </div>
                <div>
                    <p class='text-slate-400'>
                        Stufe
                    </p>
                    <p class='font-medium text-slate-700'>
                        {{ levelLabel(course.level) }}
                    </p>
                </div>
            </div>
            <UButton
                :data-testid='`course-${variant}-subscription-action-${course.id}`'
                :color="isSubscribed(course.id) ? 'red' : undefined"
                :variant="isSubscribed(course.id) ? 'soft' : 'solid'"
                block
                :label="isSubscribed(course.id) ? 'Abbestellen' : 'Abonnieren'"
                @click.stop='emitSubscriptionAction(course)'
            />
        </div>
    </div>

    <div class='hidden overflow-x-auto md:block'>
        <table class='w-full text-sm'>
            <thead>
                <tr class='border-b border-slate-100 text-left text-xs text-slate-500'>
                    <th class='px-3 py-1.5 font-medium'>
                        Kurs
                    </th>
                    <th class='w-[88px] px-2 py-1.5 font-medium'>
                        Zeit
                    </th>
                    <th class='w-[72px] px-2 py-1.5 font-medium'>
                        Stufe
                    </th>
                    <th class='w-[100px] px-2 py-1.5 text-right font-medium'>
                        Aktion
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for='course in group.courses'
                    :key='course.id'
                    :data-testid='`course-${variant}-row-${course.id}`'
                    class='border-b border-slate-50 last:border-0 hover:bg-slate-50/80 focus-within:bg-slate-50/80'
                    tabindex='0'
                    @click="emit('select', course)"
                    @keydown.enter.prevent="emit('select', course)"
                    @keydown.space.prevent="emit('select', course)"
                >
                    <td class='px-3 py-1.5'>
                        <div class='font-medium leading-tight text-slate-800'>
                            {{ course.type?.name || 'Kurs' }}
                            <UBadge
                                v-if='course.type?.code'
                                variant='soft'
                                color='gray'
                                size='xs'
                                class='ml-1 align-middle'
                            >
                                {{ course.type.code }}
                            </UBadge>
                        </div>
                        <p v-if='course.comment' class='mt-0.5 max-w-[280px] truncate text-xs text-slate-400' :title='course.comment'>
                            {{ course.comment }}
                        </p>
                    </td>
                    <td class='whitespace-nowrap px-2 py-1.5 tabular-nums text-slate-600'>
                        {{ course.startTime }}–{{ course.endTime }}
                    </td>
                    <td class='px-2 py-1.5 text-xs text-slate-500'>
                        {{ levelLabel(course.level) }}
                    </td>
                    <td class='whitespace-nowrap px-2 py-1 text-right'>
                        <UButton
                            :data-testid='`course-${variant}-subscription-action-${course.id}`'
                            :color="isSubscribed(course.id) ? 'red' : undefined"
                            :variant="isSubscribed(course.id) ? 'soft' : 'solid'"
                            size='xs'
                            :label="isSubscribed(course.id) ? 'Abbestellen' : 'Abonnieren'"
                            @click.stop='emitSubscriptionAction(course)'
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</template>

<script setup lang="ts">
import type { PropType } from 'vue';
import type { Course } from '~/types';

interface CustomerCourseGroup {
    dayOfWeek: number,
    courses: Course[],
}

const props = defineProps({
    group: {
        type: Object as PropType<CustomerCourseGroup>,
        required: true,
    },
    variant: {
        type: String as PropType<'available' | 'subscribed'>,
        required: true,
    },
    subscribedIds: {
        type: Object as PropType<Set<string>>,
        required: true,
    },
});

const emit = defineEmits<{
    (event: 'select', course: Course): void,
    (event: 'subscribe', course: Course): void,
    (event: 'unsubscribe', course: Course): void,
}>();

const { dayName, levelLabel } = useHelpers();

function isSubscribed(courseId: string): boolean {
    return props.variant === 'subscribed' || props.subscribedIds.has(courseId);
}

function emitSubscriptionAction(course: Course): void {
    if (isSubscribed(course.id)) {
        emit('unsubscribe', course);
        return;
    }

    emit('subscribe', course);
}
</script>
