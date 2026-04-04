import { beforeEach, describe, expect, it } from 'vitest';
import OverviewStats from './OverviewStats.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('OverviewStats', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders the overview stats cards', () => {
        const wrapper = mountComponent(OverviewStats, {
            props: {
                loading: false,
                creditBalance: 4,
                subscribedCourseCount: 3,
                dogCount: 1,
            },
        });

        expect(wrapper.text()).toContain('Guthaben (Credits)');
        expect(wrapper.text()).toContain('Abonnierte Kurse');
        expect(wrapper.text()).toContain('Registrierte Hunde');
    });
});
