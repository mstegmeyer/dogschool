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
                courseDate: baseBookedCourseDate,
                condensed: false,
                dogs: [baseDog],
            },
        });

        await wrapper.get('button').trigger('click');

        expect(wrapper.text()).toContain('Gebucht');
        expect(wrapper.text()).toContain('Luna');
        expect(wrapper.emitted('cancel-booking')).toHaveLength(1);
    });
});
