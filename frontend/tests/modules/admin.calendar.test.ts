import { computed, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { namedStub, createCalendarTimelineStub, flushPromises } from '../nuxt/page-test-utils';
import {
    apiGetMock,
    baseTrainer,
    installComponentGlobals,
    makeCourseDate,
    mountComponent,
} from '../components/component-test-helpers';

describe('admin calendar page', () => {
    beforeEach(() => {
        vi.resetModules();
        installComponentGlobals({ path: '/admin/calendar' });
        vi.doMock('~/composables/useCalendarView', () => ({
            useCalendarView: (courseDatesRef: { value: unknown[] }) => {
                const viewMode = ref<'day' | 'week'>('week');
                const currentMonday = ref('2026-03-30');

                return {
                    viewMode,
                    currentMonday,
                    weekStart: computed(() => '2026-03-30'),
                    weekEnd: computed(() => '2026-04-05'),
                    visibleDays: computed(() => [{
                        date: '2026-04-04',
                        label: 'Sa',
                        dateShort: '04.04.',
                        isToday: true,
                        courseDates: courseDatesRef.value,
                    }]),
                    prev: vi.fn(),
                    next: vi.fn(),
                    goToday: vi.fn(),
                };
            },
        }));
        apiGetMock.mockImplementation((url: string) => {
            if (url === '/api/admin/trainers') {
                return Promise.resolve({ items: [baseTrainer] });
            }
            if (url === '/api/admin/calendar?week=2026-03-30') {
                return Promise.resolve({ items: [makeCourseDate()] });
            }
            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads the calendar and opens the selected course date in the detail modal', async () => {
        const Page = (await import('~/modules/admin/calendar/index.vue')).default;
        const wrapper = mountComponent(Page, {
            global: {
                stubs: {
                    AppCalendarTimeline: createCalendarTimelineStub(),
                    AdminCalendarDetailModal: namedStub(
                        'AdminCalendarDetailModal',
                        [
                            'modelValue',
                            'selectedDate',
                            'trainerOptions',
                            'selectedTrainerId',
                            'savingTrainer',
                            'cancelling',
                            'cancelNotify',
                            'cancelNotifyTitle',
                            'cancelNotifyMessage',
                        ],
                        [
                            'update:selectedTrainerId',
                            'update:cancelNotify',
                            'update:cancelNotifyTitle',
                            'update:cancelNotifyMessage',
                            'save-trainer',
                            'cancel-date',
                            'uncancel-date',
                            'update:modelValue',
                        ],
                    ),
                    AdminCalendarEventCard: namedStub('AdminCalendarEventCard', ['courseDate', 'condensed']),
                },
            },
        });

        await flushPromises();

        expect(apiGetMock).toHaveBeenCalledWith('/api/admin/trainers');
        expect(apiGetMock).toHaveBeenCalledWith('/api/admin/calendar?week=2026-03-30');

        const eventButton = wrapper.findAll('button').find(button =>
            button.attributes('class')?.includes('border-slate-200'),
        );
        expect(eventButton).toBeDefined();
        await eventButton!.trigger('click');
        await flushPromises();

        const detailModal = wrapper.getComponent({ name: 'AdminCalendarDetailModal' });
        expect(detailModal.props('selectedDate')).toMatchObject({ id: 'course-date-1' });
        expect(detailModal.props('trainerOptions')).toEqual([
            { label: 'Standard vom Kurs verwenden', value: '' },
            { label: 'Lea', value: 'trainer-1' },
        ]);
    });
});
