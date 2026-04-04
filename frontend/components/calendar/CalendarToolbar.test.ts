import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import CalendarToolbar from './CalendarToolbar.vue';
import { uiPageStubs } from '~/tests/nuxt/ui-test-stubs';

describe('CalendarToolbar', () => {
    it('renders the title and emits navigation and view events', async () => {
        const wrapper = mount(CalendarToolbar, {
            props: {
                title: 'Kalender',
                viewMode: 'week',
                rangeLabel: '01.04.2026 - 07.04.2026',
            },
            global: {
                stubs: uiPageStubs,
            },
            slots: {
                'title-actions': '<span>Aktion</span>',
            },
        });

        expect(wrapper.text()).toContain('Kalender');
        expect(wrapper.text()).toContain('Aktion');
        expect(wrapper.text()).toContain('01.04.2026 - 07.04.2026');

        await wrapper.get('[data-testid="calendar-view-day"]').trigger('click');
        await wrapper.get('[data-testid="calendar-prev"]').trigger('click');
        await wrapper.get('[data-testid="calendar-next"]').trigger('click');
        await wrapper.get('[data-testid="calendar-today"]').trigger('click');

        expect(wrapper.emitted('update:viewMode')).toEqual([['day']]);
        expect(wrapper.emitted('prev')).toHaveLength(1);
        expect(wrapper.emitted('next')).toHaveLength(1);
        expect(wrapper.emitted('today')).toHaveLength(1);
    });
});
