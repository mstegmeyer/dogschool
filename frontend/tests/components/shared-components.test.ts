import { beforeEach, describe, expect, it } from 'vitest';
import AppSkeletonCollection from '~/components/skeleton/AppSkeletonCollection.vue';
import AppSkeletonStatGrid from '~/components/skeleton/AppSkeletonStatGrid.vue';
import AppSkeletonCalendar from '~/components/calendar/AppSkeletonCalendar.vue';
import CreditBalanceSummary from '~/components/credits/CreditBalanceSummary.vue';
import CreditHistoryList from '~/components/credits/CreditHistoryList.vue';
import {
    baseCreditTransaction,
    installComponentGlobals,
    makeCreditColumns,
    mountComponent,
} from './component-test-helpers';

describe('shared leaf components', () => {
    beforeEach(() => {
        installComponentGlobals();
    });

    it('renders configurable skeleton collection cards and desktop rows', () => {
        const wrapper = mountComponent(AppSkeletonCollection, {
            props: {
                mobileCards: 2,
                desktopRows: 3,
                desktopColumns: 4,
                metaColumns: 2,
                showActions: true,
            },
        });

        expect(wrapper.findAll('[data-testid="skeleton-mobile-card"]')).toHaveLength(2);
        expect(wrapper.findAll('[data-testid="skeleton-desktop-row"]')).toHaveLength(3);
    });

    it('renders centered statistic placeholders', () => {
        const wrapper = mountComponent(AppSkeletonStatGrid, {
            props: {
                count: 3,
                centered: true,
            },
        });

        expect(wrapper.findAll('[data-testid="skeleton-stat-card"]')).toHaveLength(3);
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

    it('shows compact negative balances with the alert color', () => {
        const wrapper = mountComponent(CreditBalanceSummary, {
            props: {
                loading: false,
                balance: -2,
                compact: true,
            },
        });

        expect(wrapper.text()).toContain('-2');
        expect(wrapper.text()).toContain('Verfügbare Credits');
        expect(wrapper.get('p').classes()).toContain('text-red-500');
    });

    it('renders credit history entries with amount and type formatting', () => {
        const wrapper = mountComponent(CreditHistoryList, {
            props: {
                entries: [baseCreditTransaction],
                columns: makeCreditColumns(),
            },
        });

        expect(wrapper.text()).toContain('+2');
        expect(wrapper.text()).toContain('WEEKLY_GRANT');
        expect(wrapper.text()).toContain('formatted:2026-04-01T10:00:00+02:00');
    });
});
