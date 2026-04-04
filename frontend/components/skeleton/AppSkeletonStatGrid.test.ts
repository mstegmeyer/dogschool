import { beforeEach, describe, expect, it } from 'vitest';
import AppSkeletonStatGrid from './AppSkeletonStatGrid.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AppSkeletonStatGrid', () => {
    beforeEach(() => {
        installComponentGlobals();
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
});
