import { beforeEach, describe, expect, it } from 'vitest';
import CustomerCalendarSubscriptionModal from './SubscriptionModal.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomerCalendarSubscriptionModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders the calendar subscription modal and emits copy and open actions', async () => {
        const wrapper = mountComponent(CustomerCalendarSubscriptionModal, {
            props: {
                modelValue: true,
                calendarSubscriptionUrl: 'https://example.test/calendar.ics',
                calendarSubscriptionWebcalUrl: 'webcal://example.test/calendar.ics',
            },
        });

        await wrapper.get('[data-testid="copy-calendar-url"]').trigger('click');
        await wrapper.get('[data-testid="open-calendar-url"]').trigger('click');

        expect(wrapper.text()).toContain('Kalender abonnieren');
        expect(wrapper.emitted('copy')).toHaveLength(1);
        expect(wrapper.emitted('open')).toHaveLength(1);
    });
});
