import { beforeEach, describe, expect, it } from 'vitest';
import UpcomingDatesCard from './UpcomingDatesCard.vue';
import {
    baseBookedCourseDate,
    baseDog,
    installComponentGlobals,
    makeCourseDate,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('UpcomingDatesCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders upcoming dates with booked and open booking states', async () => {
        const wrapper = mountComponent(UpcomingDatesCard, {
            props: {
                loading: false,
                upcomingDates: [
                    baseBookedCourseDate,
                    makeCourseDate({ id: 'course-date-open', booked: false, subscribed: true, bookingWindowClosed: false }),
                ],
                dogs: [baseDog],
                dogOptions: [{ label: baseDog.name, value: baseDog.id }],
                dogIdByCourseDate: { 'course-date-open': baseDog.id },
                bookingInProgress: null,
            },
        });

        await wrapper.get('[data-testid="dashboard-book-course-date-open"]').trigger('click');

        expect(wrapper.text()).toContain('Gebucht für Luna');
        expect(wrapper.emitted('book')).toHaveLength(1);
    });
});
