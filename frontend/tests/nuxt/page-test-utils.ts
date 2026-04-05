import { defineComponent, h, onBeforeUnmount, onMounted, reactive, ref, watch, computed, nextTick } from 'vue';
import { vi } from 'vitest';

export async function flushPromises(): Promise<void> {
    await Promise.resolve();
    await nextTick();
    await Promise.resolve();
}

export function installNuxtGlobals(): void {
    vi.stubGlobal('ref', ref);
    vi.stubGlobal('reactive', reactive);
    vi.stubGlobal('computed', computed);
    vi.stubGlobal('watch', watch);
    vi.stubGlobal('onMounted', onMounted);
    vi.stubGlobal('onBeforeUnmount', onBeforeUnmount);
    vi.stubGlobal('definePageMeta', vi.fn());
}

export function namedStub(name: string, propNames: string[] = [], emitNames: string[] = []) {
    return defineComponent({
        name,
        emits: emitNames,
        props: Object.fromEntries(propNames.map(propName => [propName, { default: undefined }])),
        setup(_, { slots }) {
            return () => h('div', { 'data-testid': `${name}-stub` }, slots.default?.());
        },
    });
}

export function createCalendarTimelineStub() {
    return defineComponent({
        name: 'app-calendar-timeline',
        emits: ['select'],
        props: {
            days: { type: Array, default: () => [] },
            viewMode: { type: String, default: 'week' },
            density: { type: String, default: 'default' },
            emptyLabel: { type: String, default: '' },
            eventClass: { type: Function, default: () => '' },
        },
        setup(props, { emit, slots }) {
            return () => h('div', [
                ...(props.days as any[]).flatMap(day => {
                    const courseDates = day?.courseDates ?? [];
                    if (courseDates.length === 0) {
                        return [h('div', props.emptyLabel)];
                    }

                    return courseDates.map(courseDate => h('button', {
                        type: 'button',
                        class: props.eventClass(courseDate),
                        onClick: () => emit('select', courseDate),
                    }, slots.event?.({ courseDate, condensed: false })));
                }),
            ]);
        },
    });
}
