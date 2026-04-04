import { beforeEach, describe, expect, it } from 'vitest';
import AdminCalendarEventCard from './EventCard.vue';
import {
    installComponentGlobals,
    makeCourseDate,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AdminCalendarEventCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders the admin calendar event card with booking and subscriber counts', () => {
        const wrapper = mountComponent(AdminCalendarEventCard, {
            props: {
                courseDate: makeCourseDate({
                    bookingCount: 1,
                    bookings: [{ id: 'booking-1', dogName: 'Luna', customerName: 'Max' }],
                    subscriberCount: 4,
                }),
                condensed: false,
            },
        });

        expect(wrapper.text()).toContain('Agility L1');
        expect(wrapper.text()).toContain('1');
        expect(wrapper.text()).toContain('4');
    });
});
