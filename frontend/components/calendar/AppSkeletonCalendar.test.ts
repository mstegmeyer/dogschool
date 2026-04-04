import { beforeEach, describe, expect, it } from 'vitest';
import AppSkeletonCalendar from './AppSkeletonCalendar.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AppSkeletonCalendar', () => {
    beforeEach(() => {
        installComponentGlobals();
    });

    it('renders the requested number of skeleton calendar columns', () => {
        const wrapper = mountComponent(AppSkeletonCalendar, {
            props: {
                days: 5,
                cardsPerDay: 1,
            },
        });

        expect(wrapper.findAll('[data-testid="skeleton-calendar-day"]')).toHaveLength(5);
    });
});
