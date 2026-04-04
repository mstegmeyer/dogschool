import { beforeEach, describe, expect, it } from 'vitest';
import StatsGrid from './StatsGrid.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('StatsGrid', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders dashboard stats', () => {
        const wrapper = mountComponent(StatsGrid, {
            props: {
                loading: false,
                stats: [
                    { label: 'Kunden', value: 12, icon: 'i-users', bgClass: 'bg-sand-100', iconClass: 'text-komm-700' },
                    { label: 'Kurse', value: 8, icon: 'i-course', bgClass: 'bg-blue-100', iconClass: 'text-blue-700' },
                    { label: 'Termine', value: 5, icon: 'i-calendar', bgClass: 'bg-green-100', iconClass: 'text-green-700' },
                ],
            },
        });

        expect(wrapper.text()).toContain('Kunden');
        expect(wrapper.text()).toContain('12');
        expect(wrapper.text()).toContain('Termine');
    });
});
