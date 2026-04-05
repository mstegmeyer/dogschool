import { beforeEach, describe, expect, it } from 'vitest';
import CustomerCalendarEventCard from './EventCard.vue';
import {
    baseBookedCourseDate,
    baseDog,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomerCalendarEventCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders booked customer calendar cards and emits cancellation', async () => {
        const wrapper = mountComponent(CustomerCalendarEventCard, {
            props: {
                courseDate: {
                    ...baseBookedCourseDate,
                    comment: 'Nachholstunde',
                },
                condensed: false,
                dogs: [baseDog],
            },
        });

        await wrapper.get('[data-testid^="open-course-date-details-"]').trigger('click');
        await wrapper.get('[data-testid="calendar-event-action"]').trigger('click');

        expect(wrapper.text()).toContain('Gebucht');
        expect(wrapper.text()).toContain('Luna');
        expect(wrapper.text()).not.toContain('Nachholstunde');
        expect(wrapper.emitted('open-details')).toHaveLength(1);
        expect(wrapper.emitted('cancel-booking')).toHaveLength(1);
    });

    it('keeps the title visible and inlines the booked label in condensed cards', () => {
        const wrapper = mountComponent(CustomerCalendarEventCard, {
            props: {
                courseDate: baseBookedCourseDate,
                condensed: true,
                dogs: [baseDog],
            },
        });

        expect(wrapper.get('[data-testid^="open-course-date-details-"]').classes()).toContain('flex-1');
        expect(wrapper.text()).toContain('Agility L1');
        expect(wrapper.text()).toContain('Luna');
        expect(wrapper.text()).not.toContain('Gebucht für');
        expect(wrapper.findComponent({ name: 'u-badge-stub' }).exists()).toBe(false);
    });
});
