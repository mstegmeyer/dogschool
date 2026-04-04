import { beforeEach, describe, expect, it } from 'vitest';
import TodayScheduleCard from './TodayScheduleCard.vue';
import {
    installComponentGlobals,
    makeCourseDate,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('TodayScheduleCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders today schedule rows with booking and cancellation state', () => {
        const wrapper = mountComponent(TodayScheduleCard, {
            props: {
                loading: false,
                courseDates: [
                    makeCourseDate({
                        bookingCount: 1,
                        bookings: [{ id: 'booking-1', dogName: 'Luna', customerName: 'Max' }],
                        cancelled: true,
                    }),
                ],
            },
        });

        expect(wrapper.text()).toContain('Agility');
        expect(wrapper.text()).toContain('1');
        expect(wrapper.text()).toContain('Abgesagt');
    });
});
